<?php
namespace Admin\Model;
use Admin\Logic\CacheLogic;
use Think\Model;

class AccountModel extends Model
{
    /**
     * 添加账单
     * @param $post 添加的数据
     * @param $user_id  操作人
     * @return bool|string
     */
    public function addModel($post,$user_id){
        if(!$post['type']||!$post['order_id']){return '非法操作';}
        if(!$post['title']){return '请选择账目类别';}
        if(!$post['money']){return '请填写金额';}
        if(!$post['pay_type']){return '请选择支付类型';}
        if(!$post['pay_time']){return '请选择支付时间';}
        $orderInfo= M('order')->where(array('is_delete'=>0,'id'=>$post['order_id']))->find();
        if(!$orderInfo){return '非法操作';}
        //只能订单归属者操作
        //if($orderInfo['user_id']!=$user_id){return '无权操作';}
        $add = array(
            'title'=>$post['title'],
            'money'=>$post['money'],
            'type'=>$post['type'],
            'user_id'=>$orderInfo['user_id'],//佣金归属于订单持有者而不是当前操作者
            'order_id'=>$post['order_id'],
            'create_time'=>time(),
            'pay_type'=>$post['pay_type'],
            'pay_time'=>strtotime($post['pay_time']),
            'pay_user_name'=>$post['pay_user_name'],
            'pay_account'=>$post['pay_account']
        );
        return M('account')->add($add)?true:'添加失败';
    }

    /**
     * 删除账单
     * @param $id  账单id
     * @param $user_id  删除人
     * @return bool|string
     */
    public function deleteModel($id,$user_id){
        if(!$id){return '请选择需删除项';}

        $info=M('account')->alias('a')->join('LEFT JOIN __ORDER__ o ON a.order_id = o.id')
            ->where(array('a.is_delete'=>0,'o.is_delete'=>0,'a.id'=>$id))
            ->field('a.order_id,a.status,o.user_id')->find();

        if(!$info){return '非法操作';}
//        if($info['user_id']!=$user_id){return '无权操作';}

        $result=M('account')->where(array('id'=>$id))->save(array('is_delete'=>1));
        return $result===false?'删除失败':true;
    }

    /**
     * 账目统计求和
     * @param $user_ids  统计的人员
     * @param $start_time  开始时间
     * @param $end_time  结束时间
     * @return array
     */
    public function sumAccountByUserModel($user_ids,$start_time,$end_time){
        if(!$user_ids){return array();}
        $where=array();
        $where['user_id']=array('in',$user_ids);
        $where['pay_time'][]=array('egt',strtotime($start_time));
        $where['pay_time'][]=array('elt',strtotime($end_time));
        //下面统计账目的种类
        $config=(new CacheLogic())->get_all_config();

        $result=$this->where($where)->field('count(*) as count,sum(money) as money,user_id,title,type')
            ->group('user_id,title')->select();

        $data=array();
        foreach($result as $value){
            $data[$value['user_id']][$value['title']] =$value['money'];
        }

        //下面封装数据
        $count=array();
        $data1=array();
        foreach($user_ids as $user_id){
            $data1[$user_id]=array();
            foreach($config['account_income_type'] as $value){
                $count[$value]=(int)$count[$value]+(int)$data[$user_id][$value];
                $data1[$user_id]['account_income'][$value]=$data[$user_id][$value]?$data[$user_id][$value]:0;
            }
            foreach($config['account_pay_type'] as $value){
                $count[$value]=(int)$count[$value]+(int)$data[$user_id][$value];
                $data1[$user_id]['account_pay'][$value]=$data[$user_id][$value]?$data[$user_id][$value]:0;
            }
        }
        return array('data'=>$data1,'count'=>$count);
    }
}