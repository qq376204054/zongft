<?php
namespace Admin\Controller;
use Admin\Logic\TreeLogic;
use Admin\Model\ConfigModel;
use Think\Controller;
/**
 * 产品相关控制器
 * Class User
 * @package app\admin\controller
 */
class ProductController extends BaseController
{
    public function index(){
        $get=I('get.');
        $this->assign('search', $get);
        $order='id desc';
        if($get['sort']&&$get['order']){$order=$get['sort'].' '.$get['order'];}
        $where=array();
        $where['is_delete']=0;
        if($get['name']){$where['name']=array('like','%'.$get['name'].'%');}
        $count = M('product')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list =  M('product')->where($where)->Page($this->p,$this->perpage)->order($order)->select();
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
        $result=M('product')->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('成功');
    }
    /**
     * 删除分类
     */
    public function delete(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result=M('product')->where(array('id'=>array('in',$ids)))->save(array('is_delete'=>1));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }

    /**
     * 添加文章
     */
    public function add(){
        if(IS_POST){
            $post=I('post.');
            if(!$post['name']){$this->error('请填写产品名称');}
            if(!$post['scope_start']){$this->error('请填写机构名称');}
            $post['create_time']=strtotime($post['create_time']);
            if(!$post['pic']){$post['pic']='/static/images/nopic.jpg';}
            if($post['id']){
                M('product')->where(array('id'=>$post['id']))->save($post)!==false?$this->success('修改成功',U('product/index')):$this->error('修改失败');
            }else{
                M('product')->add($post)?$this->success('添加成功',U('product/index')):$this->error('添加失败');
            }
        }else{
            if(I('get.id')){
                $this->assign('info',M('product')->where(array('id'=>I('get.id')))->find());
            }
            $this->assign('product_type',(new ConfigModel())->getConfigByNameModel('product_type'));
            $this->display();
        }
    }
}

