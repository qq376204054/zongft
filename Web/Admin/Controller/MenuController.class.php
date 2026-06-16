<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Admin\Logic\TreeLogic;
use Think\Controller;

/**
 * 后台导航管理
 * Class Menu
 * @package app\admin\controller
 */
class MenuController extends BaseController
{
    /**
     * 获取后台导航首页
     * @return mixed
     */
    public function index(){
        $tree = new TreeLogic();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result = M('menu')->where(array('is_delete'=>0))->order('ordid asc')->select();
        $array = array();
        foreach($result as $r) {
            $r['str_manage'] = '<a href="javascript:;" class="J_showdialog" data-uri="'.U('menu/add',array('pid'=>$r['id'])).'" data-title="添加子菜单" data-id="add" data-width="500" data-height="350">添加子菜单</a> |
                                <a href="javascript:;" class="J_showdialog" data-uri="'.U('menu/edit',array('id'=>$r['id'])).'" data-title="编辑 - '. $r['name'] .'" data-id="edit" data-width="500" data-height="350">编辑</a> |
                                <a href="javascript:;" class="J_confirmurl" data-callback="dd(\'#tr_id_'.$r['id'].'\')" data-acttype="ajax" data-uri="'.U('menu/delete',array('id'=>$r['id'])).'" data-msg="确认删除 - '.$r['name'].'">删除</a>';
            $array[] = $r;
        }
        $str  = "<tr id='tr_id_\$id'>
                <td align='center'><input type='checkbox' value='\$id' class='J_checkitem'></td>
                <td align='center'>\$id</td>
                <td align='center'><i class='fa fa-\$icon'></i></td>
                <td>\$spacer<span data-tdtype='edit' data-field='name' data-id='\$id' class='tdedit'>\$name</span></td>
                <td align='center'>\$remark</td>
                <td align='center'><span data-tdtype='edit' data-field='ordid' data-id='\$id' class='tdedit'>\$ordid</span></td>
                <td align='center'>\$str_manage</td>
                </tr>";
        $tree->init($array);
        $menu_list = $tree->get_tree(0, $str);
        $this->assign('menu_list', $menu_list);
        $big_menu = array('title' => '添加菜单', 'iframe' => U('menu/add'), 'id' => 'add', 'width' => '500', 'height' => '350',);
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
        (new CacheLogic())->clear_all_system_menu();
        $result=M('menu')->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('成功');
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        //先清除系统菜单的缓存
        (new CacheLogic())->clear_all_system_menu();
        $result=M('menu')->where(array('id'=>array('in',$ids)))->save(array('is_delete'=>1));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }
    /**
     * 添加修改界面
     */
    public function add(){
        if (IS_POST) {
            $post=I('post.');
            if(!isset($post['pid'])){$this->errorjsonReturn('请选择上级菜单');}
            if(!$post['name']){$this->errorjsonReturn('请填写菜单名称');}
            if(!$post['module_name']){$this->errorjsonReturn('模块名');}
            if(!$post['action_name']){$this->errorjsonReturn('方法名');}
            //先清除系统菜单的缓存
            (new CacheLogic())->clear_all_system_menu();
            M('menu')->add($post)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
        }else{
            $tree = new TreeLogic();
            $result = M('menu')->where(array('is_delete' => 0))->order('ordid asc')->select();
            $array = array();
            foreach ($result as $r) {
                $r['selected'] = $r['id'] == $_GET['pid'] ? 'selected' : '';
                $array[] = $r;
            }
            $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
            $tree->init($array);
            $select_menus = $tree->get_tree(0, $str);
            $this->assign('select_menus', $select_menus);
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
            if(!isset($post['pid'])){$this->errorjsonReturn('请选择上级菜单');}
            if(!$post['name']){$this->errorjsonReturn('请填写菜单名称');}
            if(!$post['module_name']){$this->errorjsonReturn('模块名');}
            if(!$post['action_name']){$this->errorjsonReturn('方法名');}
            if(M('menu')->where(array('id' => $post['id']))->count()==0){$this->errorjsonReturn('修改对象不存在');}
            //先清除系统菜单的缓存
            (new CacheLogic())->clear_all_system_menu();
            M('menu')->where(array('id' => $post['id']))->save($post)!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }else {
            $id = I('get.id');
            $info = M('menu')->where(array('id' => $id))->find();
            $this->assign('info', $info);
            $tree = new TreeLogic();
            $result = M('menu')->where(array('is_delete' => 0))->order('ordid asc')->select();
            $array = array();
            foreach ($result as $r) {
                $r['selected'] = $r['id'] == $info['pid'] ? 'selected' : '';
                $array[] = $r;
            }
            $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
            $tree->init($array);
            $select_menus = $tree->get_tree(0, $str);
            $this->assign('select_menus', $select_menus);
            $this->display();
        }
    }
}