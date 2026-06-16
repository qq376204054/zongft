<?php
namespace Admin\Logic;
use Admin\Model\AllocateUserModel;
use Admin\Model\ConfigModel;
use Admin\Model\CustomerModel;
use Admin\Model\OrderModel;
use Message\Logic\MessageLogic;

class CustomerLogic
{
    /**
     * 自动分配推送商机
     */
    public function pushBusinessToCustomer(){
        //参与分配城市
        $allocateConfig=(new CacheLogic())->get_all_config();
        $allocate_city = $allocateConfig['allocate_city'];
        //暂停分配的城市
        $cannot_allocate_city = (new ConfigModel())->getConfigByNameModel('cannot_allocate_city');
        foreach($cannot_allocate_city as $key=>$value){
            if(!in_array($value, $allocate_city)){
                unset($cannot_allocate_city[$key]);
            }
        }
        //每次自动推送未处理商机数量
        if(!$allocateConfig['allocate_crontab_num']){
            $allocateConfig['allocate_crontab_num'] = 20;
        }
        //每次自动推送处理失败商机数量
        if(!$allocateConfig['allocate_crontab_num_old']){
            $allocateConfig['allocate_crontab_num_old'] = 20;
        }
        //组装未处理和处理失败客户数据查询语句
        $where1 = array();
        $where1['status'] = 0;//未处理的数据
        $where2 = array();
        $where2['status'] = 2;//处理失败的数据
        if($cannot_allocate_city){
            $str = "";
            foreach($cannot_allocate_city as $value){
                if($str!=""){$str = $str." AND ";}
                $str = $str. " (city NOT LIKE '%".$value."%')";
            }
            $where1['_string'] = $str;
            $where2['_string'] = $str;
        }
        //获取未处理客户数据
        $firstCustomerInfo = M('first_customer')->where($where1)->limit($allocateConfig['allocate_crontab_num'])->order('id asc')->select();
        //获取处理失败客户数据
        $firstCustomerInfoOld = M('first_customer')->where($where2)->limit($allocateConfig['allocate_crontab_num_old'])->order('id asc')->select();
        if($firstCustomerInfoOld){
            foreach($firstCustomerInfoOld as $key=>$value){
                $firstCustomerInfo[] = $value;
            }
        }
        if(empty($firstCustomerInfo)){
            return array('status'=>false, 'msg'=>'暂无待推送的客户数据');
        }
        //下面对每个商机进行分配处理
        foreach($firstCustomerInfo as $oneOrder){
            //单个商机推送处理
            $this->pushOneBusinessToCustomer($oneOrder, $allocateConfig);
            sleep(1);//睡眠1秒
        }
        return array('status'=>true, 'msg'=>'本次推送商机任务完成');
    }

    /**
     * 推送一个商机给业务员
     */
    public function pushOneBusinessToCustomer($oneOrder, $allocateConfig){
        $result = $this->allocateLogic($oneOrder, $allocateConfig);
        if($result === false){
            //处理失败
            M('first_customer')->where(array('id'=>$oneOrder['id']))->save(array('status'=>2));
        } else {
            //处理成功
            M('first_customer')->where(array('id'=>$oneOrder['id']))->save(array('status'=>1));
        }
    }

