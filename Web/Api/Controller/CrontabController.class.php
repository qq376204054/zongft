<?php
namespace Api\Controller;
use Admin\Logic\CacheLogic;//常量配置缓存
use Admin\Logic\CustomerLogic;//商机推送
use Message\Logic\MessageLogic;//发送短信消息

/**
 * 定时任务控制器
 */
class CrontabController extends JsonController {

    /**
     * 自动执行函数
     */
    public function _initialize()
    {
        parent::_initialize();
        $checkKey = $this->vaildKey(I('request.key',''));
        if($checkKey !== true){
            $this->errorjsonReturn($checkKey);
        }
    }

    /**
     * 验证定时任务执行所需的密匙
     */
    private function vaildKey($key)
    {
        if (empty($key)) {
            return '密匙不能为空';
        }
        //判断key值是否为定时任务执行所需的密钥
        if ($key == "LPhRwEHc6VQ40XA8iEJ6quXmYyxkIUVcktNDNH46FqsBJMoQyJ") {
            return true;
        } else {
           return '密匙信息错误'; 
       }
    }

    /**
     * 定时删除调试模式下生成的日志文件
     * 避免日志文件过大造成服务器阻塞
     */
    public function del_log()
    {
        $res = delDirAndFile(RUNTIME_PATH.'Logs');
        if ($res) {
            $this->setjsonReturn('日志文件已删除');
        } else {
            $this->errorjsonReturn('日志文件删除失败');
        }
    }

    /**
     * 使用短信进行消息通知
     */
    public function send_sms()
    {
        $message = new MessageLogic();

        //针对余额低于1000的公司进行提醒通知
        $companyList = M('company_branch')->field('id,name,score,business_name,business_mobile')->where(array('is_delete'=>0,'pid'=>0,'is_company'=>1,'status'=>1,'score'=>array('between',[1, 2000])))->select();
        if ($companyList && is_array($companyList) && count($companyList)>0) {
            foreach ($companyList as $key=>$value) {
                //给业务负责人发短信
                $condition = array(
                    'mobile' => $value['business_mobile'],
                    'type' => '公司账户余额不足通知',
                    'back' => 1,
                    'create_time' => array('gt',date('Y-m-d'))
                );
                $msgSendLog = M('phone_msg_log')->field('id,mobile')->where($condition)->find();
                if (empty($msgSendLog)) {
                    if (is_phone($value['business_mobile'])) {
                        $message->addMessage('company_balance_lack', $value['business_mobile'], array('name'=>$value['business_name'],'customer'=>$value['name'],'balance'=>$value['score']));
                    }
                }
            }
        }

        //针对余额低于500的业务员进行提醒通知
        $userList = M('user')->field('id,user_name,balance,company_id')->where(array('delete'=>1,'balance'=>array('between',[1, 1000])))->select();
        if ($userList && is_array($userList) && count($userList)>0) {
            foreach ($userList as $key=>$value) {
                //给业务负责人发短信
                $company = M('company_branch')->field('id,name,business_name,business_mobile')->where(array('id'=>$value['company_id']))->find();
                $condition = array(
                    'mobile' => $company['business_mobile'],
                    'type' => '业务员账户余额不足通知',
                    'back' => 1,
                    'create_time' => array('gt',date('Y-m-d'))
                );
                $msgSendLog = M('phone_msg_log')->field('id,mobile')->where($condition)->find();
                if (empty($msgSendLog)) {
                    if (is_phone($company['business_mobile'])) {
                        $message->addMessage('user_balance_lack', $company['business_mobile'], array('name'=>$company['business_name'],'customer'=>$company['name'],'username'=>$value['user_name'],'balance'=>$value['balance']));
                    }
                }
            }
        }

        //客户租赁系统快到期时进行提醒通知 提醒内容：您租用的系统当前可使用天数剩余X天，请及时续费，以免因欠费停用影响您的业务开展！
        $businessList = M('business')->field('id,mobile,remark,business_name,business_mobile,end_time')->where(array('end_time'=>array('lt',strtotime("+3 days"))))->select();
        if ($businessList && is_array($businessList) && count($businessList)>0) {
            foreach ($businessList as $key=>$value) {
                $time_diff = $value['end_time'] - time() ;
                $days = intval($time_diff / 86400);
                if ($days>=0) {
                    if (is_phone($value['mobile'])) { //给租系统联系人发短信
                        $message->addMessage('business_end_time', $value['mobile'], array('day'=>$days,'type'=>1));
                    }
                    if (is_phone($value['business_mobile'])) { //给业务负责人发短信
                        $message->addMessage('business_end_time', $value['business_mobile'], array('name'=>$value['business_name'],'remark'=>$value['remark'],'day'=>$days,'type'=>2));
                    }
                    // $condition = array(
                    //     'mobile' => $value['mobile'],
                    //     'type' => '租赁系统到期通知',
                    //     'back' => 1,
                    //     'create_time' => array('gt',date('Y-m-d'))
                    // );
                    // $msgSendLog = M('phone_msg_log')->field('id,mobile')->where($condition)->find();
                    // if (empty($msgSendLog)) {
                    //     if (is_phone($value['mobile'])) { //给租系统联系人发短信
                    //         $message->addMessage('business_end_time', $value['mobile'], array('day'=>$days,'type'=>1));
                    //     }
                    // }
                    // $condition['mobile'] = $value['business_mobile'];
                    // $msg_send_log = M('phone_msg_log')->field('id,mobile')->where($condition)->find();
                    // if (empty($msg_send_log)) {
                    //     if (is_phone($value['business_mobile'])) { //给业务负责人发短信
                    //         $message->addMessage('business_end_time', $value['business_mobile'], array('name'=>$value['business_name'],'remark'=>$value['remark'],'day'=>$days,'type'=>2));
                    //     }
                    // }
                }
            }
        }
        $this->setjsonReturn('执行成功');
    }

