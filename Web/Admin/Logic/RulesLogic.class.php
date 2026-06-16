<?php
namespace Admin\Logic;

use Admin\Model\UserRulesModel;

class RulesLogic
{
    /**
     * 获取用户列表逻辑
     * @param $search
     * @return \think\Paginator
     */
    public function getRulesListLogic($search){
        $get=array('name'=>$search['name'], 'title'=>$search['title'],'status'=>$search['status'],'perpage'=>$search['perpage'],'p'=>$search['p']);
        foreach($get as &$v){$v=trim($v);}
        $where = '1=1';
        if(trim($get['name'])){$where .= ' and name like "%'.trim($get['name']).'%"';}
        if(trim($get['title'])){$where .= ' and title like "%'.trim($get['title']).'%"';}
        //状态默认查找没有删除的
        if(in_array($get['status'],array(1,2))){$where .= ' and `status` = '.$get['status'];}else{$where .= ' and `status` in (1,2)';}
        $perpage=$get['perpage']?$get['perpage']:10;
        $p=$get['p']?$get['p']:1;
        return (new UserRulesModel())->getRulesListModel($where,$p,$perpage);
    }
}