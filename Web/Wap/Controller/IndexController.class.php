<?php
namespace Wap\Controller;
use Think\Controller;
use Message\Logic\MessageLogic;

class IndexController extends Controller {

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
        //判断是不是手机端访问
        if (!isMobile()) {
           exit("<!DOCTYPE html>
                <html>
                <head>
                    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, user-scalable=0\">
                    <title>抱歉，出错了</title>
                    <meta charset=\"utf-8\">
                    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, user-scalable=0\">
                    <link rel=\"stylesheet\" type=\"text/css\" href=\"https://res.wx.qq.com/open/libs/weui/0.4.1/weui.css\">
                </head>
                <body>
                <div class=\"weui_msg\">
                    <div class=\"weui_icon_area\">
                        <i class=\"weui_icon_info weui_icon_msg\"></i>
                    </div>
                    <div class=\"weui_text_area\">
                        <h4 class=\"weui_msg_title\">请在手机客户端打开链接</h4>
                    </div>
                </div>
                </body>
                </html>");
        }
    }

    /**
     * 进入首页
     * 获取客户列表
     */
    public function index()
    {
        if(!session('wap_user_info')){
            $this->redirect('/wap/index/login');
        }
        //获取用户信息
        $user = session('wap_user_info');
        //获取我的客户列表
        $where = array(
            'is_delete' => 0,
            'user_id' => $user['id']
        );
        $order = 'communicate_time asc,change_user_time desc,create_time desc';
        $field = 'id,name,city,money,mobile,communicate_time,has_house,has_car,has_baodan,has_gongjijin,has_weilidai';
        $list = M('order')->field($field)->where($where)->Page(1,10)->order($order)->select();
        if ($list && count($list)>0) {
            foreach ($list as $key=>$val) {
                $list[$key]['phone'] = substr_replace($val['mobile'],'****',3,4);
            }

        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 获取下一页客户列表
     */
    public function getNextData()
    {
        //获取用户信息
        $user = session('wap_user_info');
        //获取我的客户列表
        $where = array(
            'is_delete' => 0,
            'user_id' => $user['id']
        );
        $page = I('post.page') > 0 ? I('post.page')+1 : 1;
        $order = 'communicate_time asc,change_user_time desc,create_time desc';
        $field = 'id,name,city,money,mobile,communicate_time,has_house,has_car,has_baodan,has_gongjijin,has_weilidai';
        $list = M('order')->field($field)->where($where)->Page($page,10)->order($order)->select();
        if ($list && count($list)>0) {
            $html = '';
            foreach ($list as $key=>$val) {
                if ($val['communicate_time'] > 0) {
                    $communicate_time = "最后沟通时间 ".date('Y-m-d H:i', $val['communicate_time']);
                } else {
                    $communicate_time = "尚未拨打过电话";
                }
                $html .= '<li data-v-6372e806="" data-v-3a05a57a="" class="house-li por">
                            <div data-v-6372e806="" class="flex"
                                 style="border-bottom: 0.02rem solid rgb(238, 238, 238);">
                                <div data-v-6372e806="" class="right-sec">
                                    <div data-v-6372e806="" class="pra1">
                                        '.$val["name"].'
                                        <span data-v-6372e806="" class="pra1" style="padding-left: 10px;"> '.$val["city"].' </span>
                                        <span data-v-6372e806="" class="pra1" style="padding-left: 10px;"> '.$val["money"].'元 </span>
                                    </div>
                                    <div data-v-6372e806="" class="praliu oh" style="display: none;"></div>
                                    <div data-v-6372e806="" class="pra2 oh">
                                        <div data-v-6372e806="" class="fl">
                                            <span data-v-6372e806="">'.$communicate_time.'</span>
                                        </div>
                                        <a href="tel:'.$val["mobile"].'">
                                            <div data-v-6372e806="" class="fr"></div>
                                        </a>
                                    </div>
                                    <div data-v-6372e806="" class="oh pra3">
                                        <div data-v-6372e806="" class="fl">
                                            <span data-v-6372e806="">'.$val["has_house"].'房 '.$val["has_car"].'车 '.$val["has_baodan"].'保单 '.$val["has_gongjijin"].'公积金 '.$val["has_weilidai"].'微粒贷</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>';
            }
            $this->setjsonReturn($html);
        }else {
            $this->errorjsonReturn('没有更多数据了');
        }
    }


    /**
     * 登录界面
     */
    public function login()
    {
        if(session('wap_user_info')){
            $this->redirect('/wap/index/index');
        }
        $this->display();
    }

    /**
     * 登录动作
     */
    public function doLogin()
    {
        $data = I('post.');
        if (empty($data['mobile'])) {
            $this->errorjsonReturn('请输入手机号码');
        }
        if (empty($data['pass'])) {
           //使用验证码登录
            $info = M('phone_code')->field('id,code')->where(array('mobile'=>$data['mobile'],'code'=>$data['code'],'used'=>0,'create_time'=>array('gt',time()-300)))->find();
            if (empty($info)){
                $this->errorjsonReturn('验证码已过期或有误');
            }
            M('phone_code')->where(array('id'=>$info['id']))->save(array('used'=>1));
            $user = M('user')->field('id,mobile')->where(array('mobile'=>$data['mobile'],'delete'=>1))->find();
            if (empty($user)){
                $this->errorjsonReturn('用户不存在');
            }
        } else {
            //使用密码登录
            $user = M('user')->field('id,pwd')->where(array('mobile'=>$data['mobile'],'delete'=>1))->find();
            if (empty($user)){
                $this->errorjsonReturn('用户不存在');
            }
            if (md5($data['pass']) != $user['pwd']){
                $this->errorjsonReturn('密码错误');
            }
        }
        //记录登录日志
        $log = array(
            'user_id' => $user['id'],
            'ip' => get_client_ip(),
            'action' => 1,
            'from' => '手机端密码登录'
        );
        M('user_login_log')->add($log);
        session('wap_user_info', $user);
        $this->setjsonReturn('登录成功');
    }

    /**
     * 发送验证码
     */
    public function send_code()
    {
        $post = I('post.');
        if(!$post['mobile']){
            $this->errorjsonReturn('请填写手机号');
        }
        if(!is_phone($post['mobile'])){
            $this->errorjsonReturn('手机号码格式错误');
        }
        //判断手机号是否注册，未注册不发送短信
        $info = M('user')->field('id,mobile')->where(array('mobile'=>$post['mobile']))->find();
        if (empty($info)) {
            $this->errorjsonReturn('该手机号尚未登记');
        }
        $return = (new MessageLogic())->addMessage('mobile_code',$post['mobile']);
        $return===true?$this->setjsonReturn('发送成功'):$this->errorjsonReturn('发送失败');
    }

    /**
     * 退出操作
     */
    public function loginout()
    {
        session(null);
        $this->redirect('/wap/index/index');
    }

    /**
     * 返回正确的格式的json数据
     */
    private function setjsonReturn($value, $key=null)
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
    private function errorjsonReturn($value)
    {
        $this->ret['errNum']=-1;
        $this->ret['errMsg']=$value;
        $this->ajaxReturn ($this->ret,'JSON');
    }
}