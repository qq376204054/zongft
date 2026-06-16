<?php
namespace Admin\Model;
use Admin\Logic\CacheLogic;
use Think\Model;

class AllocateUserModel extends Model
{

    /**
     * 获取最新的分配人
     * @param $city   城市
     * @param $allocate_channel  配置的需要走特殊分配的渠道
     * @param $channel  渠道
     * @return array
     */
    public function getNewestAllocateUser($city,$allocate_channel=array(),$channel='')
    {
        //客户申请城市不存在时不能分配
        if(!$city){
            return array('can'=>false);
        }
        //客户属于特殊渠道时走特殊渠道分配
        if($channel && $allocate_channel && in_array($channel,$allocate_channel)){
            $channel = $channel;
        }else{
            $channel = '';
        }
        //查询当天当前的待分配业务员
        $todayTime = date("Y-m-d",time());
        //is_over 是否已分满客户
        $sql = "SELECT * FROM `yq_allocate_user` WHERE `channel` = '".$channel."' AND `city` = '".$city."' AND `is_delete` = 0 AND ((`is_over` = 0)||((`is_over` = 1)&&(`over_time`<>'".$todayTime."'))) ORDER BY update_time asc LIMIT 1";
        $info = M()->query($sql);
        $info = $info[0];
        if(!$info){
            return array('can'=>false);
        }
        //查询当前业务员当天已分配了多少个
        $start_time = date('Y-m-d 00:00:00',time());
        $end_time = date('Y-m-d 23:59:59',time());
        $where3 = array();
        $where3['a.remark'] = "自动分配";
        $where3['a.create_time'] = array('between',array($start_time,$end_time));
        $where3['a.new_user_id'] = $info['user_id'];
        if($channel){
            $where3['b.channel'] = $channel;
        }else{
            $all_config = (new CacheLogic())->get_all_config();
            if($all_config['allocate_channel']){
                $where3['b.channel'] = array('not in', $all_config['allocate_channel']);
            }
        }
        $allocateNumInfo = M('allocate_log')->alias('a')->where($where3)->join('LEFT JOIN __FIRST_CUSTOMER__ as b ON a.first_customer_id = b.id')->count();
        //判断当天是否已分配完
        if($allocateNumInfo<$info['can_allocate']){
            //当前业务员属于外包合作商时获取API请求地址
            $actionUrl = "";
            if($info['mall_company_id']){
                $actionUrl = M('mall_company')->where(array('id'=>$info['mall_company_id']))->getField('action');
            }
            $info['action'] = $actionUrl;
            return array('can'=>true,'info'=>$info);
        }else{
            $save = array();
            $save['is_over'] = 1;
            $save['over_time'] = $todayTime;
            M('allocate_user')->where(array('id'=>$info['id']))->save($save);
            return array('can'=>false,'info'=>$info);
        }
    }
}