    /**
     * 商机推送：先推送未处理的商机，再推送之前推送失败的商机
     * 建议每5分钟跑一次
     */
    public function push_business()
    {
        //从常量配置缓存中获取允许分配的时间段
        $all_config = (new CacheLogic())->get_all_config();
        $open_allocate_time = $all_config['open_allocate_time'];
        //开始时间
        if ($open_allocate_time['start_time']) {
            $start_time = date('Y-m-d',time())." ".$open_allocate_time['start_time'].":00";
            $start_time_str = strtotime($start_time);
            if (time() < $start_time_str) {
                $this->errorjsonReturn('还没有到分配时间');
                die();
            }
        } 
        //结束时间
        if ($open_allocate_time['stop_time']) {
            $stop_time = date('Y-m-d',time())." ".$open_allocate_time['stop_time'].":00";
            $stop_time_str = strtotime($stop_time);
            if (time() > $stop_time_str) {
                $this->errorjsonReturn('今日分配时间已过');
                die();
            }
        }
        //判断程序是否已经在运行中
        if (S('push_business_running')) {
            $this->errorjsonReturn('程序运行中'); 
            die();
        }
        //写缓存记录正在进行的任务
        S('push_business_running',1,300);
        set_time_limit(0);
        //开始推送商机
        $return = (new CustomerLogic())->pushBusinessToCustomer();
        //清除缓存
        S('push_business_running',NULL);
        //记录结果
        if ($return['status'] === true) {
            $this->setjsonReturn($return['msg']);
        } else {
            $this->errorjsonReturn($return['msg']);
        }
    }

