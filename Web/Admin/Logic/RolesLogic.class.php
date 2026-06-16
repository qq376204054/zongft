<?php
namespace Admin\Logic;

use Admin\Model\UserRolesModel;

class RolesLogic
{
    /**
     * 获取用户列表逻辑
     * @param $search
     * @return \think\Paginator
     */
    public function getRolesListLogic($search){
        $get=array('type'=>$search['type'], 'status'=>$search['status'],'name'=>$search['name'],'perpage'=>$search['perpage'],'p'=>$search['p']);
        foreach($get as &$v){$v=trim($v);}
        $where = '1=1';
        if(trim($get['type'])){$where .= ' and type = "'.trim($get['type']).'"';}
        //状态默认查找没有删除的
        if(in_array($get['status'],array(1,2))){$where .= ' and `status` = '.$get['status'];}else{$where .= ' and `status` in (1,2)';}
        if(trim($get['name'])){$where .= ' and name like "%'.trim($get['name']).'%"';}
        $perpage=$get['perpage']?$get['perpage']:10;
        $p=$get['p']?$get['p']:1;
        return (new UserRolesModel())->getRolesListModel($where,$p,$perpage);
    }
}