<?php
namespace Admin\Model;
use Think\Model;

class MenuModel extends Model
{


    /**
     * 获取一个菜单的子目录
     */
    public function getSubMenu($menuid){
        if(!$menuid){return array();}
        $list=M('menu')->where(array('pid'=>$menuid,'is_delete'=>0))->field('id,name,module_name,action_name,data')->select();
        return $list;
    }

}