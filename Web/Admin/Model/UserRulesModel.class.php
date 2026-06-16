<?php
namespace Admin\Model;
use Think\Model;

class UserRulesModel extends Model
{
    /**
     * 获取权限列表
     * @param $where
     * @param int $p
     * @param int $perpage
     * @return array|mixed
     */
    public function getRulesListModel($where,$p=1,$perpage=10){
        $count=$this->where($where)->count();
        $list = $this->where($where)->field('id,name,title,status,condition,remark')
            ->limit(($p-1)*$perpage.",".$perpage)->select();
        return array('count'=>$count, 'p'=>$p, 'perpage'=>$perpage, 'list'=>$list);
    }

    /**
     * 根据post志添加或者修改数据数据
     * @param $post
     * @return int|string  正确返回添加的id
     */
    public function saveUserRulesModel($post){
        if(isset($post['id'])&&$post['id']){
            return $this->save($post,['id' => $post['id']])!==false?true:false;
        }else{
            return $this->add($post);
        }
    }
}