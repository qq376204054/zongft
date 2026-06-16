<?php
namespace Admin\Controller;
use Admin\Logic\TreeLogic;
use Think\Controller;

/**
 * 客户附件管理
 * Class Menu
 * @package app\admin\controller
 */
class FileController extends BaseController
{
    /**
     * 附件列表  -----检查完毕
     */
    public function index(){
        $get=I('get.');
        $this->assign('search', $get);
        $order='id desc';
        if($get['sort']&&$get['order']){$order=$get['sort'].' '.$get['order'];}
        $where=array();
        $where['status']=array('neq',3);
        if($get['name']){$where['name']=array('like','%'.$get['name'].'%');}
        if($get['cate_id']){$where['cate_id']=$get['cate_id'];}
        if($get['type']){$where['type']=array('like','%'.$get['type'].'%');}
        if($get['time_start']){$where['createtime'][]=array('gt',strtotime($get['time_start']));}
        if($get['time_end']){$where['createtime'][]=array('lt',strtotime($get['time_end'])+86400);}
        $count = M('file')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list =  M('file')->where($where)->Page($this->p,$this->perpage)->order($order)->select();
        //分类名
        $cate_ids=array_filter(array_unique(array_column($list,'cate_id')));
        if($cate_ids){
            $cate_list=M('file_cate')->where(array('id'=>array('in',$cate_ids)))->getField('id,name');
            $this->assign('cate_list',$cate_list);
        }
        //客户信息
        $order_ids=array_filter(array_unique(array_column($list,'order_id')));
        if($order_ids){
            $order_info=M('order')->where(array('id'=>array('in',$order_ids)))->getField('id,name,mobile');
            $this->assign('order_info',$order_info);
        }
        //下面找出创建人的信息
        $user_ids=array_filter(array_unique(array_column($list,'create_user_id')));
        if($user_ids){
            $user_info=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
            $this->assign('user_info',$user_info);
        }
        $this->assign('status_name',array(1=>'正常', 2=>'有错误' ,3=>'删除',4=>'待审核'));
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 作业人员添加附件   -----检查好了
     */
    public function add(){
        if(IS_POST){
            $order_id = I('get.order_id');
            $cate_id=I('get.cate_id');

            if(!$order_id){$this->errorjsonReturn('非法操作');}
            if(!$cate_id){$this->errorjsonReturn('请选择分类');}
            $orderInfo=M('order')->where(array('id'=>$order_id))->field('user_id')->find();
            if($orderInfo['user_id']!=$this->userInfo['id']){$this->errorjsonReturn('您没有权限');}
            //下面上传附件
            $upload = new \Think\Upload();// 实例化上传类
            $upload->exts  = array('pdf','doc','docx','xls','xlsx','txt','rar','zip','jpg','png','jpeg','gif');// 设置附件上传类型
            $upload->rootPath='./Files/'; //保存根路径
            $upload->autoSub = true;
            $upload->subType = 'date';
            $upload->dateFormat = 'Y-m-d';
            $upload->maxSize  = 20*1024*1024 ;// 设置附件上传大小 20M
            $file = $_FILES['imgFile'];
            $add = array();
            $add['create_user_id'] = $this->userInfo['id'];
            $add['original'] = $file['name'];
            $add['type'] = $file['type'];
            $add['size'] = $file['size'];
            $add['createtime'] = time();
            $add['order_id'] = $order_id;
            $add['cate_id'] = $cate_id;
            $info   = $upload->upload();
            $add['savepath']=$info['imgFile']['savepath'];
            $add['name']=$info['imgFile']['savename'];
            if(!$info) {$this->errorjsonReturn('上传失败');}
            if(M('file')->add($add)){
                $this->setjsonReturn(array(
                    'url'=>__ROOT__.'/Uploads/'.$info['imgFile']['savepath'].$info['imgFile']['savename']
                ));
            }else{
                $this->errorjsonReturn('上传失败');
            }
        }else{
            $order_id=I('get.id');
            if(!$order_id){$this->errorjsonReturn('非法操作');}
            $this->assign('order_id',$order_id);
            $tree = new TreeLogic();
            $result = M('file_cate')->where(array('is_delete' => 0))->order('ordid asc')->select();
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
     * 文件列表   -----检查好了
     */
    public function customer_file(){
        if(I('get.achievement_id')){
            $order_id=M('achievement')->where(array('id'=>I('get.achievement_id')))->getField('order_id');
        }elseif(I('get.order_id')){
            $order_id=I('get.order_id');
        }else{exit('非法操作');}
        $list=M('file')->where(array('order_id'=>$order_id,'status'=>array('neq',3)))->order('id desc')->select();
        $cate_ids=array_column($list,'cate_id');
        if($cate_ids){
            $cate_list=M('file_cate')->where(array('id'=>array('in',$cate_ids)))->getField('id,name');
            $this->assign('cate_list',$cate_list);
        }
        $this->assign('list',$list);
        $this->assign('status_name',array(1=>'正常', 2=>'有错误' ,3=>'删除',4=>'待审核'));
        $this->display();
    }

    /**
     * 删除   -----检查好了
     */
    public function delete()
    {
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result=M('file')->where(array('id'=>array('in',$ids)))->save(array('status'=>3));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }

    /**
     * 获取附件分类
     * @return mixed
     */
    public function cate_index(){
        $tree = new TreeLogic();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result = M('file_cate')->where(array('is_delete'=>0))->order('ordid asc')->select();
        $array = array();
        foreach($result as $r) {
            $r['str_manage'] = '<a href="javascript:;" class="J_showdialog" data-uri="'.U('File/cate_add',array('pid'=>$r['id'])).'" data-title="添加子分类" data-id="add" data-width="500" data-height="150">添加子分类</a> |
                                <a href="javascript:;" class="J_showdialog" data-uri="'.U('File/cate_edit',array('id'=>$r['id'])).'" data-title="编辑 - '. $r['name'] .'" data-id="edit" data-width="500" data-height="150">编辑</a> |
                                <a href="javascript:;" class="J_confirmurl" data-acttype="ajax" data-uri="'.U('File/cate_delete',array('id'=>$r['id'])).'" data-msg="确认删除 - '.$r['name'].'">删除</a>';
            $array[] = $r;
        }
        $str  = "<tr>
                <td align='center'><input type='checkbox' value='\$id' class='J_checkitem'></td>
                <td align='center'>\$id</td>
                <td>\$spacer<span data-tdtype='edit' data-field='name' data-id='\$id' class='tdedit'>\$name</span></td>
                <td align='center'><span data-tdtype='edit' data-field='ordid' data-id='\$id' class='tdedit'>\$ordid</span></td>
                <td align='center'>\$str_manage</td>
                </tr>";
        $tree->init($array);
        $menu_list = $tree->get_tree(0, $str);
        $this->assign('menu_list', $menu_list);
        $big_menu = array('title' => '添加一级分类', 'iframe' => U('file/cate_add'), 'id' => 'add', 'width' => '500', 'height' => '150',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * ajax修改单个字段值
     */
    public function cate_ajax_edit()
    {
        $get=I('get.');
        if(!$get['id']||!$get['field']){$this->errorjsonReturn('修改失败');}
        $result=M('file_cate')->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('成功');
    }

    /**
     * 删除
     */
    public function cate_delete()
    {
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result=M('file_cate')->where(array('id'=>array('in',$ids)))->save(array('is_delete'=>1));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }
    /**
     * 添加修改界面
     */
    public function cate_add(){
        if (IS_POST) {
            $post=I('post.');
            if(!isset($post['pid'])){$this->errorjsonReturn('请选择上级分类');}
            if(!$post['name']){$this->errorjsonReturn('请填写分类名称');}
            M('file_cate')->add($post)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
        }else{
            $tree = new TreeLogic();
            $result = M('file_cate')->where(array('is_delete' => 0))->order('ordid asc')->select();
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
    public function cate_edit(){
        if (IS_POST) {
            $post=I('post.');
            if(!$post['id']){$this->errorjsonReturn('系统有误，请联系管理员');}
            if(!isset($post['pid'])){$this->errorjsonReturn('请选择上级分类');}
            if(!$post['name']){$this->errorjsonReturn('请填写分类名称');}
            if(M('file_cate')->where(array('id' => $post['id']))->count()==0){$this->errorjsonReturn('修改对象不存在');}
            M('file_cate')->where(array('id' => $post['id']))->save($post)!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }else {
            $id = I('get.id');
            $info = M('file_cate')->where(array('id' => $id))->find();
            $this->assign('info', $info);
            $tree = new TreeLogic();
            $result = M('file_cate')->where(array('is_delete' => 0))->order('ordid asc')->select();
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

    /**
     * 作业人员添加附件   -----检查好了
     */
    public function useradd(){
        if(IS_POST){
            $cate_id=I('get.cate_id');
            if(!$cate_id){$this->errorjsonReturn('请选择分类');}
            //下面上传附件
            $upload = new \Think\Upload();// 实例化上传类
            $upload->exts  = array('pdf','doc','docx','xls','xlsx','txt','rar','zip','jpg','png','jpeg','gif');// 设置附件上传类型
            $upload->rootPath='./Files/'; //保存根路径
            $upload->autoSub = true;
            $upload->subType = 'date';
            $upload->dateFormat = 'Y-m-d';
            $upload->maxSize  = 20*1024*1024 ;// 设置附件上传大小 20M
            $file = $_FILES['imgFile'];
            $info   = $upload->upload();
            if(!$info) {$this->errorjsonReturn('上传失败');}
            $add = array();
            $add['create_user_id'] = $this->userInfo['id'];
            $add['original'] = $file['name'];
            $add['type'] = $file['type'];
            $add['size'] = $file['size'];
            $add['createtime'] = time();
            $add['user_id'] = $this->userInfo['id'];
            $add['savepath']=$info['imgFile']['savepath'];
            $add['name']=$info['imgFile']['savename'];
            $add['cate_id'] = $cate_id;
            if(M('file_user')->add($add)){
                $this->setjsonReturn(array(
                    'url'=>__ROOT__.'/Uploads/'.$info['imgFile']['savepath'].$info['imgFile']['savename']
                ));
            }else{
                $this->errorjsonReturn('上传失败');
            }
        }else{
            $tree = new TreeLogic();
            $result = M('file_cate')->where(array('is_delete' => 0))->order('ordid asc')->select();
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
     * 文件列表   -----检查好了
     */
    public function user_file(){
        $user_id = $this->data['user_id']?$this->data['user_id']:$this->userInfo['id'];
        $list=M('file_user')->where(array('user_id'=>$user_id,'status'=>array('neq',3)))->order('id desc')->select();
        $cate_ids=array_column($list,'cate_id');
        if($cate_ids){
            $cate_list=M('file_cate')->where(array('id'=>array('in',$cate_ids)))->getField('id,name');
            $this->assign('cate_list',$cate_list);
        }
        $this->assign('list',$list);
        $this->assign('status_name',array(1=>'正常', 2=>'有错误' ,3=>'删除',4=>'待审核'));
        $this->display();
    }
}