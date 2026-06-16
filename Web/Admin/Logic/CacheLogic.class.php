<?php
namespace Admin\Logic;

use Admin\Model\CooperationModel;

class CacheLogic
{
    /**
     * 这个是用来保存key和接口的管理关系的缓存
     * @return mixed
     */
    public function get_channel_key_manger(){
        $return=S('channel_key_manger');
        if($return){return $return;}
        $return=(new CooperationModel())->getAllUtmSourceConfig();
        S('channel_key_manger',$return);
        return $return;
    }
    public function clear_channel_key_manger(){
        S('channel_key_manger',NULL);
    }

    /**
     * 下面是管理系统所有的常量缓存
     */
    public function get_all_config(){
        $all_return=S('all_config');
        if($all_return){return $all_return;}
        $configInfo=M('config')->where(array('is_delete'=>1))->getField('name,type,value');
        if(empty($configInfo)){return array();}
        $all_return=array();
        foreach($configInfo as $k=>$oneConfig){
            if($oneConfig['type']==1){//如果是单个数据
                $all_return[$k]=$oneConfig['value']?$oneConfig['value']:0;
            }elseif($configInfo['type']==2){//如果不是单个数据,就是json化的字符串
                $all_return[$k]=json_decode($oneConfig['value'],true)?json_decode($oneConfig['value'],true):array();
                $all_return[$k]=array_values($all_return[$k]);
            }else{
                $all_return[$k]=json_decode($oneConfig['value'],true)?json_decode($oneConfig['value'],true):array();
            }
        }
        S('all_config',$all_return);
        return $all_return;
    }
    public function clear_all_config(){
        S('all_config',NULL);
    }

    /**
     * 下面是管理系统的所有系统设置
     */
    public function get_all_setting(){
        $all_return=S('all_setting');
        if($all_return){return $all_return;}
        $all_return=M('setting')->where(array('type'=>'system'))->getField('name,data');
        S('all_setting',$all_return);
        return $all_return;
    }
    public function clear_all_setting(){
        S('all_setting',NULL);
    }

    /**
     * 下面获取系统的所有菜单权限
     * @return mixed
     */
    public function get_all_system_menu_rules(){
        $all_return=S('all_system_menu_rules');
        if($all_return){return $all_return;}
        $all_return=M('user_rules')->where(array('status'=>1,'type'=>2))->getField('name',true);
        foreach($all_return as $k=>$v){
            $all_return[$k]=strtolower($v);
        }
        S('all_system_menu_rules',$all_return);
        return $all_return;
    }
    public function clear_all_system_menu_rules(){
        S('all_system_menu_rules',NULL);
    }

    /**
     * 下面获取系统的所有菜单权限
     * @return mixed
     */
    public function get_all_system_menu(){
        $all_return=S('all_system_menu');
        if($all_return){return $all_return;}
        $all_return=M('menu')->where(array('is_delete'=>0,'display'=>1))
            ->order('ordid asc')->field('id,name,pid,module_name,icon,action_name,data')->select();
        foreach($all_return as $k=>$v){
            $all_return[$k]['rule']=strtolower('admin_'.$v['module_name'].'_'.$v['action_name']);
        }
        S('all_system_menu_rules',$all_return);
        return $all_return;
    }
    public function clear_all_system_menu(){
        S('all_system_menu',NULL);
    }

