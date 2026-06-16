<?php
namespace Message\Logic;
use Admin\Logic\CacheLogic;
use Admin\Model\OrderModel;
//包含通讯类
vendor("GatewayClient.Gateway");
use GatewayClient\Gateway;

/**
 * 消息服务机制（系统消息+短信消息）
 * Class MessageLogic
 * @package Admin\Logic
 */
class MessageLogic
{
    private $SMS_API_KEY = '';
    private $SMS_API_KEY_USER = '';
    private $SMS_API_URL = 'http://yunpian.com/v1/sms/send.json';
    private $all_setting = array();

    public function __construct(){
        $all_setting=(new CacheLogic())->get_all_setting();
        $this->SMS_API_KEY=$all_setting['yunpian_sms_key'];
        $this->SMS_API_KEY_USER=$all_setting['yunpian_sms_key_user'];
        $this->all_setting = $all_setting;
    }

    /**
     * @param $type
     * @param string $mobile
     * @param array $customer_infos
     * @param int $user_id
     * @param int $old_user_id
     * @param string $order_number
     * @return bool
     */
    public function addMessage($type,$mobile='',$customer_infos=array(),$user_id=0,$old_user_id=0,$order_number=''){
        if($type=='customer_apply'){               //贷款申请验证码短信
            $code=(new OrderModel())->makeStr('0123456789',4);
            $sms_temp = $this->all_setting['apply_money_sms_code'];
            if(!$sms_temp){return false;}
            $content = sprintf($sms_temp,$code);
            $return=$this->sendSMS($mobile,$content);
            $addPhoneLog=array('mobile'=>$mobile, 'code'=>$code, 'content'=>$content, 'create_time'=>time());
            $addPhoneLog['back']=$return?1:0;
            M('phone_code')->add($addPhoneLog);
        }elseif($type=='apply_success'){           //贷款申请成功短信
            $content = $this->all_setting['apply_success_sms_code'];
            if(!$content){return false;}
            $return=$this->sendSMS($mobile,$content);
            $add_phone_log=array('mobile'=>$mobile,'type'=>'客户通知','content'=>$content);
            $add_phone_log['back']=$return?1:0;
            M('phone_msg_log')->add($add_phone_log);
        }elseif($type=='allocate_customer_new'){     //自动分配客户  --要系统通知也要短信   $user_id  $order_number
            $mobile=M('user')->where(array('id'=>$user_id))->getField('mobile');
            $order_number = substr($order_number, 0 , 9);
            $sms_temp = $this->all_setting['allocate_customer_new_sms_code'];
            if(!$sms_temp){return false;}
            $content = sprintf($sms_temp,$order_number);
            //下面添加系统消息
            $addMessage=array('user_id'=>$user_id,'content'=>$content,'create_time'=>time());
            $return=M('message_log')->add($addMessage);
            //马上通知界面的用户
            $this->sendMessage($user_id,$return,$content);
            //下面添加手机短息消息
            $return=$this->sendSMS($mobile,$content,2);
            $add_phone_log=array('mobile'=>$mobile,'type'=>'系统分配客户通知','content'=>$content);
            $add_phone_log['back']=$return?1:0;
            M('phone_msg_log')->add($add_phone_log);
        }elseif($type=='allocate_customer_old'){     //自动分配老客户  --要系统通知也要短信   $user_id  $order_number
            $mobile=M('user')->where(array('id'=>$user_id))->getField('mobile');
            $order_number = substr($order_number, 0 , 9);
            $sms_temp = $this->all_setting['allocate_customer_old_sms_code'];
            if(!$sms_temp){return false;}
            $content = sprintf($sms_temp,$order_number);
            //下面添加系统消息
            $addMessage=array('user_id'=>$user_id,'content'=>$content,'create_time'=>time());
            $return1=M('message_log')->add($addMessage);
            //马上通知界面的用户
            $this->sendMessage($user_id,$return1,$content);
            //下面添加手机短息消息
            $this->sendSMS($mobile,$content,2);
            $add_phone_log=array('mobile'=>$mobile,'type'=>'系统分配客户通知','content'=>$content);
            $add_phone_log['back']=$return1?1:0;
            M('phone_msg_log')->add($add_phone_log);
        }elseif($type=='move_customer'){             //移动客户  --系统要通知老负责人  同时要系统消息通知新负责人
            $customer_names=implode('、',array_column($customer_infos,'name'));
            $to_new_content='有客户《'.$customer_names.'》转移至你名下，请尽快操作';
            $return=M('message_log')->add(array('user_id'=>$user_id,'content'=>$to_new_content,'create_time'=>time()));
            //马上通知界面的用户
            $this->sendMessage($user_id,$return,$to_new_content);
            foreach($customer_infos as $value){
                if($value['user_id']){
                    $content='你名下客户《'.$value['name'].'》被转移，敬请须知';
                    $return=M('message_log')->add(array('user_id'=>$value['user_id'],'content'=>$content,'create_time'=>time()));
                    //马上通知界面的用户
                    $this->sendMessage($value['user_id'],$return,$content);
                }
            }
        }elseif($type=='want_apply_order'){          //申请的通知  --系统通知要通知需审批人
            $content='有订单提交到您的手上，请及时审批';
            $addMessage=array('user_id'=>$user_id,'content'=>$content,'create_time'=>time());
            $return=M('message_log')->add($addMessage);
            //马上通知界面的用户
            $this->sendMessage($user_id,$return,$content);
        }elseif($type=='order_apply_success'){      //审批通过的通知  --系统通知 提交人
            $content='您提交的审批已经通过，您可以在业绩中核对自己的业绩';
            $addMessage=array('user_id'=>$user_id,'content'=>$content,'create_time'=>time());
            $return =M('message_log')->add($addMessage);
            //马上通知界面的用户
            $this->sendMessage($user_id,$return,$content);
        }elseif($type=='order_apply_error'){      //审批通过的通知  --系统通知 提交人
            $content='您提交的审批未成功，您可以修改后重新提交';
            $addMessage=array('user_id'=>$user_id,'content'=>$content,'create_time'=>time());
            $return = M('message_log')->add($addMessage);
            //马上通知界面的用户
            $this->sendMessage($user_id,$return,$content);
        }elseif($type=='mobile_code'){
            $code=(new OrderModel())->makeStr('0123456789',4);
            $sms_temp = $this->all_setting['mobile_admin_sms_code'];
            if(!$sms_temp){return false;}
            $content = sprintf($sms_temp,$code);
            $return=$this->sendSMS($mobile,$content);
            $addPhoneLog=array('mobile'=>$mobile, 'code'=>$code, 'content'=>$content, 'create_time'=>time());
            $addPhoneLog['back']=$return?1:0;
            M('phone_code')->add($addPhoneLog);

        } elseif($type=='business_end_time') { //租赁系统到期
            if ($customer_infos['type'] == 2) {
                //给业务负责人发短信
                $sms_temp = "【全行通】亲爱的%s，您负责的客户《%s》租用的系统当前可使用天数剩余%d天，请提醒及时续费，以免因欠费停用影响您的业务开展！";
                $content = sprintf($sms_temp, $customer_infos['name'], $customer_infos['remark'], $customer_infos['day']);
            } else {
                //给租系统联系人发短信
                $sms_temp = "【全行通】尊敬的用户，您租用的系统当前可使用天数剩余%d天，请及时续费，以免因欠费停用影响您的业务开展！";
                $content = sprintf($sms_temp, $customer_infos['day']);
            }
            $return = $this->sendSMS($mobile, $content);
            $add_phone_log = array('mobile'=>$mobile,'type'=>'租赁系统到期通知','content'=>$content);
            $add_phone_log['back'] = $return ? 1:0;
            M('phone_msg_log')->add($add_phone_log);

        } elseif($type=='company_balance_lack') { //公司账户余额不足时
            $sms_temp = "【全行通】亲爱的%s，您负责的客户《%s》当前推送可用余额剩余%s，请提醒及时续费！";
            $content = sprintf($sms_temp, $customer_infos['name'], $customer_infos['customer'], $customer_infos['balance']);
            $return = $this->sendSMS($mobile, $content);
            $add_phone_log = array('mobile'=>$mobile,'type'=>'公司账户余额不足通知','content'=>$content);
            $add_phone_log['back'] = $return ? 1:0;
            M('phone_msg_log')->add($add_phone_log);

        } elseif($type=='user_balance_lack') { //业务员账户余额不足时
            $sms_temp = "【全行通】亲爱的%s，您负责的客户《%s》下的业务经理《%s》当前推送可用余额剩余%s，请提醒及时续费！";
            $content = sprintf($sms_temp, $customer_infos['name'], $customer_infos['customer'], $customer_infos['username'], $customer_infos['balance']);
            $return = $this->sendSMS($mobile, $content);
            $add_phone_log = array('mobile'=>$mobile,'type'=>'业务员账户余额不足通知','content'=>$content);
            $add_phone_log['back'] = $return ? 1:0;
            M('phone_msg_log')->add($add_phone_log);
        }
        return true;
    }

