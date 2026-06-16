<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Admin\Logic\TreeLogic;
use Admin\Model\CompanyScoreLogModel;
use Admin\Model\ConfigModel;
use Think\Controller;

/**
 * 部门管理控制器
 */
class CompanybranchController extends BaseController
{
    /**
     * 首页
     */
    public function index(){
        $tree = new TreeLogic();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';

        $where=array();
        $where['is_delete'] = 0;
        if($this->data['city']){$where['city'] = array('like','%'.$this->data['city'].'%');}

        $result = M('company_branch')->where($where)->order('ordid asc,id asc')->select();
        if($this->userInfo['data_auth'] != 1){
            $company_sub_branch = getAllChildInArray($this->userInfo['company_id'],$result,'pid');
            $company_sub_branch[] = $this->userInfo['company_id'];
            foreach($result as $key=>$value){
                if(!in_array($value['id'],$company_sub_branch)){
                    unset($result[$key]);
                }
            }
        }
        //下面查看部门的管理员
        $allBranchAdminUser=array();
        $branch_ids=array_column($result,'id');
        if($branch_ids){
            $selectUserName = M('user')->where(array('is_admin'=>1,'delete'=>1,'branch_id'=>array('in',$branch_ids)))
                ->field('id,user_name,branch_id')->select();
            foreach($selectUserName as $val){
                $allBranchAdminUser[$val['branch_id']][]=$val;
            }
        }

        $array = array();
        foreach($result as $r) {
            $r['company_remark'] = $r['is_company']==1 ?"<span style='color: #ff0000;'>【公司】</span>":"";
            $str1='';
            foreach($allBranchAdminUser[$r['id']] as $rr){
                $str1.=$rr['user_name'] .' ';
            }
            $r['admin_user']= $str1;
            if($r['is_company']==1){
                $r['business'] = $r['business_name']."（".$r['business_mobile']."）";
                //$r['score_show'] = '<a href="javascript:;" class="J_showdialog" data-uri="'.U('Companybranch/addScore',array('id'=>$r['id'])).'" data-title="添加积分" data-id="addScore" data-width="500" data-height="300">【'.$r['score'].'】</a> |
                $r['score_show'] = '【'.$r['score'].'】 |
                                 <a href="javascript:;" class="J_showdialog" data-uri="'.U('Companybranch/logList',array('id'=>$r['id'],'type'=>1)).'" data-title="加积分日志" data-id="logList" data-width="500" data-height="300">加日志</a> |
                                 <a href="javascript:;" class="J_showdialog" data-uri="'.U('Companybranch/logList',array('id'=>$r['id'],'type'=>2)).'" data-title="减积分日志" data-id="logList" data-width="500" data-height="300">减日志</a>';

                if ($r['status'] == 1) {
                    $r['status'] = '<img data-tdtype="toggle" data-id="'.$r['id'].'" data-field="status" data-value="'.$r['status'].'" src="/static/images/admin/toggle_enabled.gif" />';
                } else {
                    $r['status'] = '<img data-tdtype="toggle" data-id="'.$r['id'].'" data-field="status" data-value="'.$r['status'].'" src="/static/images/admin/toggle_disabled.gif" />';
                }

                $r['str_manage'] = '<a href="javascript:;" class="J_showdialog" data-uri="'.U('Companybranch/add',array('pid'=>$r['id'])).'" data-title="添加子部门" data-id="add" data-width="500" data-height="300">添加子部门</a> |
                                <a href="javascript:;" class="J_showdialog" data-uri="'.U('Companybranch/addCompany',array('id'=>$r['id'])).'" data-title="编辑 - '. $r['name'] .'" data-id="addCompany" data-width="500" data-height="350">编辑</a> |
                                <a href="javascript:;" class="J_confirmurl" data-acttype="ajax" data-uri="'.U('Companybranch/delete',array('id'=>$r['id'])).'" data-msg="确认删除 - '.$r['name'].'">删除</a>';
            }else{
                $r['score_show'] = "";
                $r['status'] = '';
                $r['business'] = '';
                $r['str_manage'] = '<a href="javascript:;" class="J_showdialog" data-uri="'.U('Companybranch/add',array('pid'=>$r['id'])).'" data-title="添加子部门" data-id="add" data-width="500" data-height="300">添加子部门</a> |
                                <a href="javascript:;" class="J_showdialog" data-uri="'.U('Companybranch/edit',array('id'=>$r['id'])).'" data-title="编辑 - '. $r['name'] .'" data-id="edit" data-width="500" data-height="350">编辑</a> |
                                <a href="javascript:;" class="J_confirmurl" data-acttype="ajax" data-uri="'.U('Companybranch/delete',array('id'=>$r['id'])).'" data-msg="确认删除 - '.$r['name'].'">删除</a>';
            }
            $array[] = $r;
        }
        $str  = "<tr>
                <td align='center'><input type='checkbox' value='\$id' class='J_checkitem'></td>
                <td align='center'>\$id</td>
                <td>\$spacer \$company_remark \$name</td>
                <td>\$city</td>
                <td align='center'>\$score_show</td>
                <td>\$remark</td>
                <td>\$admin_user</td>
                <td>\$business</td>
                <td align='center'><span data-tdtype='edit' data-field='ordid' data-id='\$id' class='tdedit'>\$ordid</span></td>
                <td align='center'>\$status</td>
                <td align='center'>\$str_manage</td>
                </tr>";
        $tree->init($array);
        $menu_list = $tree->get_tree(0, $str);
        $this->assign('search', $this->data);
        $this->assign('menu_list', $menu_list);
        $big_menu = array('title' => '添加公司', 'iframe' => U('Companybranch/addCompany'), 'id' => 'addCompany', 'width' => '500', 'height' => '250',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }


    /**
     * 某公司的积分变动日志
     */
    public function logList(){

        $start_time = $this->data['start'];
        if (empty($start_time)) {
            $start_time = strtotime("-7 days");
        } else {
            $start_time = strtotime($start_time);
        }
        $end_time = $this->data['end'];
        if (empty($end_time)) {
            $end_time = time();
        } else {
            $end_time = strtotime('+1 days', strtotime($end_time));
        }
        $where = array();
        $where['company_id'] = $this->data['id'];
        $where['create_time'] = array('between',[$start_time, $end_time]);
        $where['score'] = $this->data['type']==1?array('gt',0):array('lt',0);
        $list = M('company_score_log')->where($where)->order('id desc')->select();
        if (IS_POST) {
            $html = '';
            if ($list && is_array($list)) {
                foreach($list as $key=>$value) {
                    $user_name = '未知';
                    if ($value['user_id'] > 0) {
                        $user_name = M('user')->where(array('id'=>$value['user_id']))->getField('user_name');
                    }
                    $score = $value['score'];
                    $create_time = date('Y-m-d',$value['create_time']);
                    $str = "<tr><td align='center'>{$user_name}</td><td align='center'>{$score}</td><td align='center'>{$create_time}</td></tr>";
                    $html .= $str;
                }
            }
            $this->setjsonReturn($html);
        } else {
            if ($list && is_array($list)) {
                foreach($list as $key=>$value) {
                    $list[$key]['user_name'] = '未知';
                    if ($value['user_id'] > 0) {
                        $list[$key]['user_name'] = M('user')->where(array('id'=>$value['user_id']))->getField('user_name');
                    }
                }
            }
            $this->assign('company_id', $this->data['id']);
            $this->assign('type', $this->data['type']);
            $this->assign('start_time', date('Y-m-d',$start_time));
            $this->assign('end_time', date('Y-m-d',$end_time));
            $this->assign('list', $list);
            $this->display();
        }
    }

    /**
     * 添加部门
     */
    public function addCompany(){
        if (IS_POST) {
            $post=I('post.');
            if(!$post['name']){$this->errorjsonReturn('请填写公司名称');}
            if(!$post['city']){$this->errorjsonReturn('请选择城市');}
            (new CacheLogic())->clear_all_branch();
            if($post['id']){
                M('company_branch')->where(array('id'=>$post['id']))->save($post)!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
            }else{
                $post['is_company'] = 1;
                $post['create_time']=time();
                M('company_branch')->add($post)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
            }
        }else{
            $id = I('get.id');
            if($id){
                $info = M('company_branch')->where(array('id' => $id))->find();
                $this->assign('info', $info);
            }
            $this->assign('job_city',(new ConfigModel())->getConfigByNameModel('job_city'));
            $this->display();

        }
    }


    /**
     * 添加积分
     */
    public function addScore(){
        if (IS_POST) {
            if(!$this->data['id']){$this->errorjsonReturn('请填写公司名称');}
            if(!$this->data['score']){$this->errorjsonReturn('请填写积分');}
            $info =  M('company_branch')->where(array('id'=>$this->data['id']))->find();
            if(!$info){$this->errorjsonReturn('非法操作');}
            $new_score = $info['score'] + $this->data['score'];
            //添加积分变动日志
            (new CompanyScoreLogModel())->addLog($info['id'],$this->data['score'],$this->userInfo['id']);
            M('company_branch')->where(array('id'=>$this->data['id']))->save(array('score'=>$new_score))!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }else{
            $this->assign('id',$this->data['id']);
            $this->display();
        }
    }


    /**
     * ajax修改单个字段值
     */
    public function ajax_edit()
    {
        $get=I('get.');
        if(!$get['id']||!$get['field']){$this->errorjsonReturn('修改失败');}
        //先清除系统部门的缓存
        (new CacheLogic())->clear_all_branch();
        $result = M('company_branch')->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('成功');
    }

    /**
     * 添加部门
     */
    public function add(){
        if (IS_POST) {
            $post=I('post.');
            if(!isset($post['pid'])){$this->errorjsonReturn('请选择上级部门');}
            if(!$post['name']){$this->errorjsonReturn('请填写部门名称');}
            if(!$post['city']){$this->errorjsonReturn('请选择城市');}

            if($this->userInfo['data_auth'] != 1){
                $result = (new CacheLogic())->getCompanyBranch($this->userInfo['company_id']);
                $branch_ids = array_column($result,'id');
                if(!in_array($post['pid'],$branch_ids)){$this->errorjsonReturn('请选择公司下的部门');}
            }

            (new CacheLogic())->clear_all_branch();
            $post['create_time']=time();
            M('company_branch')->add($post)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
        }else{
            $tree = new TreeLogic();

            $result = (new CacheLogic())->get_all_branch();
            if($this->userInfo['data_auth'] != 1){
                $result = (new CacheLogic())->getCompanyBranch($this->userInfo['company_id']);
            }

            $array = array();
            foreach ($result as $r) {
                $r['selected'] = $r['id'] == $_GET['pid'] ? 'selected' : '';
                $array[] = $r;
            }
            $this->assign('job_city',(new ConfigModel())->getConfigByNameModel('job_city'));
            $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
            $tree->init($array);
            $select_menus = $tree->get_tree(0, $str);
            $this->assign('select_menus', $select_menus);
            $this->display();
        }
    }

    /**
     * 修改部门
     */
    public function edit(){
        if (IS_POST) {
            $post=I('post.');
            if(!$post['id']){$this->errorjsonReturn('系统有误，请联系管理员');}
            if(!isset($post['pid'])){$this->errorjsonReturn('请选择上级部门');}
            if(!$post['name']){$this->errorjsonReturn('请填写部门名称');}
            if(!$post['city']){$this->errorjsonReturn('请选择城市');}

            if($this->userInfo['data_auth'] != 1){
                $result = (new CacheLogic())->getCompanyBranch($this->userInfo['company_id']);
                $branch_ids = array_column($result,'id');
                if(!in_array($post['pid'],$branch_ids)){$this->errorjsonReturn('请选择公司下的部门');}
            }

            (new CacheLogic())->clear_all_branch();
            if(M('company_branch')->where(array('id' => $post['id']))->count()==0){$this->errorjsonReturn('修改对象不存在');}
            M('company_branch')->where(array('id' => $post['id']))->save($post)!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }else {
            $id = I('get.id');
            $info = M('company_branch')->where(array('id' => $id))->find();
            $this->assign('info', $info);
            $tree = new TreeLogic();

            $result = (new CacheLogic())->get_all_branch();
            if($this->userInfo['data_auth'] != 1){
                $result = (new CacheLogic())->getCompanyBranch($this->userInfo['company_id']);
            }

            $array = array();
            foreach ($result as $r) {
                $r['selected'] = $r['id'] == $info['pid'] ? 'selected' : '';
                $array[] = $r;
            }
            $this->assign('job_city',(new ConfigModel())->getConfigByNameModel('job_city'));
            $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
            $tree->init($array);
            $select_menus = $tree->get_tree(0, $str);
            $this->assign('select_menus', $select_menus);
            $this->display();
        }
    }

    /**
     * 删除部门
     */
    public function delete()
    {
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        (new CacheLogic())->clear_all_branch();
        $result=M('company_branch')->where(array('id'=>array('in',$ids)))->save(array('is_delete'=>1));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }

}

