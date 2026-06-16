<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Admin\Logic\TreeLogic;
use Admin\Model\AuthModel;
use Admin\Model\ConfigModel;
use Admin\Model\UserModel;
use Think\Controller;
/**
 * 用户相关控制器
 * Class User
 * @package app\admin\controller
 */
class UserController extends BaseController
{
    /**
     * 获取用户列表
     * @return mixed
     */
    public function index(){
        $where=array();
        $where['delete'] = 1;
        if(in_array($this->data['sex'],array(1,-1))){$where['sex'] = $this->data['sex'];}
        if($this->data['user_name']){$where['user_name'] = array('like','%'.$this->data['user_name'].'%');}
        if($this->data['mobile']){$where['mobile'] = array('like','%'.$this->data['mobile'].'%');}

        //下面封装权限，不是全平台权限的查看自己的公司的员工
        if($this->userInfo['data_auth'] != 1){
            $user_ids = (new UserModel())->getCompanyUserId($this->userInfo['company_id']);
            $where['id'][] = array('in',$user_ids);
        }
        if($this->data['branch_id']){
            $branch_search_user_ids = (new CacheLogic())->getOneBranchAllSubUser($this->data['branch_id']);
            $where['id'][] = array('in',$branch_search_user_ids);
        }
        //下面通过城市来查用户
        if($this->data['city']){
           $search_branch_ids = M('company_branch')->where(array('city'=>array('like',"%".$this->data['city']."%")))->getField('id',true);
            if($search_branch_ids){
                $search_user_ids = M('user')->where(array('branch_id'=>array('in',$search_branch_ids)))->getField('id',true);
                if($search_user_ids){
                    $where['id'][] = array('in',$search_user_ids);
                }else{
                    $where['id'][] = array('eq',-1);
                }
            }else{
                $where['id'][] = array('eq',-1);
            }
        }
        //修改版时间插件
        $create_time = $this->data['create_time'];
        if($create_time){
            $dateArr = explode(' - ', $create_time);
            $where['create_time'][] = array('gt',strtotime($dateArr[0]));
            $where['create_time'][] = array('lt',strtotime(date('Y-m-d 23:59:59', strtotime($dateArr[1]))));
        }
        $update_time = $this->data['update_time'];
        if($update_time){
            $dateArr = explode(' - ', $update_time);
            $where['update_time'][] = array('gt',strtotime($dateArr[0]));
            $where['update_time'][] = array('lt',strtotime(date('Y-m-d 23:59:59', strtotime($dateArr[1]))));
        }

        if($this->data['start_time']){ $where['create_time'][] = array('gt',strtotime($this->data['start_time']));}
        if($this->data['end_time']){ $where['create_time'][] = array('lt',(strtotime($this->data['end_time'])+24*60*60));}
        if($this->data['start_time2']){ $where['update_time'][] = array('gt',$this->data['start_time2']." 00:00:00");}
        if($this->data['end_time2']){ $where['update_time'][] = array('lt',$this->data['end_time2']." 23:59:59");}

        $count = M('user')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list =  M('user')->where($where)->Page($this->p,$this->perpage)->order($this->desc)->select();

        $role_ids = array_column($list,'role_ids');
        if($role_ids){
            $user_roles = M('user_roles')->where(array('id'=>array('in',$role_ids)))->getField('id,name,status',true);
        }
        $branch_ids = array_column($list,'branch_id');
        $company_ids = array_column($list,'company_id');
        $branch_ids = array_merge($branch_ids,$company_ids);
        if($branch_ids){
            $branchInfos = M('company_branch')->where(array('id'=>array('in',$branch_ids),'is_delete'=>0))->getField('id,name,city',true);
        }

        $look_user_phone = $this->userInfo['look_user_phone'];
        foreach($list as $key=>$value){
            $list[$key]['company_name'] = isset($branchInfos[$value['company_id']])?$branchInfos[$value['company_id']]['name']:"";
            $list[$key]['branch_name'] = isset($branchInfos[$value['branch_id']])?$branchInfos[$value['branch_id']]['name']:"";
            $list[$key]['city'] = isset($branchInfos[$value['branch_id']])?$branchInfos[$value['branch_id']]['city']:"";
            $list[$key]['mobile'] = (new AuthModel())->doAuthToUserPhone($look_user_phone,$value['mobile']);
            $list[$key]['role_name'] = isset($user_roles[$value['role_ids']])?$user_roles[$value['role_ids']]['name']:"";
        }
        $this->assign('search', $this->data);
        $this->assign('list', $list);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('array_sex', array(1=>'男',-1=>'女'));
        $this->assign('page', $Page->show());
        $big_menu = array('title' => '添加用户', 'iframe' => U('user/add'), 'id' => 'add', 'width' => '500', 'height' => '350',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 获取离职员工列表
     * @return mixed
     */
    public function leavelist(){
        $where=array();
        $where['delete'] = 2;
        if(in_array($this->data['sex'],array(1,-1))){$where['sex'] = $this->data['sex'];}
        if($this->data['user_name']){$where['user_name'] = array('like','%'.$this->data['user_name'].'%');}
        if($this->data['mobile']){$where['mobile'] = array('like','%'.$this->data['mobile'].'%');}

        //下面封装权限，不是全平台权限的查看自己的公司的员工
        if($this->userInfo['data_auth'] != 1){
            $user_ids = (new UserModel())->getCompanyUserId($this->userInfo['company_id']);
            $where['id'][] = array('in',$user_ids);
        }
        if($this->data['branch_id']){
            $branch_search_user_ids = (new CacheLogic())->getOneBranchAllSubUser($this->data['branch_id']);
            $where['id'][] = array('in',$branch_search_user_ids);
        }
        if($this->data['city']){
            $search_branch_ids = M('company_branch')->where(array('city'=>array('like',"%".$this->data['city']."%")))->getField('id',true);
            if($search_branch_ids){
                $search_user_ids = M('user')->where(array('branch_id'=>array('in',$search_branch_ids)))->getField('id',true);
                if($search_user_ids){
                    $where['id'][] = array('in',$search_user_ids);
                }else{
                    $where['id'][] = array('eq',-1);
                }
            }else{
                $where['id'][] = array('eq',-1);
            }
        }

        if($this->data['start_time']){ $where['create_time'][] = array('gt',strtotime($this->data['start_time']));}
        if($this->data['end_time']){ $where['create_time'][] = array('lt',(strtotime($this->data['end_time'])+24*60*60));}

        if($this->data['start_time2']){ $where['update_time'][] = array('gt',$this->data['start_time2']." 00:00:00");}
        if($this->data['end_time2']){ $where['update_time'][] = array('lt',$this->data['end_time2']." 23:59:59");}

        $count = M('user')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list =  M('user')->where($where)->Page($this->p,$this->perpage)->order($this->desc)->select();

        $role_ids = array_column($list,'role_ids');
        if($role_ids){
            $user_roles = M('user_roles')->where(array('id'=>array('in',$role_ids)))->getField('id,name,status',true);
        }

        $branch_ids = array_column($list,'branch_id');
        $company_ids = array_column($list,'company_id');
        $branch_ids = array_merge($branch_ids,$company_ids);
        if($branch_ids){
            $branchInfos = M('company_branch')->where(array('id'=>array('in',$branch_ids),'is_delete'=>0))->getField('id,name,city',true);
        }

        $look_user_phone = $this->userInfo['look_user_phone'];
        foreach($list as $key=>$value){
            $list[$key]['company_name'] = isset($branchInfos[$value['company_id']])?$branchInfos[$value['company_id']]['name']:"";
            $list[$key]['branch_name'] = isset($branchInfos[$value['branch_id']])?$branchInfos[$value['branch_id']]['name']:"";
            $list[$key]['city'] = isset($branchInfos[$value['branch_id']])?$branchInfos[$value['branch_id']]['city']:"";
            $list[$key]['mobile'] = (new AuthModel())->doAuthToUserPhone($look_user_phone,$value['mobile']);
            $list[$key]['role_name'] = isset($user_roles[$value['role_ids']])?$user_roles[$value['role_ids']]['name']:"";
        }
        $this->assign('search', $this->data);
        $this->assign('list', $list);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('array_sex', array(1=>'男',-1=>'女'));
        $this->assign('page', $Page->show());
        $big_menu = array('title' => '添加用户', 'iframe' => U('user/add'), 'id' => 'add', 'width' => '500', 'height' => '350',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需操作项');}
        //将这些用户从分配队列中剔除
        M('allocate_user')->where(array('is_delete'=>0,'user_id'=>array('in',$ids)))->save(array('is_delete'=>1));
        $result=M('user')->where(array('id'=>array('in',$ids)))->save(array('delete'=>2));
        $result===false?$this->errorjsonReturn('操作失败'):$this->setjsonReturn('操作成功');
    }

    /**
     * 设置在职
     */
    public function notdelete()
    {
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需操作项');}
        //租赁商户最多只能增加20个业务员
//        $num = M('user')->where(array('delete'=>1))->count();
//        if ( ($num + count($ids)) >= 10) {
//            $this->errorjsonReturn('您最多只能设置10个在职员工');
//        }
        $result=M('user')->where(array('id'=>array('in',$ids)))->save(array('delete'=>1));
        $result===false?$this->errorjsonReturn('操作失败'):$this->setjsonReturn('操作成功');
    }



    /**
     * 添加修改界面
     */
    public function add(){
        if (IS_POST) {
            $post=I('post.');
            if(!$post['user_name']){$this->errorjsonReturn('请填写用户名');}
            if(!$post['mobile']){$this->errorjsonReturn('请填写手机号');}
            if(!is_phone($post['mobile'])){$this->errorjsonReturn('手机格式不正确');}
            $post['entry_time']=strtotime($post['entry_time']);

            if($this->userInfo['data_auth'] != 1){
                $result = (new CacheLogic())->getCompanyBranch($this->userInfo['company_id']);
                $branch_ids = array_column($result,'id');
                if(!in_array($post['branch_id'],$branch_ids)){$this->errorjsonReturn('请选择公司下的部门');}
                //不是全平台不能修改角色
                unset($post['role_ids']);
                //添加的时候只能改成系统设置的默认的 de角色
                if(!$post['id']){
                    $post['role_ids'] = (new CacheLogic())->get_all_config()['company_can_create_user_for_role'];
                }
            }
            $company_id = (new CacheLogic())->getBranchCompanyId($post['branch_id']);
            if($company_id==0){$this->errorjsonReturn("设置有误，请联系管理员");}
            $post['company_id'] = $company_id;

            if($post['id']){
                //修改的时候不能覆盖了他人的手机号码
                if(M('user')->where(array('mobile'=>$post['mobile'],'delete'=>1,'id'=>array('neq',$post['id'])))->count()>0){$this->errorjsonReturn('手机号已经存在');}

                $info = M('user')->where(array('id'=>$post['id']))->find();
                if(!$info){$this->errorjsonReturn('非法操作');}

                //修改用户信息
                if(M('user')->where(array('id'=>$post['id']))->save($post)===false){
                    $this->errorjsonReturn('修改失败');
                }
            }else{
                //租赁商户最多只能增加20个业务员
//                $num = M('user')->where(array('delete'=>1))->count();
//                if ($num >= 10) {
//                    $this->errorjsonReturn('您最多只能设置10个在职员工');
//                }
                //添加的情况下手机号码不能重复
                if(M('user')->where(array('mobile'=>$post['mobile'],'delete'=>1))->count()>0){$this->errorjsonReturn('手机号已经存在');}
                $post['create_time']=time();
                //查询最初始的密码配置
                $initial_password=(new ConfigModel())->getConfigByNameModel('initial_password');
                $post['pwd']=md5($initial_password);
                $return1 = M('user')->add($post);
                if(!$return1){ $this->errorjsonReturn('添加失败'); }
            }
            $this->setjsonReturn('添加成功');
        }else{
            $info = array();
            if(I('get.id')){
                $info=M('user')->where(array('id'=>I('get.id')))->find();
                if(!$info){exit('非法操作');}
                $this->assign('info',$info);
            }
            $role_list = M('user_roles')->where(array('status'=>array('eq',1)))->order('id asc')->field('id,name')->select();
            $this->assign('data_auth',$this->userInfo['data_auth']);
            $this->assign('role_list',$role_list);
            $this->makeBranchSelect($info['branch_id']);
            $this->display();
        }
    }


    /**
     * 设置角色
     */
    public function set_roles(){
        if (IS_POST) {
            $post=I('post.');
            if(!$post['id']){$this->errorjsonReturn('非法操作');}
            $role_ids=$post['role_id']?implode(',',$post['role_id']):'';
            M('user')->where(array('id'=>$post['id']))->save(array('role_ids'=>$role_ids))!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }else{
            $user_id=I('get.id');
            $role_ids=M('user')->where(array('id'=>$user_id))->getField('role_ids');
            $has_ids=array_filter(array_unique(explode(',',$role_ids)));
            $list = M('user_roles')->where(array('status'=>array('eq',1)))->order('id asc')->field('id,name')->select();
            $this->assign('has_ids',$has_ids);
            $this->assign('list',$list);
            $this->assign('user_id',$user_id);
            $this->display();
        }
    }

    /**
     * 查看一个用户的权限
     */
    public function look_rules(){
        $user_id=I('get.id');
        $role_ids=M('user')->where(array('id'=>$user_id))->getField('role_ids');
        $role_ids=array_filter(array_unique(explode(',',$role_ids)));
        if(!$role_ids){exit('无');}
        $rule_ids=M('user_roles')->where(array('id'=>array('in',$role_ids),'status'=>1))->getField('rules',true);
        $all_rule_ids=array();
        foreach($rule_ids as $v){
            $all_rule_ids=array_merge($all_rule_ids,array_filter(array_unique(explode(',',$v))));
        }
        $all_rule_ids=array_filter(array_unique($all_rule_ids));
        if(!$all_rule_ids){exit('无');}
        $list=M('user_rules')->where(array('id'=>array('in',$all_rule_ids),'status'=>1))->field('title,name')->select();
        $this->assign('list',$list);
        $this->display();
    }

    public function sub_child_table(){
        $get=I('get.');
        $p = $get['p'] ? $get['p'] : 1 ;
        $perpage=7;
        $where=array();
        if($get['user_name']){$where['user_name']=array('like','%'.$get['user_name'].'%');}
        if($get['mobile']){$where['mobile']=array('like','%'.$get['mobile'].'%');}
        $where['delete']=1;

        $userInfo = $this->userInfo;
        if($userInfo['data_auth'] == 1){
            $where['id'][] = array('neq',0);
        }elseif($userInfo['data_auth'] == 2){
            if(!$userInfo['id']){ return false; }
            $myAllSubUserId = (new UserModel())->getMyAllSubUserId($userInfo);
            $myAllSubUserId = array_filter($myAllSubUserId);
            if(!$myAllSubUserId){ return false; }
            $where['id'][] = array('in',$myAllSubUserId);
        }elseif($userInfo['data_auth'] == 3){
            $user_ids = M('user')->where(array('delete'=>1,'branch_id'=>$userInfo['branch_id']))->getField('id',true);
            $user_ids = array_filter($user_ids);
            if(!$user_ids){ return false; }
            $where['id'][] = array('in',$user_ids);
        }else{
            $where['id'][] = array('eq',$userInfo['id']);
        }

        $count = M('user')->where($where)->count();
        $pageNum=floor($count/$perpage);
        if($count%$perpage>0){$pageNum++;}
        $list =  M('user')->where($where)->Page($p,$perpage)->order('id desc')->select();
        $this->assign('list', $list);
        $this->assign('array_sex', array(1=>'男',-1=>'女'));
        $this->assign('page', $p);
        $this->assign('pageNum', $pageNum);
        $this->assign('count', $count);
        $this->display();
    }


    /**
     * 获取一个用户的下属
     */
    public function look_sub_user(){
        $userInfo = M('user')->where(array('id'=>$this->data['id']))->find();
        $sub_user_ids=(new UserModel())->getMyAllSubUserId($userInfo);
        $list=M('user')->where(array('id'=>array('in',$sub_user_ids)))->field('id,user_name')->select();
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 重置用户的密码
     */
    public function initial_password(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择要重置的用户');}
        //查询最初始的密码配置
        $initial_password=(new ConfigModel())->getConfigByNameModel('initial_password');
        $result=M('user')->where(array('id'=>array('in',$ids)))->save(array('pwd'=>md5($initial_password)));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('修改成功');
    }

    /**
     * 系统的默认密码：0659c7992e268962384eb17fafe88364
     */
    public function update_pwd(){
        if(IS_POST){
            $post=I('post.');
            if(!$post['new_password']||($post['new_password']!=$post['re_new_password'])){
                $this->errorjsonReturn('重置密码和确认密码不一致');
            }
            $pwd=M('user')->where(array('id'=>$this->userInfo['id']))->getField('pwd');
            if($pwd!=md5($post['password'])){
                $this->errorjsonReturn('您的旧密码不正确');
            }
            M('user')->where(array('id'=>$this->userInfo['id']))->save(array('pwd'=>md5($post['new_password'])))===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('修改成功');
        }else{
            $this->display();
        }
    }
}

