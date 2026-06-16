<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Admin\Logic\LoginLogic;
use Message\Logic\MessageLogic;

class LoginController extends JsonController
{
    /**
     * 登录界面
     * @return mixed
     */
    public function login()
    {
        $all_setting = (new CacheLogic())->get_all_setting();
        $this->assign('all_setting',$all_setting);
        if(session('user_info_new')){
            $this->redirect('index/index');
        }
        $this->display();
    }
    /**
     * 登录动作
     */
    public function doLogin(){
        //开始网站登录流程
        $post = I('post.');
        $all_setting = (new CacheLogic())->get_all_setting();
        if($post['type']=='pwd'){
            if($all_setting['login_type']==3){$this->error('登录方式有误！');}
            if(!$post['mobile'] || !$post['password'] ){
                $this->error('手机号或密码不能为空！');
            }
        }else{
            if($all_setting['login_type']==2){$this->error('登录方式有误！');}
            if(!$post['mobile2'] || !$post['code'] ){
                $this->error('手机号或验证码不能为空！');
            }
        }
        $result=(new LoginLogic())->doLoginLogic($post['mobile'],$post['password'],$post['mobile2'],$post['type'],$post['code']);
        if(is_array($result)){
            session('user_info_new',$result);
            //获取用户的菜单
            (new CacheLogic())->get_one_user_all_menu($result['id']);
            $this->success('登录成功',U('index/index'));
        }else{
            $this->error($result);
        }
    }
    /**
     * 退出操作
     */
    public function loginout(){
        S('AUTH_LIST_'.session('user_info_new.id'),NULL);//清除用户的权限缓存
        if(session('user_info_new.id')){
            //退出日志
            $addlog = array();
            $addlog['user_id'] = session('user_info_new.id');
            $addlog['ip'] = get_client_ip();
            $addlog['action'] = 2;
            M('user_login_log')->add($addlog);
        }
        //清除用户的菜单权限
        (new CacheLogic())->clear_one_user_all_menu(session('user_info_new.id'));
        session(null);
        $this->success('退出成功',U('index/index'));
    }

    /**
     * 发送短信二维码
     */
    public function post_code(){
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
}

