<?php
namespace Admin\Model;
use Admin\Logic\TreeLogic;
use Think\Model;

class UserModel extends Model
{

    /**
     * 获取某个公司的所有员工
     * @param $company_id
     * @return mixed
     */
    public function getCompanyUserId($company_id){
        $user_ids = M('user')->where(array('company_id'=>$company_id))->getField('id',true);
        return $user_ids;
    }

    /**
     * 获取我的所有下属id
     * @param $user_id
     * @return array|mixed
     */
    public function getMyAllSubUserId($userInfo){
        $sub_user_ids = array();
        //如果不是管理员只查询自己数据
        if($userInfo['is_admin']!=1){
            $sub_user_ids[] = $userInfo['id'];
            return $sub_user_ids;
        }
        $result = M('company_branch')->where(array('is_delete'=>0))->field('id,pid')->select();
        $tree = new TreeLogic();
        $tree->init($result);
        $menu_list = $tree->get_tree_array($userInfo['branch_id']);
        $all_sub_branch_ids = array_column($menu_list,'id');
        $all_sub_branch_ids[] = $userInfo['branch_id'];
        if(!$all_sub_branch_ids){return array();}

        //获取这些组织下面的用户ids,并且没有删除的
        $sub_user_ids = M('user')->where(array('branch_id'=>array('in',$all_sub_branch_ids),'delete'=>1))->field('id')->select();
        $sub_user_ids = array_column($sub_user_ids,'id');
        return $sub_user_ids;
    }

    /**
     * 下面查询自己所属部门
     * @param $user_ids
     * @return array|mixed
     */
    public function getBranchInfo($user_ids){
        if(!$user_ids){return array();}
        $allUserBranch=array();
        $selectUserName=M()->query('SELECT b.id as user_id,c.name,c.id,b.is_admin from '.C('DB_PREFIX').'user b
                               LEFT JOIN '.C('DB_PREFIX').'company_branch c ON b.branch_id=c.id WHERE b.id IN
                               ('.implode(',',$user_ids).') AND c.is_delete=0');
        foreach($selectUserName as $value){
            $allUserBranch[$value['user_id']]=$value;
        }
        return $allUserBranch;
    }

    /**
     * 获取用户组的用户信息
     * @param $user_ids
     * @return array|mixed
     */
    public function getUsersInfo($user_ids){
        if(!$user_ids){return array();}
        $info=$this->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile,delete',true);
        return $info;
    }
}