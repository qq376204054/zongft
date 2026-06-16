<?php
namespace Admin\Controller;
use Admin\Model\ConfigModel;
use Admin\Model\LoanModel;
use Think\Controller;
/**
 * 放款相关控制器
 * Class User
 * @package app\admin\controller
 */
class LoanController extends BaseController
{
    /**
     * 已检查确认
     */
    public function add(){
        if(IS_POST){
            if(!$this->data['order_id']){$this->errorjsonReturn('非法操作');}
            if(!$this->data['money']){$this->errorjsonReturn('请填写放款金额');}
            if(!$this->data['lend_time']){$this->errorjsonReturn('请填写放款时间');}
            if(!$this->data['month_rate']){$this->errorjsonReturn('请填写月利率');}
            if(!$this->data['expiry_time']){$this->errorjsonReturn('请填写到期时间');}
            if(!$this->data['product_type']){$this->errorjsonReturn('请选择产品类型');}
            if(!$this->data['payment_method']){$this->errorjsonReturn('请选择还款方式');}
            if(!$this->data['product_name']){$this->errorjsonReturn('请填写产品名称');}
            $orderInfo=  M('order')->where(array('id'=>$this->data['order_id']))->find();
            if(!$orderInfo){$this->errorjsonReturn('非法操作');}
//            if($orderInfo['user_id']!=$this->userInfo['id']){$this->errorjsonReturn('无权操作');}
            if($orderInfo['status']==1){$this->errorjsonReturn('请先操作协议');}
            //下面修改订单的贷款金额和状态
            if($orderInfo['status']!=3){
                $return1=M('order')->where(array('id'=>$this->data['order_id']))->save(array('status'=>3));
                if($return1===false){$this->errorjsonReturn('操作失败');}
            }
            $this->data['lend_time']=strtotime($this->data['lend_time']);
            $this->data['expiry_time']=strtotime($this->data['expiry_time']);
            $this->data['create_user_id']=$this->userInfo['id'];
            $this->data['create_time']=time();
            //下面添加放款信息
            M('loan')->add($this->data)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
        }else{
            $order_id=I('get.id');
            if(!$order_id){exit('非法操作');}
            //下面查询订单及客户的信息
            $orderInfo = M('order')->where(array('id'=>$order_id))->find();
            if($orderInfo['status']==1){exit('请先操作协议');}
            $product_type=(new ConfigModel())->getConfigByNameModel('product_type');
            $payment_method=(new ConfigModel())->getConfigByNameModel('payment_method');
            $this->assign('orderInfo',$orderInfo);
            $this->assign('product_type',$product_type);
            $this->assign('payment_method',$payment_method);
            $this->assign('order_id',$order_id);
            $this->display();
        }
    }

    /**
     * 获取订单或者业绩的贷款列表   ----检查完毕
     */
    public function one_list(){
        if(I('get.achievement_id')){
            $achievement_info=M('achievement')->where(array('id'=>I('get.achievement_id')))->getField('loan');
            $list=json_decode($achievement_info,true);
        }else{
            $list=(new LoanModel())->getListByOrderModel(I('get.order_id'));
        }
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 删除贷款信息   ----检查完毕
     */
    public function delete(){
        $return=(new LoanModel())->deleteModel(I('get.id'));
        $return===true?$this->setjsonReturn('删除成功'):$this->errorjsonReturn($return);
    }

}