    /**
     * 发送消息
     */
    public function sendMessage($user_id,$msg_id,$content)
    {
        $count=M('message_log')->where(array('is_look'=>0,'user_id'=>$user_id))->count();
        $msg=json_encode(array(
            'type'=>'system_msg',
            'data'=>array(
                'count'=>$count,
                'msg' =>$msg_id,
                'content' => $content,
                'time'=>time()
            )
        ));
        try {
            Gateway::sendToUid($user_id,$msg);
        } catch (\Exception $e) {
            //可以发送错误信息给管理员电话，告知错误信息
            return $e->getMessage();
        }
        return true;
    }

    /**
     * 绑定会话
     * @param $client_id
     * @param $user_id
     * @return bool|string
     */
    public function bindclient($client_id,$user_id)
    {
        try {
            Gateway::bindUid($client_id, $user_id);
        } catch (\Exception $e) {
            //可以发送错误信息给管理员电话，告知错误信息
            return $e->getMessage();
        }
        return true;
    }


    /**
     * 发送短信
     * @param $phone_number
     * @param $content
     * @param int $action  1-推送给客户  2-推送给员工
     * @return bool
     */
    private function sendSMS($phone_number,$content,$action=1)
    {
        if($action==1){
            $key = $this->SMS_API_KEY;
        }else{
            $key = $this->SMS_API_KEY_USER;
        }
        $content = urlencode($content);
        $post_string = "apikey=".$key."&text=$content&mobile=$phone_number";
        $result = self::socketSend($this->SMS_API_URL, $post_string);
        return $result;
    }


