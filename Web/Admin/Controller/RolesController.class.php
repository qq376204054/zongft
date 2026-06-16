<?php
namespace Admin\Controller;
use Admin\Logic\RolesLogic;
use Admin\Model\UserModel;
use Admin\Model\UserRolesModel;
use Think\Controller;

/**
 * 用户相关控制器
 * Class User
 * @package app\admin\controller
 */
class RolesController extends BaseController
{
    /**
     * 获取角色列表
     * @return mixed
     */
    public function index(){
        $array_status=array(1=>'启用',2=>'停用',3=>'删除');

        $array_look_user_phone=array(1=>'能看到后台员工手机号',2=>'员工的手机号中间加密');
        $array_look_customer_phone=array(1=>'只要展示出来的客户都显示出手机号',2=>'只展示自己负责的客户的手机号',3=>'隐藏所有客户的手机号');
        $array_data_auth=array(1=>'能看到整个平台的数据',2=>'能看到自己以及下属部门的数据',3=>'只能看到自己当前部门的数据',4=>'只能看到自己名下的数据');

        $list = M('user_roles')->where(array('status'=>array('neq',3)))->order('id asc')->select();
        foreach($list as $key=>$value){
            $list[$key]['status_name']=$array_status[$value['status']];

            $list[$key]['look_user_phone_name']=$array_look_user_phone[$value['look_user_phone']];
            $list[$key]['look_customer_phone_name']=$array_look_customer_phone[$value['look_customer_phone']];
            $list[$key]['data_auth_name']=$array_data_auth[$value['data_auth']];
        }
        $this->assign('list', $list);
        $big_menu = array('title' => '添加角色', 'iframe' => U('Roles/add'), 'id' => 'add', 'width' => '500', 'height' => '150',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }
    /**
     * 角色添加界面
     */
    public function add(){
        if (IS_POST) {
            $post=I('post.');
            if(!in_array($post['look_user_phone'],array(1,2))){$this->errorjsonReturn('请选择能不能看员工的手机号');}
            if(!in_array($post['look_customer_phone'],array(1,2,3))){$this->errorjsonReturn('请选择能不能看客户的手机号');}
            if(!in_array($post['data_auth'],array(1,2,3,4))){$this->errorjsonReturn('请选择数据权限');}

            if(!$post['name']){$this->errorjsonReturn('角色名');}
            M('user_roles')->add($post)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
        }else{
            $this->display();
        }
    }

    /**
     * 添加修改界面
     */
    public function edit(){
        if (IS_POST) {
            $post=I('post.');
            if(!$post['id']){$this->errorjsonReturn('系统有误，请联系管理员');}
            if(!$post['name']){$this->errorjsonReturn('角色名');}
            if(!in_array($post['look_user_phone'],array(1,2))){$this->errorjsonReturn('请选择能不能看员工的手机号');}
            if(!in_array($post['look_customer_phone'],array(1,2,3))){$this->errorjsonReturn('请选择能不能看客户的手机号');}
            if(!in_array($post['data_auth'],array(1,2,3,4))){$this->errorjsonReturn('请选择数据权限');}

            if(M('user_roles')->where(array('id' => $post['id']))->count()==0){$this->errorjsonReturn('修改对象不存在');}
            M('user_roles')->where(array('id' => $post['id']))->save($post)!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }else {
            $id = I('get.id');
            $info = M('user_roles')->where(array('id' => $id))->find();
            $this->assign('info', $info);
            $this->display();
        }
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result=M('user_roles')->where(array('id'=>array('in',$ids)))->save(array('status'=>3));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }

    /**
     * 设置角色的权限
     */
    public function set_rules(){
        if (IS_POST) {
            $post=I('post.');
            if(!$post['id']){$this->errorjsonReturn('非法操作');}
            $rule_ids=$post['rule_id']?implode(',',$post['rule_id']):'';
            M('user_roles')->where(array('id'=>$post['id']))->save(array('rules'=>$rule_ids))!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }else{
            $role_id=I('get.id');
            $rules=M('user_roles')->where(array('id'=>$role_id))->getField('rules');
            $rules=array_filter(array_unique(explode(',',$rules)));
            $list = M('user_rules')->where(array('status'=>array('eq',1)))->order('ordid asc,id asc')->field('id,name,title')->select();
            $this->assign('has_rules',$rules);
            $this->assign('list',$list);
            $this->assign('role_id',$role_id);
            $this->display();
        }
    }

    /**
     * 选择一个角色进行操作
     */
    public function select_one(){
        $this->assign('action',I('get.action'));
        $list = M('user_roles')->where(array('status'=>array('eq',1)))->order('id asc')->field('id,name')->select();
        $this->assign('list',$list);
        $this->display();
    }
}

