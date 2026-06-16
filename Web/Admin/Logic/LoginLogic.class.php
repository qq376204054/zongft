<?php
namespace Admin\Logic;

use Admin\Model\PhoneCodeModel;

class LoginLogic
{
    /**
     * 登录操作逻辑
     * @param $name
     * @param $password
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function doLoginLogic($mobile,$password,$mobile2,$type,$code){
        if($type=='pwd'){
            $adminInfo = M('user')->where(array('mobile'=>$mobile,'delete'=>1))->find();
            if(empty($adminInfo)){return '用户不存在';}
            if(md5($password)!=$adminInfo['pwd']){return '密码错误';}
        }else{
            //校验验证码,需要校验
            $code = (new PhoneCodeModel())->getCode($mobile2,$code);
            if($code===false){return '验证码已过期或有误';}
            $adminInfo = M('user')->where(array('mobile'=>$mobile2,'delete'=>1))->find();
            if(empty($adminInfo)){return '用户不存在';}
        }

        //登录日志
        $addlog = array();
        $addlog['user_id'] = $adminInfo['id'];
        $addlog['ip'] = get_client_ip();
        $addlog['action'] = 1;
        $addlog['from'] = $type=='pwd'?'密码登录':'手机验证码登录';
        M('user_login_log')->add($addlog);

        //下面找出这个人所在的组织详情
        $branchInfo = M('company_branch')->where(array('id'=>$adminInfo['branch_id']))->find();
        $adminInfo['branch_name']=$branchInfo['name'];
        $adminInfo['city']=$branchInfo['city'];
        $roleInfo = M('user_roles')->where(array('id'=>$adminInfo['role_ids']))->find();

        //下面分析用户的权限
        $adminInfo['look_user_phone'] = $roleInfo['look_user_phone'];
        $adminInfo['look_customer_phone'] = $roleInfo['look_customer_phone'];
        $adminInfo['data_auth'] = $roleInfo['data_auth'];
        $adminInfo['role_names'] = $roleInfo['name'];
        return $adminInfo;
    }
}