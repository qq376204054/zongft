<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 面见相关控制器
 * Class User
 * @package app\admin\controller
 */
class MeetingController extends BaseController
{

    /**
     * 已检查 确认
     */
    public function add(){
        if(IS_POST){
            if(!$this->data['order_id']){$this->errorjsonReturn('非法操作');}
            if(!$this->data['last_lianxi_time']){$this->errorjsonReturn('请选择面见时间');}
            if(!$this->data['last_lianxi_content']){$this->errorjsonReturn('请填写面见内容');}
            $add = array();
            $add['order_id'] = $this->data['order_id'];
            $add['user_id'] = $this->userInfo['id'];
            $add['meet_time'] = strtotime($this->data['last_lianxi_time']);
            $add['content'] = $this->data['last_lianxi_content'];
            $add['create_time'] = time();
            //更改客户的状态
            M('order')->where(array('id'=>$this->data['order_id']))->save(array('customer_status'=>3));
            $return = M('meeting')->add($add);
            $return?$this->setjsonReturn('面见成功'):$this->errorjsonReturn('面见失败');
        }else{
            $order_id = $this->data['id'];
            if(!$order_id){exit('非法操作');}
            //下面查询订单及客户的信息
            $orderInfo= M('order')->where(array('id'=>$order_id))->find();
            $this->assign('orderInfo',$orderInfo);
            $this->display();
        }
    }
}

