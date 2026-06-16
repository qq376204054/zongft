<?php
namespace Mobile\Controller;
use Message\Logic\MessageLogic;
use Admin\Model\PhoneCodeModel;
use Think\Verify;
use Think\Controller;

class ActionController extends Controller {

    /**
     * 默认返回信息
     */
    protected $ret = array('errNum'=>0, 'errMsg'=>'success', 'retData'=>array());

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        header("Access-Control-Allow-Origin: * ");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
        header('Access-Control-Allow-Headers:X-Requested-With,x-requested-with,content-type,accept,Content-Type, Accept,requesttype');
    }

    /**
     * 贷款申请提交
     */
    public function apply_mobile()
    {
        //数据处理
        $post = I('post.');
        foreach($post as $k=>$value){
            $post[$k] = trim($value);
        }
        //if($post['services']!='on'){$this->errorjsonReturn('请阅读并同意《用户服务与隐私条款》');}
        if(empty($post['name'])){$this->errorJsonReturn('请填写姓名');}
        if(empty($post['city'])){$this->errorJsonReturn('请选择地区');}
        if(empty($post['money'])){$this->errorJsonReturn('请填写金额');}
        if(empty($post['mobile'])){$this->errorJsonReturn('请填写手机号');}
        if(empty($post['channel'])){$this->errorJsonReturn('渠道标识不能为空');}
        if(!is_numeric($post['money'])){$this->errorJsonReturn('填写的金额格式错误');}
        if(!is_phone($post['mobile'])){$this->errorJsonReturn('手机号码格式错误');}
        //落地页1、16、17不验证短信验证码
        if ($post['channel'] != 1 && $post['channel'] != 16 && $post['channel'] != 17) {
            //短信验证码不能为空
            if (!$post['code']) {
                $this->errorjsonReturn('请填写短信验证码');
            }
            //短信验证码验证
            $codeVaild = (new PhoneCodeModel())->getCode($post['mobile'], $post['code']);
            if ($codeVaild===false) {
                $this->errorjsonReturn('验证码不正确或已过期');
            }
        }
        //实际金额
        $post['money'] = $post['money']*10000;
        //落地页渠道密钥
        $post['key'] = "FscMhkze3P5TLvV3nbDVWc2fssIkxaiCeTDPy0UjdSuZUDA4Sa";
        //请求API接口
        $res = post_url('http://'.$_SERVER['SERVER_NAME'].U('api/index/add'), $post);
        print_r($res);exit;
        if (!$res) {
            $this->errorJsonReturn('数据提交失败，请稍后再试');
        }
        //处理响应数据
        $return = json_decode($res, true);
        if ($res['errNum'] !== 0) {
            $this->errorJsonReturn('数据提交失败，请稍后再试');
        } else {
            //下面发送申请成功的短信
            //(new MessageLogic())->addMessage('apply_success',$post['mobile']);
            $this->successJsonReturn('数据提交成功，贷款经理将马上与您联系，请保证电话畅通');
        }
    }

    /**
     * 发送验证码
     */
    public function make_mobile_code()
    {
        $post = I('post.');
        if (!$post['mobile']) {
            $this->errorjsonReturn('请填写手机号');
        }
        if (!is_phone($post['mobile'])) {
            $this->errorjsonReturn('手机号码格式错误');
        }
        $return = (new MessageLogic())->addMessage('customer_apply',$post['mobile']);
        if ($return===true) {
            $this->successJsonReturn('短信发送成功');
        } else {
            $this->errorJsonReturn('短信发送失败');
        }
    }

     /**
     * 图片验证码 仅模板一使用
     */
    public function make_code()
    {
        $Verify = new Verify(array('fontSize' => 20, 'length' =>4, 'useCurve' => false, 'useNoise' => true, 'reset' => true));
        $Verify->entry("valid_code");
    }

    /**
     * 返回正确的格式的json数据
     */
    private function successJsonReturn($value, $key=null)
    {
        if (isset($key)) {
            $this->ret['retData'][$key]=$value;
        } else {
            $this->ret['retData']=$value;
        }
        $this->ajaxReturn ($this->ret,'JSON');
    }

    /**
     * 返回错误格式的json数据
     */
    private function errorJsonReturn($value)
    {
        $this->ret['errNum']=-1;
        $this->ret['errMsg']=$value;
        $this->ajaxReturn ($this->ret,'JSON');
    }
}