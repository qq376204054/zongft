<?php
namespace Admin\Model;
use Think\Model;

class CompanyModel extends Model
{
    /**
     * 根据post志添加或者修改数据数据
     * @param $post
     * @return int|string  正确返回添加的id
     */
    public function saveCompanyModel($post){
        if(isset($post['id'])&&$post['id']){
            return $this->save($post,['id' => $post['id']])!==false?true:false;
        }else{
            return $this->add($post);
        }
    }

    /**
     * 获取用户列表逻辑
     * @param $get
     * @param int $perpage
     * @param int $p
     * @return \think\Paginator
     */
    public function getCompanyListModel($where,$p=1,$perpage=10){
        $count=$this->where($where)->count();
        $list = $this->where($where)->field('id,name,status,logo')->limit(($p-1)*$perpage.",".$perpage)->select();
        return array('count'=>$count, 'p'=>$p, 'perpage'=>$perpage, 'list'=>$list);
    }

    /**
     * 通过用户的id数组获取公司的信息
     * @return array
     */
    public function getCompanyInfosByUserIds($user_ids){
        if(!$user_ids){return array();}
        $where['uc.user_id']=array('in',$user_ids);
        $where['uc.status']=array('neq',3);
        $where['c.status']=array('neq',2);
        $companyInfos=M('user_company')
            ->alias('uc')
            ->join('__COMPANY__ as c ON uc.company_id = c.id','LEFT')
            ->where($where)
            ->getField('uc.user_id,c.name as company_name',true);
        return $companyInfos;
    }

    /**
     * 企业用户注册的时候创建公司
     * @param $user_id
     * @param $company_name
     * @return bool
     */
    public function createCompanyByUserRegisterLogic($user_id,$city,$company_name){
        if(!$user_id||!$company_name){return false;}
        $data['name']=$company_name;
        $data['city']=$city;
        $data['status']=3;
        $data['create_time']=time();
        $addReturn=$this->add($data);
        if($addReturn){
            $addUserToCompany=(new UserCompanyModel())->addUserToCompany($user_id,$addReturn,1);
            if($addUserToCompany!==true){return false;}
        }
        return true;
    }
}