    /**
     * 新分配数据X分钟未跟进的自动转入所在公司或所在部门的下一坐席  
     * 建议每3分钟跑一次
     */
    public function no_communicate_allocate()
    {
        //从常量配置缓存中获取配置项
        $all_config = (new CacheLogic())->get_all_config();
        $setting_time = $all_config['new_customer_not_inrule_time']['no_communicate_time']?$all_config['new_customer_not_inrule_time']['no_communicate_time']:0;
        if (!$setting_time) {
            $this->errorjsonReturn('未设置参数： no_communicate_time');
            die();
        }
        //从新分配客户中获取在X分钟内未跟进的客户数据
        $where = array(
            'user_id' => array('neq',0),
            'is_from_allocate' => 1,
            'customer_status' => 1,
            'communicate_time' => 0,
            'change_user_time' => array('lt',time()-$setting_time*60)
        );
        $list = M('order')->where($where)->limit(10)->select();
        if (!$list) {
            $this->errorjsonReturn('暂无可要处理的数据');
            die();
        }
        //判断程序是否已经在运行中
        if (S('no_communicate_allocate_running')) {
            $this->errorjsonReturn('程序运行中');
            die();
        }
        //写缓存记录正在进行的任务
        S('no_communicate_allocate_running',1,300);
        set_time_limit(0);
        //从常量配置缓存中获取是将客户转入公司还是部门（branch为部门）
        $allocateBranch = $all_config['new_customer_not_inrule_to'];
        //下面对人进行一个一个分配
        foreach ($list as $key=>$value) {
            //随机寻找一个公司或部门的业务员
            $user_id = $this->getRandomUserId($allocateBranch, $value['company_id'], $value['user_id']);
            if ($user_id===false) {
                continue;
            }
            //设置客户的新负责人
            M('order')->where(array('id'=>$value['id']))->save(array('user_id'=>$user_id,'change_user_time'=>time()));
            //记录日志
            M('allocate_log')->add(array(
                'order_id'=>$value['id'],
                'old_user_id'=>$value['user_id'],
                'new_user_id'=>$user_id,
                'remark'=>'新商机未沟通分配'
            ));
        }
        //清除缓存
        S('no_communicate_allocate_running',NULL);
        $this->setjsonReturn('当前任务已执行完毕');
    }

    /**
     * 新分配数据X日内未标记为重点客户的丢入公盘 
     * 建议每30分钟跑一次
     */
    public function outtime_important_pool()
    {
        //从常量配置缓存中获取配置项
        $all_config = (new CacheLogic())->get_all_config();
        $setting_time = $all_config['new_customer_not_inrule_time']['not_set_important_time']?$all_config['new_customer_not_inrule_time']['not_set_important_time']:0;
        if (!$setting_time) {
            $this->errorjsonReturn('未设置参数： not_set_important_time');
            die();
        }
        //从新分配客户中获取在X日内未标记为重点客户的数据
        $where = array(
            'user_id'=>array('neq',0),
            'customer_status'=>1,
            'is_from_allocate'=>1,
            'change_user_time'=>array('lt',time()-$setting_time*24*60*60)
        );
        $list = M('order')->where($where)->limit(100)->select();
        if (!$list) {
            $this->errorjsonReturn('暂无可要处理的数据');
            die();
        }
        //判断程序是否已经在运行中
        if (S('outtime_important_pool_running')) {
            $this->errorjsonReturn('程序运行中');
            die();
        }
        //写缓存记录正在进行的任务
        S('outtime_important_pool_running',1,300);
        set_time_limit(0);
        //下面开始丢入公盘
        M('order')->where($where)->save(array('user_id'=>0,'change_user_time'=>time()));
        //下面开始记录日志
        $addAll = array();
        foreach($list as $key=>$value){
            $tempData = array(
                'order_id'=>$value['id'],
                'old_user_id'=>$value['user_id'],
                'new_user_id'=>0,
                'remark'=>'未标记重要丢入公盘'
            );
            $addAll[] = $tempData;
        }
        M('allocate_log')->addAll($addAll);
        //清除缓存
        S('outtime_important_pool_running',NULL);
        $this->setjsonReturn('当前任务已执行完毕');
    }

    /**
     * 再分配数据X日内未标记为重点客户的丢入公盘 
     * 建议每10分钟跑一次
     */
    public function old_outtime_important_pool()
    {
        //从常量配置缓存中获取配置项
        $all_config = (new CacheLogic())->get_all_config();
        $setting_time = $all_config['new_customer_not_inrule_time']['old_not_set_important']?$all_config['new_customer_not_inrule_time']['old_not_set_important']:0;
        if (!$setting_time) {
            $this->errorjsonReturn('未设置参数： old_not_set_important');
            die();
        }
        //从再分配客户中获取在X日内未标记为重点客户的数据
        $where = array(
            'user_id'=>array('neq',0),
            'customer_status'=>1,
            'is_from_allocate'=>0,
            'change_user_time'=>array('lt',time()-$setting_time*24*60*60)
        );
        $list = M('order')->where($where)->limit(100)->select();
        if (!$list) {
            $this->errorjsonReturn('暂无可要处理的数据');
            die();
        }
        //判断程序是否已经在运行中
        if (S('old_outtime_important_pool_running')) {
            $this->errorjsonReturn('程序运行中');
            die();
        }
        //写缓存记录正在进行的任务
        S('old_outtime_important_pool_running',1,300);
        set_time_limit(0);
        //下面开始丢入公盘
        M('order')->where($where)->save(array('user_id'=>0,'change_user_time'=>time()));
        //下面开始记录日志
        $addAll = array();
        foreach($list as $key=>$value){
            $tempData = array(
                'order_id'=>$value['id'],
                'old_user_id'=>$value['user_id'],
                'new_user_id'=>0,
                'remark'=>'再分配客户未标记重要丢入公盘'
            );
            $addAll[] = $tempData;
        }
        M('allocate_log')->addAll($addAll);
        //清除缓存
        S('old_outtime_important_pool_running',NULL);
        $this->setjsonReturn('当前任务已执行完毕');
    }