    /**
     * 获取一个人的所有菜单
     * @return array
     */
    public function get_one_user_all_menu($user_id){
        if(!$user_id){return array();}
        $all_return=S('one_user_all_menu_'.$user_id);
        if($all_return){return $all_return;}
        //读取用户所属角色所有信息
        $userInfo = M('user')->where(array('id'=>$user_id,'delete'=>1))->find();
        $userRolesIds=$userInfo['role_ids'];
        $userRolesIds_arr=array_filter(array_unique(explode(',',$userRolesIds)));

        //找出拥有的权限
        $has_rule_ids=array();
        if($userRolesIds_arr){
            $where=array();
            $where['id']=array('in',$userRolesIds_arr);
            $where['status']=1;
            $rolesInfos=M('user_roles')->where($where)->field('rules')->select();
            foreach ($rolesInfos as $rolesInfo) {
                $has_rule_ids = array_merge($has_rule_ids, explode(',',$rolesInfo['rules']));
            }
            $has_rule_ids = array_unique(array_unique($has_rule_ids));
        }

        //找出自己没有的权限
        $where=array();
        if($has_rule_ids){$where['id']=array('not in',$has_rule_ids);}
        $where['status']=1;
        $where['type']  =2;
        $not_has_menu_rule_names=M('user_rules')->where($where)->getField('name',true);
        foreach($not_has_menu_rule_names as $k=>$v){
            $not_has_menu_rule_names[$k]=strtolower($v);
        }
        //下面找出系统所有的菜单
        $all_return=$this->get_all_system_menu();
        //从菜单中去除没有权限的菜单
        foreach($all_return as $k=>$v){
            if(in_array($v['rule'],$not_has_menu_rule_names)){unset($all_return[$k]);}
        }
        S('one_user_all_menu_'.$user_id,$all_return);
        return $all_return;
    }
    public function clear_one_user_all_menu($user_id){
        S('one_user_all_menu_'.$user_id,NULL);
    }



    /**
     * 获取系统的所有的组织架构
     * @return mixed
     */
    public function get_all_branch(){
        $return=S('system_all_branch');
        if($return){return $return;}
        $return = M('company_branch')->where(array('is_delete'=>0))->order('ordid asc,id asc')->select();
        S('system_all_branch',$return);
        return $return;
    }
    public function clear_all_branch(){
        S('system_all_branch',NULL);
    }
    //判断是不是公司
    public function branchIsCompany($branch_id){
        $result = $this->get_all_branch();
        foreach($result as $key=>$value){
            if(($value['id']==$branch_id)&&($value['is_company']==1)){
                return true;
            }
        }
        return false;
    }
    //获取公司的所有组织
    public function getCompanyBranch($company_id){
        $result = $this->get_all_branch();
        $company_sub_branch = getAllChildInArray($company_id,$result,'pid');
        $company_sub_branch[] = $company_id;
        foreach($result as $key=>$value){
            if(!in_array($value['id'],$company_sub_branch)){
                unset($result[$key]);
            }
        }
        return $result;
    }
    //获取某组织的公司id
    public function getBranchCompanyId($branch_id){
        $return = $this->get_all_branch();
        $new_array = array();
        foreach($return as $key=>$value){
            $new_array[$value['id']] = $value;
        }
        $p_ids = getLeaderbranch($branch_id,$new_array);
        $p_ids[] = $branch_id;
        $company_id = 0;
        foreach($p_ids as $key=>$value){
            if($new_array[$value]['is_company'] == 1){
                $company_id = $value;
            }
        }
       return $company_id;
    }
    //获取一个部门及下面所有部门的员工id数组
    public function getOneBranchAllSubUser($branch_id){
        $all_branch = $this->get_all_branch();
        $sub_branch_ids = getAllChildInArray($branch_id,$all_branch,'pid');
        $sub_branch_ids[] = $branch_id;
        //获取这些部门的所有的员工
        $branch_search_user_ids = M('user')->where(array('branch_id'=>array('in',$sub_branch_ids)))->getField('id',true);
        if(!$branch_search_user_ids){$branch_search_user_ids = array(-1);}
        return $branch_search_user_ids;
    }

    /**
     * 获取一个城市的所有公司
     * @param $city
     * @return array
     */
    public function getCityCompany($city){
        $array = array();
        $all_branch = $this->get_all_branch();
        foreach($all_branch as $key=>$value){
            if(($city==$value['city'])&&($value['is_company']==1)){
                $array[] = $value;
            }
        }
        return $array;
    }



    /**
     * 下面获取渠道和公司的对应价格表
     * @return mixed
     */
    public function get_all_company_channel_price(){
        $all_return=S('all_company_channel_price');
        if($all_return){return $all_return;}

        $allCompanyChannel = M('company_channel_price')->select();
        $all_return = array();
        foreach($allCompanyChannel as $key=>$value){
            $all_return[$value['company_id']][$value['channel']]=$value['price'];
        }
        S('all_company_channel_price',$all_return);
        return $all_return;
    }
    public function clear_all_company_channel_price(){
        S('all_company_channel_price',NULL);
    }
}