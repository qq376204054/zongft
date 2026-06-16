<?php
namespace Admin\Model;
use Admin\Logic\CacheLogic;
use Think\Model;

/**
 * 推广客户的model
 * Class FirstCustomerModel
 * @package Admin\Model
 */
class FirstCustomerModel extends Model
{
    /**
     * 统计的model
     */
    public function report($get){
        $where=array();
        if(!$get['city']){return '请选择城市';}
        if(!$get['utm_soure']){return '请选择渠道';}

        if($get['time_start']){$where['create_time'][]=array('egt',strtotime($get['time_start']));}
        if($get['time_end']){$where['create_time'][]=array('lt',strtotime($get['time_end'])+24*60*60);}

        if($get['money_start']){$where['money'][]=array('egt',$get['money_start']);}
        if($get['money_end']){$where['money'][]=array('lt',$get['money_end']);}

        $where['city']=array('in',$get['city']);
        $where['channel']=array('in',$get['utm_soure']);

        $result=$this->where($where)->field('is_repeat,city,count(*) as num')->group('city,is_repeat')->select();
        //下面重新封装数组(这里面都是城市的)
        $list=array();
        foreach($get['city'] as $city){
            $list[$city]=array('city'=>$city,'repeat'=>0,'no_repeat'=>0);
            foreach($result as $value){
                if($value['city']==$city){
                    if($value['is_repeat']==1){
                        $list[$city]['repeat']=$value['num'];
                    }
                    if($value['is_repeat']==0){
                        $list[$city]['no_repeat']=$value['num'];
                    }
                }
            }
        }
        //如果搜索中包含其他，就要找出非固定的申请城市的客户
        if(in_array('其他',$get['city'])){
            $all_apply_citys=(new CacheLogic())->get_all_config()['customer_apply_city'];
            foreach($all_apply_citys as $key=>$value){
                if($value=='其他'){unset($all_apply_citys[$key]);}
            }
            $where['city']=array('not in',$all_apply_citys);
            $other_result=$this->where($where)->field('is_repeat,count(*) as num')->group('is_repeat')->select();
            $list['其他']['city']='其他';
            $list['其他']['repeat']=0;
            $list['其他']['no_repeat']=0;
            foreach($other_result as $value){
                if($value['is_repeat']==1){
                    $list['其他']['repeat']=$value['num'];
                }
                if($value['is_repeat']==0){
                    $list['其他']['no_repeat']=$value['num'];
                }
            }
        }
        return $list;
    }
}