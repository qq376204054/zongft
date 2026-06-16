<?php
namespace Api\Controller;
use Admin\Logic\CacheLogic;

class IndexController extends JsonController {

    /**
     * 自动执行函数
     */
    public function _initialize()
    {
        parent::_initialize();
        $checkKey = $this->vaildKey(I('request.key',''));
        if ($checkKey !== true) {
            $this->errorjsonReturn($checkKey);
        }
        header("Access-Control-Allow-Origin: * ");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
        header('Access-Control-Allow-Headers:X-Requested-With,x-requested-with,content-type,accept,Content-Type, Accept,requesttype');
    }

    /**
     * 验证密匙
     */
    private function vaildKey($key)
    {
        if (!$key) {
            return '密匙不能为空';
        }
        $key_channel_manger = (new CacheLogic())->get_channel_key_manger();
        if (!array_key_exists($key,$key_channel_manger)) {
            return '密匙信息不存在';
        }
        return true;
    }

    /**
     * 撞库接口
     */
    public function check_loan_mobile()
    {
        $jsonStr = file_get_contents("php://input");
        if (!empty($jsonStr)) {
            $data = json_decode($jsonStr, true);
            if (empty($data['mobile'])) {
                $this->errorjsonReturn('手机号不能为空');
            }
            $info = M('first_customer')->query("select id,mobile from yq_first_customer where MD5(mobile) = '{$data['mobile']}' limit 1");
            if ($info) {
                $this->errorjsonReturn('手机号已存在');
            } else {
                $this->setjsonReturn('校验通过');
            }
        }
    }

    /**
     * 数据推入接口
     */
    public function add()
    {
        //请求数据处理
        $post = I('post.');
        foreach($post as $k=>$value){
            $post[$k] = trim($value);
        }
        if(!$post['name']){$this->errorjsonReturn('客户姓名不能为空');}
        if(!$post['mobile']){$this->errorjsonReturn('手机号不能为空');}
        if(!is_phone($post['mobile'])){$this->errorjsonReturn('手机号码格式错误');}
        if(!$post['city']){$this->errorjsonReturn('城市不能为空');}
        if(!$post['money']){$this->errorjsonReturn('贷款金额不能为空');}
        //根据key值判断数据来源渠道，如果key值不在所有渠道列表中，就取默认渠道
        $key_channel_manger=(new CacheLogic())->get_channel_key_manger();
        if(!in_array($post['channel'], $key_channel_manger[$post['key']]['child'])){
            $post['channel'] = $key_channel_manger[$post['key']]['default'];
        }
        if(!$post['channel']){
            $this->errorjsonReturn('系统有误');
        }

        //客户附加属性值获取
        $allocateConfig=(new CacheLogic())->get_all_config();
        $customer_ext_filed = $allocateConfig['customer_ext_filed'];
        $ext = array();
        foreach($post as $key=>$value){
            if(isset($customer_ext_filed[$key])){
                $ext[$key] = $value;
            }
        }

        //组装数据
        $add = array(
            'name'=>$post['name'],
            'mobile'=>$post['mobile'],
            'city'=>$post['city'],
            'money'=>$post['money'],
            'has_house'=>$post['has_house']?$post['has_house']:"无",
            'has_car'=>$post['has_car']?$post['has_car']:"无",
            'has_baodan'=>$post['has_baodan']?$post['has_baodan']:"无",
            'has_gongjijin'=>$post['has_gongjijin']?$post['has_gongjijin']:"无",
            'has_weilidai'=>$post['has_weilidai']?$post['has_weilidai']:"无",
            'has_nasui'=>$post['has_nasui']?$post['has_nasui']:"无",
            'channel'=>$post['channel'],
            'create_time'=>time(),
            'ext'=>json_encode($ext)
        );
        if(isset($post['sex'])){
            $add['sex'] = $post['sex'];
        }

        //下面判断有没有重复手机号的订单
        $oldFirstCustomer = M('first_customer')->where(array('mobile'=>$post['mobile']))->order('id desc')->find();
        $add['is_repeat'] = $oldFirstCustomer ? 1 : 0;//是否重复分配

        //下面判断这条数据是否时间范围内重复无需分配的订单,默认一天内不重复分配
        $repeat_order_limit = 24 * 60 * 60;
        if ($allocateConfig['repeat_order_limit'] > 0) {
            $repeat_order_limit = $allocateConfig['repeat_order_limit'] * 60 * 60;
        }
        $time_diff = $repeat_order_limit + $oldFirstCustomer['create_time'] - time();
        if($oldFirstCustomer && $time_diff>0){
            $add['status'] = 4;//申请频繁客户
        }
        //将数据先写入数据库
        $return = M('first_customer')->add($add);

        //是重复数据且从不属于本服务器地址请求时返回错误信息 用于推广渠道不计费
        if($add['is_repeat']==1 && $_SERVER['REMOTE_ADDR']!='116.62.217.120'){
            $this->errorjsonReturn('客户重复');
        }
        $return?$this->setjsonReturn('申请成功'):$this->errorjsonReturn('申请失败');
    }

