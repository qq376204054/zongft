<?php
namespace Admin\Model;

class AuthModel
{
    /**
     * 加密用户的手机号
     */
    public function doAuthToUserPhone($look_user_phone,$phone){
        if($look_user_phone!=1){
            $phone = substr_replace($phone,'****',3,4);
        }
        return $phone;
    }

    /**
     * 加密商机的手机号
     */
    public function doAuthToFirstCustomerPhone($look_customer_phone,$phone){
        if($look_customer_phone!=1){
            $phone = substr_replace($phone,'****',3,4);
        }
        return $phone;
    }


    /**
     * 是否要加密客户的手机号
     * @param $look_customer_phone  手机号查看权限
     * `look_customer_phone` （1-能看所有展示的客户的  2-只能看自己负责的客户的  3-不能看到客户手机号）',
     * @param $phone                手机号
     * @param $my_id                我的id
     * @param $sale_id              客户的业务员id
     * @return mixed
     */
    public function doAuthToCustomerPhone($look_customer_phone,$phone,$my_id,$sale_id){
        if($look_customer_phone==1){
            return $phone;
        }elseif($look_customer_phone==2){
            if($my_id==$sale_id){
                return $phone;
            }else{
                $phone = substr_replace($phone,'****',3,4);
            }
        }else{
            $phone = substr_replace($phone,'****',3,4);
        }
        return $phone;
    }
}