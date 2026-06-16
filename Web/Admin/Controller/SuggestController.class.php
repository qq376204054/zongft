<?php
namespace Admin\Controller;
use Admin\Logic\TreeLogic;
use Think\Controller;
/**
 * 投诉意见控制器
 * Class User
 * @package app\admin\controller
 */
class SuggestController extends BaseController
{
    public function index(){
        $get=I('get.');
        $this->assign('search', $get);
        $order='id desc';
        if($get['sort']&&$get['order']){$order=$get['sort'].' '.$get['order'];}
        if($get['time_start']){$where['create_time'][]=array('gt',strtotime($get['time_start']));}
        if($get['time_end']){$time_end=strtotime($get['time_end'])+86400; $where['create_time'][]=array('lt',$time_end);}
        if($get['type']){$where['type']=$get['type'];}
        if(isset($get['status'])&&($get['status']!='')){$where['status']=$get['status'];}
        $count = M('suggest')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list =  M('suggest')->where($where)->Page($this->p,$this->perpage)->order($order)->select();
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 查看并且处理问题
     */
    public function edit(){
        if(IS_POST){
            $post=I('post.');
            foreach($post as $k=>$v){$post[$k]=trim($v);}
            $return=M('suggest')->where(array('id'=>$post['id'],'status'=>0))->save(array('status'=>1,'answer'=>$post['answer']));
            $return===false?$this->errorjsonReturn('提交失败'):$this->setjsonReturn('提交成功');
        }else{
            $id=I('get.id');
            if(!$id){exit('非法操作');}
            $this->assign('info',M('suggest')->where(array('id'=>$id))->find());
            $this->display();
        }
    }
}