    /**
     * 广点通数据推入接口
     * http://www.zongft.com/api/index/add_wide_data.html?key=oDYYJQGeT6HVx81mowPv3JTIXU5yTe97WD0wvaZvtU7AUuAzIY
     */
    public function add_wide_data()
    {
        $jsonStr = file_get_contents("php://input");
        if (!empty($jsonStr)) {
            $response = json_decode($jsonStr, true);
            if ($response) {
                $ext = json_decode($response['bundle'], true);
                if (!empty($ext)) {
                    $tempArr = explode('万', $ext['贷款金额']);
                    if ($tempArr[0] > 0) {
                        $money = $tempArr[0] * 10000;
                    } else {
                        $money = 0;
                    }
                }
                //组装客户数据
                $add = array(
                    'name'      => $response['leads_name'],
                    'mobile'    => $response['leads_tel'],
                    'sex'       => '',
                    'city'      => $response['leads_area'],
                    'money'     => $money,
                    'has_house' => "不详",
                    'has_car'   => "不详",
                    'has_baodan'    => "不详",
                    'has_gongjijin' => "不详",
                    'has_weilidai'  => "不详",
                    'has_nasui'     => "不详",
                    'channel'       => '广点通',
                    'create_time'   => time(),
                    'remark'        => $response['bundle'],
                    'is_repeat'     => 0,
                    'status'        => 0
                );
                //下面判断有没有重复手机号的订单
                $oldFirstCustomer = M('first_customer')->where(array('mobile'=>$response['leads_tel']))->order('id desc')->find();
                if ($oldFirstCustomer) {
                    //是否重复分配
                    $add['is_repeat'] = 1;
                    //一天内重复申请的用户标识为申请频繁客户
                    $time_diff = $oldFirstCustomer['create_time'] + 24 * 60 * 60 - time();
                    if ($time_diff > 0) {
                        $add['status'] = 4;//申请频繁客户
                    }
                }
                //将数据写入数据库
                M('first_customer')->add($add);
            }
        }
    }

    /**
     * 百度信息流数据推入接口
     * http://www.zongft.com/api/index/add_baidu_data.html?key=oDYYJQGeT6HVx81mowPv3JTIXU5yTe97WD0wvaZvtU7AUuAzIY
     */
    public function add_baidu_data()
    {
        $jsonStr = file_get_contents("php://input");
        if (!empty($jsonStr)) {
            $response = json_decode($jsonStr, true);
            if ($response) {
                //组装客户数据
                $add = array(
                    'name'      => $response['name'],
                    'mobile'    => $response['mobile'],
                    'sex'       => '',
                    'city'      => $response['city'],
                    'money'     => $response['money'],
                    'has_house' => "不详",
                    'has_car'   => "不详",
                    'has_baodan'    => "不详",
                    'has_gongjijin' => "不详",
                    'has_weilidai'  => "不详",
                    'has_nasui'     => "不详",
                    'channel'       => '百度信息流',
                    'create_time'   => time(),
                    'remark'        => $response['remark'],
                    'is_repeat'     => 0,
                    'status'        => 0
                );
                //下面判断有没有重复手机号的订单
                $oldFirstCustomer = M('first_customer')->where(array('mobile'=>$response['mobile']))->order('id desc')->find();
                if ($oldFirstCustomer) {
                    //是否重复分配
                    $add['is_repeat'] = 1;
                    //一天内重复申请的用户标识为申请频繁客户
                    $time_diff = $oldFirstCustomer['create_time'] + 24 * 60 * 60 - time();
                    if ($time_diff > 0) {
                        $add['status'] = 4;//申请频繁客户
                    }
                }
                //将数据写入数据库
                $return = M('first_customer')->add($add);
                $return ? $this->setjsonReturn('数据推入成功'):$this->errorjsonReturn('数据推入失败');
            }
        }
    }
}