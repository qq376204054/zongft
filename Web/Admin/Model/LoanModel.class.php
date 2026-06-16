<?php
namespace Admin\Model;
use Think\Model;

class LoanModel extends Model
{
    /**
     * 删除放款信息
     * @param $id
     * @return bool|string
     */
    public function deleteModel($id){
        $has=$this->where(array('id'=>$id))->count();
        if($has==0){return '非法操作';}
        $return=$this->where(array('id'=>$id))->save(array('is_delete'=>1));
        if($return===false){
            return '操作失败';
        }
        return true;
    }

    /**
     * 获取订单的放款信息
     * @param $order_id
     * @return mixed
     */
    public function getListByOrderModel($order_id){
        $where=array();
        $where['order_id']=$order_id;
        $where['is_delete']=0;
        return $this->where($where)->order('id desc')->select();
    }
}