<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Think\Controller;

/**
 * 权限管理相关控制器
 * Class User
 * @package app\admin\controller
 */
class RulesController extends BaseController
{
    /**
     * 获取权限列表
     * @return mixed
     */
    public function index(){
        $array_status=array(1=>'启用',2=>'停用',3=>'删除');
        $list = M('user_rules')->where(array('status'=>array('neq',3)))->order('ordid asc,id asc')->select();
        foreach($list as $key=>$value){
            $list[$key]['status_name']=$array_status[$value['status']];
        }
        $this->assign('list', $list);
        $big_menu = array('title' => '添加权限', 'iframe' => U('Rules/add'), 'id' => 'add', 'width' => '500', 'height' => '350',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * ajax修改单个字段值
     */
    public function ajax_edit()
    {
        $get=I('get.');
        if(!$get['id']||!$get['field']){$this->errorjsonReturn('修改失败');}
        //先清除系统菜单的缓存
        (new CacheLogic())->clear_all_system_menu_rules();
        $result=M('user_rules')->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('成功');
    }

    /**
     * 权限添加界面
     */
    public function add(){
        if (IS_POST) {
            $post=I('post.');
            if(!$post['name']){$this->errorjsonReturn('唯一标识');}
            if(!$post['title']){$this->errorjsonReturn('中文名称');}
            $post['name']=mb_strtolower($post['name']);
            //先清除系统的菜单权限缓存
            (new CacheLogic())->clear_all_system_menu_rules();
            M('user_rules')->add($post)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
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
            if(!$post['name']){$this->errorjsonReturn('唯一标识');}
            if(!$post['title']){$this->errorjsonReturn('中文名称');}
            $post['name']=mb_strtolower($post['name']);
            if(M('user_rules')->where(array('id' => $post['id']))->count()==0){$this->errorjsonReturn('修改对象不存在');}
            //先清除系统的菜单权限缓存
            (new CacheLogic())->clear_all_system_menu_rules();
            M('user_rules')->where(array('id' => $post['id']))->save($post)!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }else {
            $id = I('get.id');
            $info = M('user_rules')->where(array('id' => $id))->find();
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
        //先清除系统的菜单权限缓存
        (new CacheLogic())->clear_all_system_menu_rules();
        $result=M('user_rules')->where(array('id'=>array('in',$ids)))->save(array('status'=>3));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }

}