    /**
     * 发送营销短信
     * @param $mobile
     * @param $content
     * @return bool
     */
    public function postYingxiaoSms($sms_key,$mobile,$content)
    {
        $content = urlencode($content);
        $post_string = "apikey=".$sms_key."&text=$content&mobile=$mobile";
        $result = self::socketSend($this->SMS_API_URL, $post_string);
        return $result;
    }

    /**
     * url socket 形式提交
     * @param $url 服务的url地址
     * @param $query 请求串
     * @return bool
     */
    private function socketSend($url,$query)
    {
        $data = "";
        $info = parse_url($url);
        $fp = fsockopen($info["host"], 80, $errno, $errstr, 30);
        if (!$fp) {
            return $data;
        }
        $head = "POST " . $info['path'] . " HTTP/1.0\r\n";
        $head .= "Host: " . $info['host'] . "\r\n";
        $head .= "Referer: http://" . $info['host'] . $info['path'] . "\r\n";
        $head .= "Content-type: application/x-www-form-urlencoded\r\n";
        $head .= "Content-Length: " . strlen(trim($query)) . "\r\n";
        $head .= "\r\n";
        $head .= trim($query);
        $write = fputs($fp, $head);
        $header = "";
        //返回结果字符中去除头部信息
        while ($str = trim(fgets($fp, 4096))) {
            $header .= $str;
        }
        //返回的结果信息状态码、msg....
        while (!feof($fp)) {
            $data .= fgets($fp, 4096);
        }
        //关闭文件指针
        if ($fp) {
            fclose($fp);
        }
        $result = json_decode($data, true);
        return $result['code'] == 0 ? true : false;
    }
}