<?php
namespace Admin\Model;
use Admin\Logic\CacheLogic;
use Think\Model;

/**
 * 合作渠道model
 * Class CooperationModel
 * @package Admin\Model
 */
class CooperationModel extends Model
{
    /**
     * 删除合作方或者渠道
     * @param $ids 合作方或者渠道的 id组
     * @return bool
     */
    public function deleteModel($ids=array()){
        if(!$ids){return '请选择需删除项';}

        //下面分析有没有删除渠道的，有的话需要判断渠道下面有没有客户
        $hasChannel=$this->where(array('id'=>array('in',$ids),'pid'=>array('neq',0),'is_delete'=>0))->getField('name',true);
        if($hasChannel&&($this->channelHasCustomerModel($hasChannel)===true)){
            return '有渠道下存在客户，请先转移客户至其他渠道';
        }
        //下面清除接口缓存
        (new CacheLogic())->clear_channel_key_manger();

        $result=$this->where(array('id'=>array('in',$ids)))->save(array('is_delete'=>1));
        return $result===false?'删除失败': true;
    }

    /**
     * 添加修改渠道接口
     * @param $post
     * @return bool|string
     */
    public function addChannelModel($post){
        if(!$post['name']){return '请填写渠道名称';}
        if($this->hasChannelModel($post['name'],$post['id'])){return '渠道已经存在';}
        //下面清除接口缓存
        (new CacheLogic())->clear_channel_key_manger();

        if($post['id']){//修改的时候
            //如果修改了渠道名，判断有没有这个渠道的客户，有的话不能更改
            $channel=$this->where(array('id'=>$post['id']))->getField('name');
            if(!$channel){return '非法操作';}
            if(($channel!=$post['name'])&&($this->channelHasCustomerModel($channel)===true)){
                return '渠道下存在客户，请先转移客户至其他渠道';
            }
            if($this->where(array('id'=>$post['id']))->save($post)===false){
                return '修改失败';
            }
        }else{//添加的时候
            //没有默认渠道就设置默认渠道
            $has_default=$this->where(array('pid'=>$post['pid'],'is_delete'=>0,'is_default'=>1))->count();

            $post['is_default']=$has_default>0?0:1;
            $post['create_time']=time();
            if(!$this->add($post)){
                return '添加失败';
            }
        }
        return true;
    }

    /**
     * 判断渠道是否存在
     * @param $channel
     * @param $id
     * @return bool
     */
    public function hasChannelModel($channel,$id=false){
        if($id){
            $count = $this->where(array('name'=>$channel,'id'=>array('neq',$id)))->count();
        }else{
            $count = $this->where(array('name'=>$channel))->count();
        }
        return $count>0?true:false;
    }

    /**
     * 渠道下面是否有客户
     * @param $channel
     * @return bool
     */
    public function channelHasCustomerModel($channel){
        if(!$channel){return false;}
        if(!is_array($channel)){$channels[]=$channel;}else{$channels=$channel;}
        $count=M('order')->where(array('channel'=>array('in',$channels)))->count();
        return $count>0?true:false;
    }

    /**
     * 获取以什么为主键的渠道对应数组
     * @return array
     */
    public function getAllUtmSourceConfig(){
        $sql_get=$this->where(array('is_delete'=>0))->field('id,pid,key,name,is_default')->select();
        $return=array();
        foreach($sql_get as $value){
            if($value['pid']==0){
                $sub=array();
                $sub['name']=$value['name'];
                foreach($sql_get as $value11){
                    //设置子渠道
                    if($value11['pid']==$value['id']){$sub['child'][]=$value11['name'];}
                    //设置默认渠道
                    if(($value11['pid']==$value['id'])&&($value11['is_default']==1)){$sub['default']=$value11['name'];}
                }
                $return[$value['key']]=$sub;
            }
        }
        return $return;
    }
}