    /**
     * 新分配数据X日内未标记为已上门的自动转
     * 建议每30分钟跑一次
     */
    public function not_set_meeting_time()
    {
        //从常量配置缓存中获取配置项
        $all_config = (new CacheLogic())->get_all_config();
        $setting_time = $all_config['new_customer_not_inrule_time']['not_set_meeting_time']?$all_config['new_customer_not_inrule_time']['not_set_meeting_time']:0;
        if (!$setting_time) {
            $this->errorjsonReturn('未设置参数： not_set_meeting_time');
            die();
        }
        //从新分配客户中获取在X日内未标记为已上门的数据
        $where = array(
            'user_id'=>array('neq',0),
            'is_from_allocate'=>1,
            'customer_status'=>2,
            'change_user_time'=>array('lt',time()-$setting_time*24*60*60)
        );
        $list = M('order')->where($where)->limit(10)->select();
        if (!$list) {
            $this->errorjsonReturn('暂无可要处理的数据');
            die();
        }
        //判断程序是否已经在运行中
        if (S('not_set_meeting_time_running')) {
            $this->errorjsonReturn('程序运行中');
            die();
        }
        //写缓存记录正在进行的任务
        S('not_set_meeting_time_running',1,300);
        set_time_limit(0);
        //从常量配置缓存中获取是将客户转入公司还是部门（branch为部门）
        $allocateBranch = $all_config['new_customer_not_inrule_to'];
        //下面对人进行一个一个分配
        foreach($list as $key=>$value){
            //随机寻找一个公司或部门的业务员
            $user_id = $this->getRandomUserId($allocateBranch, $value['company_id'], $value['user_id']);
            if ($user_id===false) {
                continue;
            }
            //设置客户的新负责人
            M('order')->where(array('id'=>$value['id']))->save(array('user_id'=>$user_id,'change_user_time'=>time()));
            //记录日志
            M('allocate_log')->add(array(
                'order_id'=>$value['id'],
                'old_user_id'=>$value['user_id'],
                'new_user_id'=>$user_id,
                'remark'=>'重要客户未上门自动转走'
            ));
        }
        //清除缓存
        S('not_set_meeting_time_running',NULL);
        $this->setjsonReturn('当前任务已执行完毕');
    }

    /**
     * 新分配数据X日内未标记为已签约的自动转
     * 建议每30分钟跑一次
     */
    public function not_set_order_time()
    {
        //从常量配置缓存中获取配置项
        $all_config = (new CacheLogic())->get_all_config();
        $setting_time = $all_config['new_customer_not_inrule_time']['not_set_order_time']?$all_config['new_customer_not_inrule_time']['not_set_order_time']:0;
        if (!$setting_time) {
            $this->errorjsonReturn('未设置参数： not_set_order_time');
            die();
        }
        //从新分配客户中获取在X日内未标记为已签约的数据
        $where = array(
            'user_id'=>array('neq',0),
            'is_from_allocate'=>1,
            'customer_status'=>3,
            'change_user_time'=>array('lt',time()-$setting_time*24*60*60)
        );
        $list = M('order')->where($where)->limit(10)->select();
        if (!$list) {
            $this->errorjsonReturn('暂无可要处理的数据');
            die();
        }
        //判断程序是否已经在运行中
        if (S('not_set_order_time_running')) {
            $this->errorjsonReturn('程序运行中');
            die();
        }
        //写缓存记录正在进行的任务
        S('not_set_order_time_running',1,300);
        set_time_limit(0);
        //从常量配置缓存中获取是将客户转入公司还是部门（branch为部门）
        $allocateBranch = $all_config['new_customer_not_inrule_to'];
        //下面对人进行一个一个分配
        foreach($list as $key=>$value){
            //随机寻找一个公司或部门的业务员
            $user_id = $this->getRandomUserId($allocateBranch, $value['company_id'], $value['user_id']);
            if ($user_id===false) {
                continue;
            }
            //设置客户的新负责人
            M('order')->where(array('id'=>$value['id']))->save(array('user_id'=>$user_id,'change_user_time'=>time()));
            //记录日志
            M('allocate_log')->add(array(
                'order_id'=>$value['id'],
                'old_user_id'=>$value['user_id'],
                'new_user_id'=>$user_id,
                'remark'=>'上门客户未签约自动转走'
            ));
        }
        //清除缓存
        S('not_set_order_time_running',NULL);
        $this->setjsonReturn('当前任务已执行完毕');
    }

