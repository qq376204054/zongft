<?php
namespace Admin\Controller;
use Admin\Logic\TreeLogic;
use Think\Controller;

/**
 * 导航管理
 * Class Menu
 * @package app\admin\controller
 */
class DaohangController extends BaseController
{
    /**
     * 导航首页
     * @return mixed
     */
    public function index(){
        $type=I('get.type');
        if(!in_array($type,array('pc','wap','weixin'))){exit('非法操作');}
        $target_array=array(1=>'新窗口打开',2=>'本窗口打开',3=>'触发动作');
        $tree = new TreeLogic();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result = M('daohang')->where(array('is_delete'=>0,'type'=>$type))->order('ordid asc')->select();
        $array = array();
        foreach($result as $r) {
            $r['str_manage'] = '<a href="javascript:;" class="J_showdialog" data-uri="'.U('daohang/add',array('pid'=>$r['id'],'type'=>$type)).'" data-title="添加子导航" data-id="add" data-width="500" data-height="350">添加子导航</a> |
                                <a href="javascript:;" class="J_showdialog" data-uri="'.U('daohang/edit',array('id'=>$r['id'])).'" data-title="编辑 - '. $r['name'] .'" data-id="edit" data-width="500" data-height="350">编辑</a> |
                                <a href="javascript:;" class="J_confirmurl" data-acttype="ajax" data-uri="'.U('daohang/delete',array('id'=>$r['id'])).'" data-msg="确认删除 - '.$r['name'].'">删除</a>';
            $r['target_name']=$target_array[$r['target']];
            $array[] = $r;
        }
        $str  = "<tr>
                <td align='center'><input type='checkbox' value='\$id' class='J_checkitem'></td>
                <td align='center'>\$id</td>
                <td>\$spacer<span data-tdtype='edit' data-field='name' data-id='\$id' class='tdedit'>\$name</span></td>
                <td align='center'><span data-tdtype='edit' data-field='url' data-id='\$id' class='tdedit'>\$url</span></td>
                <td align='center'>\$target_name</td>
                <td align='center'><span data-tdtype='edit' data-field='ordid' data-id='\$id' class='tdedit'>\$ordid</span></td>
                <td align='center'>\$str_manage</td>
                </tr>";
        $tree->init($array);
        $menu_list = $tree->get_tree(0, $str);
        $this->assign('menu_list', $menu_list);
        $big_menu = array('title' => '添加导航', 'iframe' => U('daohang/add',array('type'=>$type)), 'id' => 'add', 'width' => '500', 'height' => '350',);
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
        $result=M('daohang')->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('成功');
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result=M('daohang')->where(array('id'=>array('in',$ids)))->save(array('is_delete'=>1));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }
    /**
     * 添加修改界面
     */
    public function add(){
        if (IS_POST) {
            $post=I('post.');
            if(!isset($post['pid'])){$this->errorjsonReturn('请选择上级菜单');}
            if(!in_array($post['type'],array('pc','wap','weixin'))){exit('非法操作');}
            if(!$post['name']){$this->errorjsonReturn('请填写导航名称');}
            if(!$post['url']){$this->errorjsonReturn('请填写链接');}
            M('daohang')->add($post)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
        }else{
            if(!in_array(I('get.type'),array('pc','wap','weixin'))){exit('非法操作');}
            $tree = new TreeLogic();
            $result = M('daohang')->where(array('is_delete' => 0,'type'=>I('get.type')))->order('ordid asc')->select();
            $array = array();
            foreach ($result as $r) {
                $r['selected'] = $r['id'] == $_GET['pid'] ? 'selected' : '';
                $array[] = $r;
            }
            $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
            $tree->init($array);
            $select_menus = $tree->get_tree(0, $str);
            $this->assign('type',I('get.type'));
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
            if(!$post['name']){$this->errorjsonReturn('请填写导航名称');}
            if(!$post['url']){$this->errorjsonReturn('请填写链接');}
            if(M('daohang')->where(array('id' => $post['id']))->count()==0){$this->errorjsonReturn('修改对象不存在');}
            M('daohang')->where(array('id' => $post['id']))->save($post)!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }else {
            $id = I('get.id');
            $info = M('daohang')->where(array('id' => $id))->find();
            $this->assign('info',$info);
            $tree = new TreeLogic();
            $result = M('daohang')->where(array('is_delete' => 0,'type'=>$info['type']))->order('ordid asc')->select();
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