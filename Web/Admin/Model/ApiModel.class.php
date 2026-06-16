<?php
namespace Admin\Model;
use Think\Model;

class ApiModel extends Model
{
    /**
     * 添加修改密匙
     * @param $id
     * @param $key
     * @param $name
     * @return bool|string
     * @throws \think\Exception
     */
    public function saveApiKey($id,$key,$name){
        S('All_Api_Key',NULL);
        if(!$key||!$name){return '请完善数据';}
        $add['name']=$name;
        if($id){
            $oldKey=M('api')->where(array('id'=>$id))->getField('key');
            $saveReturn=M('api')->where(array('id'=>$id))->field('name')->save($add);
            if($saveReturn===false){return '修改失败';}
            $saveAllKey=M('api')->where(array('key'=>$oldKey))->field('key')->save(array('key'=>$key));
            if($saveAllKey===false){return '修改失败';}
        }else{
            $add['key']=$key;
            $add['create_time']=time();
            $addReturn=M('api')->field('key,name,create_time')->add($add);
            if(!$addReturn){return '修改失败';}
        }
        return true;
    }

    /**
     * 添加修改密匙下的链接
     * @param $id
     * @param $key
     * @param $name
     * @param $action
     * @return bool|string
     * @throws \think\Exception
     */
    public function saveApiKeyUrl($id,$key,$name,$action,$type,$remark,$field){
        S('All_Api_Key',NULL);
        if(!$key||!$name||!$action){return '请完善数据';}
        $add['key']=$key;
        $add['name']=$name;
        $add['action']=$action;
        $add['type']=$type;
        $add['remark']=$remark;
        //下面包装field配置
        $arr=array();
        foreach($field['key'] as $k=>$v){
            if(trim($v)!=''){
                $arr[$v]=array($field['name'][$k],$field['value'][$k]);
            }
        }
        $add['config']=json_encode($arr);
        if($id){
            $saveReturn=M('api')->where(array('id'=>$id))->field('key,name,action,type,remark,config')->save($add);
            $addReturn=$saveReturn===false?false:true;
        }else{
            $add['create_time']=time();
            $addReturn=M('api')->field('key,name,action,type,remark,config,create_time')->add($add);
        }
        return $addReturn?true:'添加失败';
    }
}