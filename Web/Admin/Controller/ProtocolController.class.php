<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 合作协议相关控制器
 * Class User
 * @package app\admin\controller
 */
class ProtocolController extends BaseController
{

    /**
     * 已检查 确认
     */
    public function add(){
        if(IS_POST){
            if(!$this->data['order_id']){$this->errorjsonReturn('非法操作');}
            if(!$this->data['money']){$this->errorjsonReturn('请填写需求金额');}
            if(!$this->data['service_money']){$this->errorjsonReturn('请填写服务总佣金');}
            if(!$this->data['service_rate']){$this->errorjsonReturn('请填写服务费率');}
            if(!$this->data['protocol_number']){$this->errorjsonReturn('请填写协议编号');}
            $orderInfo = M('order')->where(array('id'=>$this->data['order_id']))->find();
            if(!$orderInfo){$this->errorjsonReturn('非法操作');}
//            if($orderInfo['user_id']!=$this->userInfo['id']){$this->errorjsonReturn('无权操作');}
            if($orderInfo['status']==3){$this->errorjsonReturn('已放款不能修改');}
            //下面修改订单的贷款金额和状态
            $return1=M('order')->where(array('id'=>$this->data['order_id']))->save(array('money'=>$this->data['money'],'status'=>2,'customer_status'=>4));
            if($return1===false){$this->errorjsonReturn('操作失败');}
            //下面查询这个订单下面存不存在协议,不存在就添加，存在就修改
            if(M('protocol')->where(array('order_id'=>$this->data['order_id']))->count()>0){
                M('protocol')->where(array('order_id'=>$this->data['order_id']))->save(array(
                    'service_money'=>$this->data['service_money'],'service_rate'=>$this->data['service_rate'],'protocol_number'=>$this->data['protocol_number']
                ))===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('修改成功');
            }else{
                M('protocol')->add(array(
                    'create_user_id'=>$this->userInfo['id'],
                    'service_money'=>$this->data['service_money'],
                    'service_rate'=>$this->data['service_rate'],
                    'protocol_number'=>$this->data['protocol_number'],
                    'order_id'=>$this->data['order_id'],
                    'create_time'=>time()
                ))?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
            }
        }else{
            $order_id = $this->data['id'];
            if(!$order_id){exit('非法操作');}
            //下面查询订单及客户的信息
            $orderInfo= M('order')->where(array('id'=>$order_id))->find();
            $this->assign('orderInfo',$orderInfo);
            //如果不是意向交易，那么需要查出他下面的合作协议信息
            if($orderInfo['status']!=1){
                $protocolInfo=M('protocol')->where(array('order_id'=>$order_id))->find();
                $this->assign('protocolInfo',$protocolInfo);
            }
            $this->display();
        }
    }

    /**
     * 通过订单id获取协议的信息  -------已检查
     */
    public function one_info(){
        $order_id=I('get.achievement_id')?M('achievement')->where(array('id'=>I('get.achievement_id')))->getField('order_id'):I('get.order_id');
        $this->assign('info',M('protocol')->where(array('order_id'=>$order_id))->find());
        $this->display();
    }
}

