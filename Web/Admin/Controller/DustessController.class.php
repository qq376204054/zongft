<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 智能外呼控制器
 */
class DustessController extends Controller
{
    protected $ret = array('errNum'=>0,'errMsg'=>'success');//默认返回信息
    protected $accessToken = '';//获取数据的令牌，有效期为2个⼩时
    protected $refreshToken = '';//刷新accessToken的令牌，有效期为30天
    protected $userPhone = '';//员工手机
    protected $callPhone = '';//被叫手机

    /**
     * 自动执行函数
     */
    public function _initialize()
    {
        //判断员工是否登录
        if (session('user_info_new')){
            $this->userPhone = session('user_info_new.mobile');
        } else {
            $this->errorjsonReturn('请先登录');
        }

        //根据订单号获取客户手机号
        $id = I('get.id');
        $orderInfo = M('order')->field('id,mobile')->where(array('id'=>$id))->find();
        if (empty($orderInfo)) {
            $this->errorjsonReturn('客户电话无法获取');
        } else {
            $this->callPhone = $orderInfo['mobile'];
        }

        //accessToken 有效时长 2⼩时
        $time_diff = time() - 7200;
        if(session('oauth_token') && session('oauth_token_time')>$time_diff) {
            $tokenData = session('oauth_token');
            $this->accessToken = $tokenData['accessToken'];
            $this->refreshToken = $tokenData['refreshToken'];
        } else {
            //refreshToken 有效时长 30天
            $time_diff = time() - 60*60*24*30;
            if($this->refreshToken != '' && session('oauth_token_time')>$time_diff) {
                $this->refreshToken();
            } else {
                $this->getToken();
            }
        }
    }

    /**
     * 接口鉴权，获取Token
     */
    protected function getToken()
    {
        //当前请求时间
        $timestamp = time() * 1000;
        //请求地址
        $url = "https://openapi.dustess.com/oauth/token/getToken?timestamp=".$timestamp;
        //请求参数
        $params = array(
            'clientId' => "4f1f172f1fc38036",//管理后台生成
            'clientSecret' => "f1b680355f7dc9c9ea49c1f018ff9828"//管理后台生成
        );
        //开始请求接口获取响应数据
        $output = $this->https_post_json($url, json_encode($params));
        $response = json_decode($output, true);
        if ($response && $response['code']==1 && $response['data']) {
            $api_ok = true;
            $msg = $response['msg'];
            $resData = $response['data'];
            session('oauth_token', $resData);
            session('oauth_token_time', time());
            $this->accessToken = $resData['accessToken'];
            $this->refreshToken = $resData['refreshToken'];
        } else {
            $api_ok = false;
            $msg = $response['msg'] ? $response['msg'] : '接口挂了';
        }
        $this->addLog($api_ok, $msg, $output);
        //接口请求失败时返回错误信息
        if ($api_ok == false) {
            $this->errorjsonReturn($msg);
        }
    }

    /**
     * 刷新Token
     */
    protected function refreshToken()
    {
        //当前请求时间
        $timestamp = time() * 1000;
        //请求地址
        $url = "https://openapi.dustess.com/oauth/token/reToken?timestamp=".$timestamp."&refreshToken=".$this->refreshToken;
        //开始请求接口获取响应数据
        $output = $this->https_post_json($url);
        $response = json_decode($output, true);
        if ($response && $response['code']==1 && $response['data']) {
            $api_ok = true;
            $msg = $response['msg'];
            $resData = $response['data'];
            $this->accessToken = $resData['accessToken'];
        } else {
            $api_ok = false;
            $msg = $response['msg'] ? $response['msg'] : '接口挂了';
        }
        $this->addLog($api_ok, $msg, $output);
        //接口请求失败时返回错误信息
        if ($api_ok == false) {
            $this->errorjsonReturn($msg);
        }
    }

    /**
     * 拨打电话V1版
     */
    public function callPhone()
    {
        //当前请求时间
        $timestamp = time() * 1000;
        //请求地址
        $url = "https://openapi.dustess.com/api/crm/callPhone?timestamp=".$timestamp."&accessToken=".$this->accessToken;
        //请求参数
        $params = array(
            'callPhone' => $this->callPhone,//被叫手机号码
            'userPhone' => $this->userPhone,//员工手机号码
            "isEncrypt" => false,//该通外呼是否加密被叫手机号码
            "callId" => '',
            "callType" => ''
        );
        //开始请求接口获取响应数据
        $output = $this->https_post_json($url, json_encode($params));
        $response = json_decode($output, true);
        if ($response && $response['code']==1) {
            $api_ok = true;
            $msg = $this->userPhone.'->'.$this->callPhone.'：'.$response['msg'];
        } else {
            $api_ok = false;
            $msg = $response['msg'] ? $response['msg'] : '接口挂了';
            $msg = $this->userPhone.'->'.$this->callPhone.'：'.$msg;
        }
        $this->addLog($api_ok, $msg, $output);
        //接口请求失败时返回错误信息
        if ($api_ok == false) {
            $this->errorjsonReturn($msg);
        }
    }


    /**
     * 通过post传输Json化数据
     */
    protected function https_post_json($url='', $jsonStr='')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        if (!empty($jsonStr)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * 写入推送日志
     */
    protected function addLog($api_ok,$msg,$api_data)
    {
        $add = array();
        $add['is_ok'] = $api_ok ? 1:2;
        $add['msg'] = $msg;
        $add['api_data'] = $api_data;
        $add['create_time'] = time();
        M('dustess_log')->add($add);
    }

    /**
     * 返回错误格式的json数据
     */
    protected function errorjsonReturn($error_msg)
    {
        $this->ret['errNum'] = -1;
        $this->ret['errMsg'] = $error_msg;
        $this->ajaxReturn ($this->ret,'JSON');
    }
}