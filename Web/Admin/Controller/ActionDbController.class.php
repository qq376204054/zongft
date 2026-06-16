<?php
namespace Admin\Controller;
use Admin\Model\OrderModel;
use Admin\Model\UserModel;
use Think\Controller;
/**
 * 处理数据库的脚本
 * Class User
 * @package app\admin\controller
 */
class ActionDbController extends BaseController
{

    /***********************************
     * 8月17号的任务*********************
     ***********************************/
    //删除没有客户主体的订单
    public function delNoCustomerOrder(){
        $sql = "SELECT o.id from yq_order o LEFT JOIN yq_customer cu ON cu.id = o.customer_id WHERE cu.id is null";
        $return=M()->query($sql);
        if($return){
            $ids = array_column($return,'id');
            $result = M('order')->where(array('id'=>array('in',$ids)))->delete();
        }
        var_dump($result);
    }
    //初始化订单的数据
    public function setOrderInfo(){
        set_time_limit(0);
        $action = 1;
        while($action){
            $userList = M('order')->where(array('mobile'=>''))->limit(100)->getField('id,customer_id',true);
            if($userList){
                foreach($userList as $key=>$value){
                    $customer_info = M('customer')->where(array('id'=>$value))->find();
                    $save = array();
                    $save['name'] = $customer_info['name'];
                    $save['sex'] = $customer_info['sex'];
                    $save['mobile'] = $customer_info['mobile'];
                    $save['city'] = $customer_info['city'];
                    $save['address'] = $customer_info['address'];
                    $save['user_id'] = $customer_info['user_id'];
                    $save['company_id'] = $customer_info['company_id'];
                    $save['has_house'] = $customer_info['has_house'];
                    $save['house_address'] = $customer_info['house_address'];
                    $save['follow_type'] = $customer_info['follow_type'];
                    $save['communicate_time'] = $customer_info['communicate_time'];
                    $save['ext'] = $customer_info['ext'];
                    $save['change_user_time'] = $customer_info['change_user_time'];
                    $save['level'] = $customer_info['level'];
                    $save['is_shield'] = $customer_info['is_shield'];
                    M('order')->where(array('id'=>$key))->save($save);
                }
            }else{
                $action = 0;
            }
        }
        var_dump("初始化订单数据成功");
    }

    /**
     * 删除重复的老订单
     */
    public function delOldOrder(){
        set_time_limit(0);
        $sql = "SELECT MAX(id) as id,customer_id,COUNT(*) as count FROM yq_order WHERE is_delete=0 GROUP BY customer_id HAVING count>1";
        $list=M()->query($sql);
        foreach($list as $key=>$value){
            M('order')->where(array('customer_id'=>$value['customer_id'],'id'=>array('neq',$value['id'])))->save(array('is_delete'=>1));
        }
        var_dump("初始化订单数据成功");
    }

    /**
     * 需要对比哪个客户没有订单，先帮他们生成一条订单
     */
    public function createOrderForCustomer(){
        set_time_limit(0);
        $sql = "SELECT * from yq_customer cu LEFT JOIN (SELECT DISTINCT customer_id FROM yq_order where is_delete=0) o ON cu.id = o.customer_id WHERE o.customer_id is null;";
        $list=M()->query($sql);
        foreach($list as $key=>$value){
            $add= array();
            $add['customer_id'] = $value['id'];
            $add['number'] = (new OrderModel())->creatOrderNumber();
            $add['money'] = $value['want_money'];
            $add['create_user_id'] = $value['user_id'];
            $add['channel'] = $value['channel'];
            $add['create_time'] = $value['create_time'];
            $add['create_type'] = 1;
            $add['status'] = 1;
            $add['name'] = $value['name'];
            $add['sex'] = $value['sex'];
            $add['mobile'] = $value['mobile'];
            $add['city'] = $value['city'];
            $add['address'] = $value['address'];
            $add['user_id'] = $value['user_id'];
            $add['company_id'] = $value['company_id'];
            $add['has_house'] = $value['has_house'];
            $add['house_address'] = $value['house_address'];
            $add['follow_type'] = $value['follow_type'];
            $add['communicate_time'] = $value['communicate_time'];
            $add['ext'] = $value['ext'];
            $add['change_user_time'] = $value['change_user_time'];
            $add['level'] = $value['level'];
            $add['is_shield'] = $value['is_shield'];
            M('order')->add($add);
            sleep(1);
        }
        var_dump("帮无订单客户创建订单成功");
    }

    /**
     * 帮沟通日志绑定订单
     */
    public function setOrderForCommi(){
        set_time_limit(0);
        $action = 1;
        while($action){
            $communicateList = M('communicate')->where(array('order_id'=>0))->limit(100)->getField('id,customer_id',true);
            if($communicateList){
                foreach($communicateList as $key=>$value){
                    $orderInfo = M('order')->where(array('customer_id'=>$value,'is_delete'=>0))->find();
                    $order_id = $orderInfo?$orderInfo['id']:999999;
                    M('communicate')->where(array('id'=>$key))->save(array('order_id'=>$order_id));
                }
            }else{
                $action = 0;
            }
        }
        var_dump("帮沟通记录修复订单成功");
    }

