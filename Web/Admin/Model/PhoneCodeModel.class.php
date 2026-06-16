<?php
namespace Admin\Model;
use Think\Model;

class PhoneCodeModel extends Model
{
    /**
     * 获取相应手机的验证码
     * @param $mobile
     * @return bool|mixed
     */
    public function getCode($mobile,$code){
        $result = $this->where(array('mobile'=>$mobile,'code'=>$code,'used'=>0,'create_time'=>array('gt',time()-300)))->find();
        if(!$result){return false;}
        $this->where(array('id'=>$result['id']))->save(array('used'=>1));
        return $result;
    }

    /**
     * 使用手机验证码
     * @param $mobile
     * @return bool
     */
    public function useCode($mobile){
        $result=$this->where(array('mobile'=>$mobile))->save(array('used'=>1));
        if($result===false){return false;}
        return true;
    }
}