<?php
namespace Admin\Model;
use Think\Model;

class UserCompanyModel extends Model
{
    /**
     * 修改用户的公司绑定
     * @param $user_id
     * @param $company_id
     * @return bool
     */
    public function saveCompanyForUserModel($user_id,$company_id,$operat_user_id){
        if(!$user_id||!$company_id||!$operat_user_id){return false;}
        $companyInfo=M('company')->where(array('id'=>$company_id))->getField('name');
        if(empty($companyInfo)){return false;}
        $add['user_id']=$user_id;
        $add['company_id']=$company_id;
        $add['operat_user_id']=$operat_user_id;
        $addReturn=$this->add($add);
        if(!$addReturn){return false;}
        if($this->where(array('user_id'=>$user_id,'id'=>array('neq',$addReturn)))->save(array('status'=>3))===false){return false;}
        return $companyInfo;
    }


    /**
     * 创建公司和员工关联
     * @param $user_id
     * @param $company_id
     * @param int $is_admin
     * @param int $operat_user_id
     * @return bool
     */
    public function addUserToCompany($user_id,$company_id,$is_admin=2,$operat_user_id=0){
        if(!$user_id||!$company_id||!in_array($is_admin,array(1,2))){return false;}
        if($operat_user_id==0){$operat_user_id=$user_id;}
        $add['user_id']=$user_id;
        $add['company_id']=$company_id;
        $add['status']=2;
        $add['operat_user_id']=$operat_user_id;
        $add['is_admin']=$is_admin;
        return $this->add($add)?true:false;
    }
}