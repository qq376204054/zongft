<?php
namespace Admin\Controller;
use Admin\Model\AuthModel;
use Admin\Model\OrderModel;
use Admin\Model\UserModel;
use Think\Controller;
/**
 * 用户相关控制器
 * Class User
 * @package app\admin\controller
 */
class OrderController extends BaseController
{

    /**
     * 删除   -----检查完毕
     */
    public function delete()
    {
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        //下面获取这些交易的详情
        $infos= M('order')->where(array('is_delete'=>0,'id'=>array('in',$ids)))->select();
        if(!$infos){$this->setjsonReturn('删除成功');}
        if(implode(',',array_unique(array_column($infos,'status')))!='1'){$this->errorjsonReturn('协议交易或者放款交易不能删除，操作失败');}
        if(implode(',',array_unique(array_column($infos,'user_id')))!=$this->userInfo['id']){$this->errorjsonReturn('您没有权限删除不是你名下的交易，操作失败');}
        $result=M('order')->where(array('id'=>array('in',$ids)))->save(array('is_delete'=>1,'delete_user'=>$this->userInfo['id']));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }

    /**
     * 订单详情管理   -----检查完毕
     */
    public function look(){
        $id=I('get.id');
        $this->assign('id',$id);
        $this->display();
    }

    /**
     * 订单详情   -----检查完毕
     */
    public function order_info(){
        $order_id = I('get.achievement_id')?M('achievement')->where(array('id'=>I('get.achievement_id')))->getField('order_id'):I('get.id');
        $orderInfo= M('order')->where(array('is_delete'=>0,'id'=>$order_id))->find();
        $this->assign('orderInfo',$orderInfo);
        $this->display();
    }

    /**
     * 下面提交结案申请
     */
    public function apply(){
        //下面获取现在正在默认使用的审批流
        $apply_liu=M('setting')->where(array('type'=>'apply','used'=>1))->getField('data');
        if(!$apply_liu){exit('请联系管理员配置审批流');}
        $apply_liu=json_decode($apply_liu,true);
        //下面查询自己的最高角色
        $myRoles=array_filter(array_unique(explode(',',$this->userInfo['role_ids'])));
        $current_step=-1;//设立初始的步骤为-1，就是没有权限的步骤
        foreach($apply_liu as $k=>$v){
            if(in_array($v,$myRoles)){$current_step=$k;}
        }
        if($current_step==-1){exit('您的角色不能提交');}
        if(!$apply_liu){exit('请联系管理员配置审批流');}
        $role_info=M('user_roles')->where(array('id'=>array('in',$apply_liu)))->getField('id,name');
        $apply_all=array();
        foreach($apply_liu as $k=>$value){
            $array=array();
            if($current_step>$k){$array['current']=2;}
            elseif($current_step==$k){$array['current']=1;}
            else{$array['current']=0;}
            $array['name']=$role_info[$value];
            $array['role_id']=$value;
            $apply_all[]=$array;
        }
        $this->assign('current_step',$current_step);
        $this->assign('apply_all',$apply_all);
        $id=I('get.id');
        $this->assign('id',$id);
        $this->display();
    }


}

