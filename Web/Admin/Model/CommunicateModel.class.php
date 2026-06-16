<?php
namespace Admin\Model;
use Think\Model;

class CommunicateModel extends Model
{
    /**
     * 获取每个业务员的沟通客户数
     * @param $user_ids
     * @param $start_time
     * @param $end_time
     * @return array
     */
    public function getCommunicateCustomerNum($user_ids,$start_time,$end_time){
        $return=array();
        if(!$user_ids){return $return;}
        $where['user_id']=array('in',$user_ids);
        $where['create_time']=array('between',array($start_time,$end_time));
        return $this->where($where)->group('user_id')->getField('user_id,COUNT(DISTINCT order_id) AS num',true);
    }

    /**
     * 获取每个业务员的沟通次数
     * @param $user_ids
     * @param $start_time
     * @param $end_time
     * @return array
     */
    public function getCommunicateNum($user_ids,$start_time,$end_time){
        $return=array();
        if(!$user_ids){return $return;}
        $where['user_id']=array('in',$user_ids);
        $where['create_time']=array('between',array($start_time,$end_time));
        return $this->where($where)->group('user_id')->getField('user_id,COUNT(*) AS num',true);
    }

}