<?php
namespace Admin\Model;
use Think\Model;

/**
 * 组织架构模型
 * Class CompanyBranchModel
 * @package Admin\Model
 */
class CompanyBranchModel extends Model
{
    /**
     * 存储或者保存组织架构
     * @param $post
     * @return bool|mixed
     */
    public function saveBranchModel($post){
        //如果不存在父id，默认父id为0
        if(!$post['fatherid']){$post['fatherid']=0;}
        //判断是增加还是修改
        if(isset($post['id'])&&$post['id']){
            return $this->save($post,['id' => $post['id']])!==false?true:false;
        }else{
            $post['create_time']=time();
            return $this->add($post);
        }
    }

    /**
     * 获取分级好的所有组织
     * @return array
     */
    public function getAllBranch(){
        $data=$this->where(array('is_delete'=>array('neq',1)))->select();
        $levelData=listDataToLevel($data,0);
        return $levelData;
    }

    /**
     * 通过id获取信息
     * @param $id
     * @return mixed
     */
    public function getInfoById($id){
        $info=$this->where(array('id'=>$id,'is_delete'=>array('neq',1)))->field('id,fatherid,city,name,remark')->find();
        return $info;
    }
}