    /**
     * 开始推送
     */
    public function allocateLogic($oneOrder, $allocateConfig)
    {
        //先判断商机对应城市是否在可分配城市内，格式化城市名称
        $city = filterCity($oneOrder['city'], $allocateConfig['allocate_filter_city']);
        if(in_array($city, $allocateConfig['allocate_city'])){
            //当前商机对应渠道
            $channel = $oneOrder['channel'];

            //从分配队列中获取下一个待分配业务员，属于特殊渠道的商机需要获取指定的业务员
            $allocate_user = (new AllocateUserModel())->getNewestAllocateUser($city, $allocateConfig['allocate_channel'], $channel);
            if($allocate_user['can'] !== true){
                return false;
            }

            //如果有分配人就更改分配顺序
            if($allocate_user['info']['user_id']){

                //更新本次推送时间就可达到更改分配顺序的目的
                M('allocate_user')->where(array('id'=>$allocate_user['info']['id']))->save(array('update_time'=>time()));

                //获取所有渠道价格
                $all_company_channel_price = (new CacheLogic())->get_all_company_channel_price();

                //获取业务员账户余额以及所在公司
                $userInfo = M('user')->field('company_id,balance')->where(array('id'=>$allocate_user['info']['user_id']))->find();
                if (empty($userInfo)) {
                    return false;
                }

                //获取公司账户余额
                $companyInfo = M('company_branch')->field('id,score')->where(array('id'=>$userInfo['company_id']))->find();
                if (empty($companyInfo)) {
                    return false;
                }

                //根据用户所在公司查找对应的渠道价格
                if (!empty($all_company_channel_price[$userInfo['company_id']][$channel])) {
                    $company_channel_price = $all_company_channel_price[$userInfo['company_id']][$channel];
                } else {
                    $company_channel_price = 0;
                }

                //判断业务员账户余额是否大于渠道价格,余额不足时不分配数据
                if ($company_channel_price > $userInfo['balance']) {
                    return false;
                }

                //********************************** 下面开始分配客户操作 **********************************//

                //先处理是不是api分配
                if($allocate_user['info']['mall_company_id']){
                    if(!$allocate_user['info']['action']){
                        return false;
                    }
                    $api_url = "http://".$_SERVER['HTTP_HOST'].U($allocate_user['info']['action']);
                    $api_ret = post_url($api_url,array('company_id'=>$allocate_user['info']['mall_company_id'],'first_customer_id'=>$oneOrder['id']));
                    $api_ret = json_decode($api_ret,true);
                    //api推送失败报错就直接返回客户分配失败
                    if(!$api_ret || $api_ret['errNum']!==0){
                        return false;
                    }
                }

                //避免先分配后再扣款失败问题，这里先扣款：用户和公司账户减去对应金额
                $result = $res = $resAddLog = true;
                if ($company_channel_price > 0) {
                    $result = M('company_branch')->where(array('id'=>$userInfo['company_id']))->save(array('score'=>$companyInfo['score']-$company_channel_price));
                    $res = M('user')->where(array('id'=>$allocate_user['info']['user_id']))->save(array('balance'=>$userInfo['balance']-$company_channel_price));
                    $resAddLog = M('company_score_log')->add(array('company_id'=>$userInfo['company_id'],'user_id'=>$allocate_user['info']['user_id'],'score'=>-$company_channel_price,'create_time'=>time()));
                }

                if ($result && $res && $resAddLog) {

                    //添加客户并且添加交易
                    $customerReturn = (new OrderModel())->addCustomerAndOrderByOneBusiness($oneOrder, $city, $allocate_user['info']['user_id']);
                    if($customerReturn === false){
                        return false;
                    }

                    //发送通知，通知作业人员
                    (new MessageLogic())->addMessage('allocate_customer_new','',array(),$allocate_user['info']['user_id'],0,$customerReturn['order_number']);

                    //添加分配日志
                    $addData = array(
                        'customer_id' => $oneOrder['id'],
                        'order_id'    => $customerReturn['order_id'],
                        'first_customer_id' => $oneOrder['id'],
                        'old_user_id' => 0,
                        'new_user_id' => $allocate_user['info']['user_id'],
                        'remark'      => '自动分配',
                        'price'       => $company_channel_price
                    );
                    $result = M('allocate_log')->add($addData);
                    if (!$result) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }else{ //不在分配城市时将客户添加到无归属客户
            $result = (new OrderModel())->addCustomerAndOrderByOneBusiness($oneOrder, $city);
            if(!$result){
                return false;
            }
        }
        return true;
    }


    /**
     * 推送一个商机给业务员
     * 未完成此功能
     */
    public function pushOneBusinessToCustomerNew($oneOrder, $allocateConfig)
    {
        //先判断商机对应城市是否在可分配城市内
        $city = filterCity($oneOrder['city'], $allocateConfig['allocate_filter_city']);
        if(in_array($city, $allocateConfig['allocate_city'])){
            //获取下一个待分配业务员，属于特殊渠道的商机需要获取指定的业务员
            if($oneOrder['channel'] && in_array($oneOrder['channel'], $allocateConfig['allocate_channel'])){
                $channel = $oneOrder['channel'];
            }else{
                $channel = '';
            }
            $where = array(
                'channel' => $channel,
                'city' => $city,
                'is_delete' => 0
            );
            $todayTime = date("Y-m-d",time());//is_over 当日是否已分满客户
            $allocate_user = M('allocate_user')->field('id,can_allocate,mall_company_id')->where($where)->where("`is_over` = '0' OR `is_over` = '1' AND `over_time` <> '{$todayTime}'")->order('update_time asc')->find();
            if (empty($allocate_user)) {
                //无分配计划或当日全部已分满
                return false;
            }
            //判断当日分配是否已达上限
            $condition = array(
                'remark' => '自动分配',
                'create_time' => array('gt',date('Y-m-d 00:00:00',time())),
                'new_user_id' => $allocate_user['user_id']
            );
            $current_allocate_count = M('allocate_log')->where($condition)->count();
            if($current_allocate_count >= $allocate_user['can_allocate']){
                $save = array();
                $save['is_over'] = 1;
                $save['over_time'] = $todayTime;
                M('allocate_user')->where(array('id'=>$allocate_user['id']))->save($save);
            }
            //获取所有渠道价格
            $all_company_channel_price = (new CacheLogic())->get_all_company_channel_price();
            //判断业务员账户余额是否大于该条商机对应渠道价格
            $userInfo = M('user')->field('company_id,balance')->where(array('id'=>$allocate_user['info']['user_id']))->find();
        } else {
            //不在分配城市时将客户添加到无归属客户
            $result = (new OrderModel())->addCustomerAndOrderByOneBusiness($oneOrder, $city);
            if($result === false){
                return false;
            } else {
                return true;
            }
        }
    }
}