<?php
namespace Admin\Model;
use Think\Model;

class UserRolesModel extends Model
{
    /**
     * 获取角色列表的逻辑
     * @param $where
     * @param int $perpage
     * @param int $p
     * @return \think\Paginator
     */
    public function getRolesListModel($where,$p=1,$perpage=10){
        $count=$this->where($where)->count();
        $list = $this->where($where)->field('id,name,type,status,remark')
            ->limit(($p-1)*$perpage.",".$perpage)->select();
        return array('count'=>$count, 'p'=>$p, 'perpage'=>$perpage, 'list'=>$list);
    }

    /**
     * 根据post志添加或者修改数据数据
     * @param $post
     * @return int|string  正确返回添加的id
     */
    public function saveUserRolesModel($post){
        if(isset($post['id'])&&$post['id']){
            return $this->save($post,['id' => $post['id']])!==false?true:false;
        }else{
            return $this->add($post);
        }
    }

    /**
     * 设置一个角色的权限
     * @param $roles_id
     * @param $rules_id_arr
     * @return bool
     */
    public function setRolesRules($roles_id,$rules_id_arr){
        if(!$roles_id){return false;}
        return $this->save(array('rules'=>implode(',',$rules_id_arr)),['id' => $roles_id])!==false?true:false;
    }
}