    /**
     * 新分配数据X日内未标记为已批款的自动转
     * 建议每30分钟跑一次
     */
    public function not_set_last_time()
    {
        //从常量配置缓存中获取配置项
        $all_config = (new CacheLogic())->get_all_config();
        $setting_time = $all_config['new_customer_not_inrule_time']['not_set_last_time']?$all_config['new_customer_not_inrule_time']['not_set_last_time']:0;
        if (!$setting_time) {
            $this->errorjsonReturn('未设置参数： not_set_last_time');
            die();
        }
        //从新分配客户中获取在X日内未标记为已批款的数据
        $where = array(
            'user_id'=>array('neq',0),
            'is_from_allocate'=>1,
            'customer_status'=>4,
            'change_user_time'=>array('lt',time()-$setting_time*24*60*60)
        );
        $list = M('order')->where($where)->limit(10)->select();
        if (!$list) {
            $this->errorjsonReturn('暂无可要处理的数据');
            die();
        }
        //判断程序是否已经在运行中
        if (S('not_set_last_time_running')) {
            $this->errorjsonReturn('程序运行中');
            die();
        }
        //写缓存记录正在进行的任务
        S('not_set_last_time_running',1,300);
        set_time_limit(0);
        //从常量配置缓存中获取是将客户转入公司还是部门（branch为部门）
        $allocateBranch = $all_config['new_customer_not_inrule_to'];
        //下面对人进行一个一个分配
        foreach($list as $key=>$value){
            //随机寻找一个公司或部门的业务员
            $user_id = $this->getRandomUserId($allocateBranch, $value['company_id'], $value['user_id']);
            if ($user_id===false) {
                continue;
            }
            //设置客户的新负责人
            M('order')->where(array('id'=>$value['id']))->save(array('user_id'=>$user_id,'change_user_time'=>time()));
            //记录日志
            M('allocate_log')->add(array(
                'order_id'=>$value['id'],
                'old_user_id'=>$value['user_id'],
                'new_user_id'=>$user_id,
                'remark'=>'签约未标记已放款自动转走'
            ));
        }
        //清除缓存
        S('not_set_last_time_running',NULL);
        $this->setjsonReturn('当前任务已执行完毕');
    }

    /**
     * 随机获取公司或部门下一个业务员
     */
    private function getRandomUserId($allocateBranch,$company_id,$user_id)
    {
        if ($allocateBranch=='branch') {
            $branch_id = M('user')->where(array('id'=>$user_id))->getField('branch_id');
            if (!$branch_id) {
                return false;
            }
            $user_ids = M('user')->where(array('branch_id'=>$branch_id,'delete'=>1))->getField('id',true);
        } else {
            $user_ids = M('user')->where(array('company_id'=>$company_id,'delete'=>1))->getField('id',true);
        }
        if (!$user_ids) {
            return false;
        }
        $new_user_ids = M('allocate_user')->where(array('is_delete'=>0,'user_id'=>array('in',$user_ids)))->getField('user_id',true);
        $new_user_ids = array_filter(array_unique($new_user_ids));
        if (!$new_user_ids) {
            return false;
        }
        $index = rand(1, count($new_user_ids));
        $cur_user_id = $new_user_ids[$index-1];
        if (!$cur_user_id) {
            return false;
        }
        return $cur_user_id;
    }
}