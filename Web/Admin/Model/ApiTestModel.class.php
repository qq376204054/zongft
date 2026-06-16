<?php
namespace Admin\Model;
use Think\Model;

class ApiTestModel extends Model
{
    /**
     * 获取接口日志列表逻辑
     * @param $where
     * @param $p
     * @param $perpage
     * @return mixed
     */
    public function getApiTestListModel($where,$p=1,$perpage=10,$order='id desc'){
        $count=$this->where($where)->count();
        $list = $this->where($where)->limit(($p-1)*$perpage.",".$perpage)->order($order)->select();
        foreach($list as &$value){
            $value['create_time']=date('Y-m-d H:i:s',time());
        }
        return array('count'=>$count, 'p'=>$p, 'perpage'=>$perpage, 'list'=>$list);
    }
}