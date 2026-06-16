<?php
namespace Admin\Model;
use Think\Model;

class AllocateLogModel extends Model
{

    /**
     * 添加转移记录
     * @param $customer_infos  要移动的客户信息组
     * @param $user_id  移动给谁
     * @return bool
     */
    public function addMoveCustomerLog($customer_infos,$user_id){
        if(!$customer_infos||!$user_id){return true;}
        $addData=array();
        foreach($customer_infos as $value){
            $addData[]=array(
                'first_customer_id'=>$value['first_customer_id'],
                'order_id'=>$value['id'],
                'old_user_id'=>$value['user_id'],
                'new_user_id'=>$user_id,
                'remark'=>'转移客户'
            );
        }
        $this->addAll($addData);
        return true;
    }

    /**
     * 批量获取业务员的相应分配数（得到）
     * @param $user_ids  相关的负责人
     * @param $start_time  开始时间
     * @param $end_time  结束时间
     * @return array
     */
    public function getAllocateByRemarkIn($user_ids,$remarks,$start_time,$end_time){
        //获取这些业务员的 自动分配和未沟通分配
        if(!$user_ids){return array();}
        $sql="SELECT new_user_id,COUNT(*) as num,remark FROM ".C('DB_PREFIX')."allocate_log
              WHERE remark IN ("."'".implode("','",$remarks)."'".") AND new_user_id IN ("."'".implode("','",$user_ids)."'".")
              AND create_time BETWEEN '".$start_time."' AND '".$end_time."' GROUP BY new_user_id,remark";
        $sqlReturn=M()->query($sql);
        $return=array();
        foreach($sqlReturn as $value){
            $return[$value['new_user_id']][$value['remark']]=$value['num'];
        }
        return $return;
    }

    /**
     * 批量获取业务员的相应分配数（流失）
     * @param $user_ids  相关的负责人
     * @param $start_time  开始时间
     * @param $end_time  结束时间
     * @return array
     */
    public function getAllocateByRemarkOut($user_ids,$remarks,$start_time,$end_time){
        //获取这些业务员的 自动分配和未沟通分配
        if(!$user_ids){return array();}
        $sql="SELECT old_user_id,COUNT(*) as num,remark FROM ".C('DB_PREFIX')."allocate_log
              WHERE remark IN ("."'".implode("','",$remarks)."'".") AND old_user_id IN ("."'".implode("','",$user_ids)."'".")
              AND create_time BETWEEN '".$start_time."' AND '".$end_time."' GROUP BY old_user_id,remark";
        $sqlReturn=M()->query($sql);
        $return=array();
        foreach($sqlReturn as $value){
            $return[$value['old_user_id']][$value['remark']]=$value['num'];
        }
        return $return;
    }
}