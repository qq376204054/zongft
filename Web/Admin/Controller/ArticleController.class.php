<?php
namespace Admin\Controller;
use Admin\Logic\TreeLogic;
use Think\Controller;
/**
 * 文章相关控制器
 * Class User
 * @package app\admin\controller
 */
class ArticleController extends BaseController
{
    public function index(){
        $get=I('get.');
        $this->assign('all_cate', M('article_cate')->where(array('status'=>1))->getField('id,name,status'));
        $this->assign('search', $get);
        $order='id desc';
        if($get['sort']&&$get['order']){$order=$get['sort'].' '.$get['order'];}
        $str='is_delete=0';
        if($get['time_start']){$str.=' and create_time>'.strtotime($get['time_start']);}
        if($get['time_end']){$str.=' and create_time<'.strtotime($get['time_end']);}
        if($get['cate_id']){$str.=' and cate_id='.$get['cate_id'];}
        if($get['status']){$str.=' and status='.$get['status'];}
        $where['_string']=$str;
        if($get['name']){$where['name']=array('like','%'.$get['name'].'%');}
        $count = M('article')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list =  M('article')->where($where)->Page($this->p,$this->perpage)->order($order)->select();
        $user_ids=array_column($list,'user_id');
        if($user_ids){
            $this->assign('all_user',M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile'));
        }
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
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
        $result=M('article')->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('成功');
    }
    /**
     * 删除分类
     */
    public function delete(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result=M('article')->where(array('id'=>array('in',$ids)))->save(array('is_delete'=>1));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }

    /**
     * 添加文章
     */
    public function add(){
        if(IS_POST){
            $post=I('post.');
            if(!$post['cate_id']){$this->error('请选择分类');}
            if(!$post['name']){$this->error('请填写标题');}
            if(!$post['content']){$this->error('请填写内容');}

            $post['content']=$this->clearhtml($post['content']); //fre 添加 过滤掉文章中的违法的 html
            if(!$post['pic']){$post['pic']='/static/images/nopic.jpg';}
            if($post['id']){
                M('article')->where(array('id'=>$post['id']))->save($post)!==false?$this->success('修改成功',U('article/index')):$this->error('修改失败');
            }else{
                $post['user_id'] = $this->userInfo['id'];
                $post['create_time']=time();
                M('article')->add($post)?$this->success('添加成功',U('article/index')):$this->error('添加失败');
            }
        }else{
            if(I('get.id')){
                $this->assign('info',M('article')->where(array('id'=>I('get.id')))->find());
            }
            $this->assign('all_cate', M('article_cate')->where(array('status'=>1))->getField('id,name,status'));
            $this->display();
        }
    }









    public function cate_index() {
        $tree = new TreeLogic();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result = M('article_cate')->where(array('status'=>array('in',array(1,2))))->order('ordid asc')->select();
        $array = array();
        foreach($result as $r) {
            $r['url']=U('Home/Index/list1',array('id'=>$r['id']));
            $r['url_m']=U('Mobile/Index/article_list',array('id'=>$r['id']));
            $r['str_status'] = '<img data-tdtype="toggle" data-id="'.$r['id'].'" data-field="status" data-value="'.$r['status'].'" src="'.__ROOT__.'/static/images/admin/toggle_' . ($r['status'] == 2 ? 'disabled' : 'enabled') . '.gif" />';
            $r['str_manage'] = '<a href="javascript:;" class="J_showdialog" data-uri="'.U('article/cate_add',array('id'=>$r['id'])).'" data-title="修改 - '.$r['name'].'" data-id="cate_add" data-width="500" data-height="290">修改</a> |
                                <a href="javascript:;" data-acttype="ajax" class="J_confirmurl" data-uri="'.U('article/cate_delete',array('id'=>$r['id'])).'" data-msg="确认要删除 - '.$r['name'].' - 吗？">删除</a>';
            $r['parentid_node'] = ($r['pid'])? ' class="child-of-node-'.$r['pid'].'"' : '';
            $array[] = $r;
        }
        $str  = "<tr id='node-\$id' \$parentid_node>
                <td align='center'><input type='checkbox' value='\$id' class='J_checkitem'></td>
                <td>\$spacer<span data-tdtype='edit' data-field='name' data-id='\$id' class='tdedit'>\$name</span></td>
                <td align='left'><a href='\$url' target='_blank'>\$url</a></td>
                <td align='left'><a href='\$url_m' target='_blank'>\$url_m</a></td>
                <td align='center'>\$id</td>
                <td align='center'><span data-tdtype='edit' data-field='ordid' data-id='\$id' class='tdedit'>\$ordid</span></td>
                <td align='center'>\$str_status</td>
                <td align='center'>\$str_manage</td>
                </tr>";
        $tree->init($array);
        $list = $tree->get_tree(0, $str);
        $this->assign('list', $list);
        $big_menu = array('title' => '添加分类', 'iframe' => U('Article/cate_add'), 'id' => 'cate_add', 'width' => '500', 'height' => '250',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 添加部门
     */
    public function cate_add(){
        if (IS_POST) {
            $post=I('post.');
            if(!$post['name']){$this->errorjsonReturn('请填写分类名称');}
            if(!in_array($post['status'],array(1,2))){$this->errorjsonReturn('请选择状态');}
            if($post['id']){
                M('article_cate')->where(array('id'=>$post['id']))->save($post)!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
            }else{
                M('article_cate')->add($post)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
            }
        }else{
            if(I('get.id')){
                $info=M('article_cate')->where(array('id'=>I('get.id')))->find();
                $this->assign('info',$info);
            }
            $this->display();
        }
    }

    /**
     * 删除分类
     */
    public function cate_delete(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result=M('article_cate')->where(array('id'=>array('in',$ids)))->save(array('status'=>3));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }

    /**
     * ajax修改单个字段值
     */
    public function ajax_cate_edit()
    {
        $get=I('get.');
        if(!$get['id']||!$get['field']){$this->errorjsonReturn('修改失败');}
        $result=M('article_cate')->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('成功');
    }
}

