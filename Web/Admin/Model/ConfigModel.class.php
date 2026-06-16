<?php
namespace Admin\Model;
use Think\Model;

class ConfigModel extends Model
{
    /**
     * 通过关键字获取一个配置的值
     * @param $id
     * @return array|bool|mixed
     */
    public function getConfigByNameModel($name){
        $configInfo=$this->where(array('name'=>$name,'is_delete'=>1))->find();
        if(empty($configInfo)){return false;}
        if($configInfo['type']==1){//如果是单个数据
            $return=$configInfo['value'];
        }elseif($configInfo['type']==2){//如果不是单个数据,就是json化的字符串
            $return=json_decode($configInfo['value'],true)?json_decode($configInfo['value'],true):array();
            $return=array_values($return);
        }else{
            $return=json_decode($configInfo['value'],true)?json_decode($configInfo['value'],true):array();
        }
        return $return;
    }

    /**
     * 批量获取关键字的配置的值
     * @param $id
     * @return array|bool|mixed
     */
    public function getConfigByNamesModel($names){
        $configInfo=$this->where(array('name'=>array('in',$names),'is_delete'=>1))->getField('name,type,value');
        if(empty($configInfo)){return false;}
        $all_return=array();
        foreach($configInfo as $k=>$oneConfig){
            if($oneConfig['type']==1){//如果是单个数据
                $all_return[$k]=$oneConfig['value']?$oneConfig['value']:0;
            }elseif($configInfo['type']==2){//如果不是单个数据,就是json化的字符串
                $all_return[$k]=json_decode($oneConfig['value'],true)?json_decode($oneConfig['value'],true):array();
                $all_return[$k]=array_values($all_return[$k]);
            }else{
                $all_return[$k]=json_decode($oneConfig['value'],true)?json_decode($oneConfig['value'],true):array();
            }
        }
        return $all_return;
    }

    /**
     * 获取一个配置的值
     * @param $id
     * @return array|bool|mixed
     */
    public function getConfigByIdModel($id){
        $configInfo=$this->where(array('id'=>$id,'is_delete'=>1))->find();
        if(empty($configInfo)){return false;}
        if($configInfo['type']==1){//如果是单个数据
            $return=$configInfo['value'];
        }elseif($configInfo['type']==2){//如果不是单个数据,就是json化的字符串
            $return=json_decode($configInfo['value'],true)?json_decode($configInfo['value'],true):array();
            $return=array_values($return);
        }else{
            $return=json_decode($configInfo['value'],true)?json_decode($configInfo['value'],true):array();
        }
        return $return;
    }

    /**
     * 修改配置的值
     * @param $value
     * @return bool
     */
    public function editOneConfigById($id,$value,$key=''){
        if(!$id){return false;}
        if(!$key){
            if(is_array($value)){
                //下面剔除空格
                $value=array_filter($value);
                foreach($value as $k=>$v){
                    if(trim($v)){$value[$k]=trim($v);}else{unset($value[$k]);}
                }
                $value=array_values($value);
            }
        }else{
            foreach($key as $k=>$item){$key[$k]=trim($item);}
            foreach($value as $k=>$item){$value[$k]=trim($item);}
            for ($i=0; $i<count($key); $i++) {
                if($key[$i]){
                    $array[$key[$i]]=$value[$i];
                }
            }
            $value=$array;
        }
        if(!$value){return false;}
        $info=M('config')->where(array('id'=>$id))->find();
        if(!$info){return false;}
        //写入数据
        $add['value']=$info['type']==1?$value:json_encode($value);
        $result=$this->where(array('id'=>$id))->save($add);
        if($result===false){return false;}
        return true;
    }
}