    /**
     * 帮分配日志绑定订单
     */
    public function setOrderForAllocateLog(){
        set_time_limit(0);
        $action = 1;
        while($action){
            $communicateList = M('allocate_log')->where(array('order_id'=>0))->limit(100)->getField('id,customer_id',true);
            if($communicateList){
                foreach($communicateList as $key=>$value){
                    $orderInfo = M('order')->where(array('customer_id'=>$value,'is_delete'=>0))->find();
                    $order_id = $orderInfo?$orderInfo['id']:999999;
                    M('allocate_log')->where(array('id'=>$key))->save(array('order_id'=>$order_id));
                }
            }else{
                $action = 0;
            }
        }
        var_dump("帮分配日志记录修复订单成功");
    }

    /**
     * 帮文件绑定订单
     */
    public function setOrderForFile(){
        set_time_limit(0);
        $action = 1;
        while($action){
            $communicateList = M('file')->where(array('order_id'=>0))->limit(100)->getField('id,customer_id',true);
            if($communicateList){
                foreach($communicateList as $key=>$value){
                    $orderInfo = M('order')->where(array('customer_id'=>$value,'is_delete'=>0))->find();
                    $order_id = $orderInfo?$orderInfo['id']:999999;
                    M('file')->where(array('id'=>$key))->save(array('order_id'=>$order_id));
                }
            }else{
                $action = 0;
            }
        }
        var_dump("帮文件记录修复订单成功");
    }

    /**
     * 帮文件绑定订单
     */
    public function setOrderForMallSaleLog(){
        set_time_limit(0);
        $action = 1;
        while($action){
            $communicateList = M('mall_sale_log')->where(array('order_id'=>0))->limit(100)->getField('id,customer_id',true);
            if($communicateList){
                foreach($communicateList as $key=>$value){
                    $orderInfo = M('order')->where(array('customer_id'=>$value,'is_delete'=>0))->find();
                    $order_id = $orderInfo?$orderInfo['id']:999999;
                    M('mall_sale_log')->where(array('id'=>$key))->save(array('order_id'=>$order_id));
                }
            }else{
                $action = 0;
            }
        }
        var_dump("帮买卖记录修复订单成功");
    }


    /**
     * 初始化 yq_mall_sale_log 表中的 first_customer_id
     */
    public function setFirstCustomerForMallSaleLog(){
        set_time_limit(0);
        $action = 1;
        while($action){
            $MallSaleLogList = M('mall_sale_log')->where(array('first_customer_id'=>0))->limit(100)->getField('id,order_id',true);
            if($MallSaleLogList){
                foreach($MallSaleLogList as $key=>$value){
                    $orderInfo = M('order')->where(array('id'=>$value))->find();
                    $first_customer_id = $orderInfo['first_customer_id']?$orderInfo['first_customer_id']:999999;
                    M('mall_sale_log')->where(array('id'=>$key))->save(array('first_customer_id'=>$first_customer_id));
                }
            }else{
                $action = 0;
            }
        }
        var_dump("帮yq_mall_sale_log修复first_customer_id成功");
    }

    /**
     * 初始化 yq_allocate_log 表中的 first_customer_id
     */
    public function setFirstCustomerForAllocateLog(){
        set_time_limit(0);
        $action = 1;
        while($action){
            $MallSaleLogList = M('allocate_log')->where(array('first_customer_id'=>0))->limit(100)->getField('id,order_id',true);
            if($MallSaleLogList){
                foreach($MallSaleLogList as $key=>$value){
                    $orderInfo = M('order')->where(array('id'=>$value))->find();
                    $first_customer_id = $orderInfo['first_customer_id']?$orderInfo['first_customer_id']:999999;
                    M('allocate_log')->where(array('id'=>$key))->save(array('first_customer_id'=>$first_customer_id));
                }
            }else{
                $action = 0;
            }
        }
        var_dump("帮yq_allocate_log修复first_customer_id成功");
    }

    /**
     * 设置用户的部门
     */
    public function setUserBranch(){
        set_time_limit(0);
        $action = 1;
        $id = 0;
        while($action){
            $userInfo = (new UserModel())->where(array('id'=>array('gt',$id)))->order('id asc')->find();
            if($userInfo){
                $id = $userInfo['id'];
                $branchInfo = M('user_branch')->where(array('user_id'=>$userInfo['id'],'status'=>2))->find();
                if($branchInfo){
                    $save = array();
                    $save['company_id'] = $branchInfo['company_id'];
                    $save['is_admin'] = $branchInfo['is_admin'];
                    $return = M('user')->where(array('id'=>$userInfo['id']))->save($save);
                }
            }else{
                $action = 0;
            }
        }
        var_dump("帮yq_user修复部门成功");
    }

    /**
     * 修复20191218的误操作
     */
    public function xiufu_allcote(){
        $where = array();
        $where['create_time'][] = array('gt','2019-12-17 9:00:00');
        $where['create_time'][] = array('lt','2019-12-17 14:00:00');
        $where['new_user_id'][] = array('eq',0);
        $orderAllocates = M('allocate_log')->where($where)->select();
        foreach($orderAllocates as $key=>$value){
            //先转移客户
            M('order')->where(array('id'=>$value['order_id']))->save(array('user_id'=>$value['old_user_id'],'change_user_time'=>time()));
            $one = array(
                'order_id'=>$value['order_id'],
                'old_user_id'=>0,
                'new_user_id'=>$value['old_user_id'],
                'remark'=>'误丢入公盘数据修复'
            );
            $id = M('allocate_log')->add($one);
            var_dump($id);
        }
    }
}

//SELECT * from yq_allocate_log WHERE create_time>'2019-12-17 10:00:00' and create_time<'2019-12-17 14:00:00' AND new_user_id=0

