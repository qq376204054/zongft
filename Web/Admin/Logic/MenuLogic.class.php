<?php
namespace Admin\Logic;

/**
 * 模型逻辑
 * Class Column
 * @package app\admin\logic
 */
class Menu
{
    /**
     * 删除导航逻辑
     * @param $id
     * @return bool|string
     */
    public function delectMenuLogic($id){
        if(M('menu')->where(array('fatherid'=>$id,'status'=>1))->count()){
            return '存在子导航,不能删除';
        }else{
            $where['id']=$id;
            $where['status']=1;
            if(M('menu')->where($where)->save(['status' => 2])!==false){
                return true;
            }else{
                return '删除失败';
            }
        }
    }
}