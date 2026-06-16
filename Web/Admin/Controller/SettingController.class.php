<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Think\Controller;
/**
 * 站点相关控制器
 * Class User
 * @package app\admin\controller
 */
class SettingController extends BaseController
{
    public function index(){
        $info=M('setting')->where(array('type'=>'system'))->getField('name,data');
        $this->assign('info',$info);
        $this->display();
    }

    public function company(){
        $info=M('setting')->where(array('type'=>'system'))->getField('name,data');
        $this->assign('info',$info);
        $this->display();
    }

    public function sms_setting(){
        $info=M('setting')->where(array('type'=>'system'))->getField('name,data');
        $this->assign('info',$info);
        $this->display();
    }

    public function edit(){
        $post=I('post.');
        $post=$post['setting'];
        foreach($post as $k=>$v){$post[$k]=trim($post[$k]);}
        $keys=array_keys($post);
        //下面判断哪些key系统的设置表里面已经存在
        $hasKeys=M('setting')->where(array('name'=>array('in',$keys)))->getField('name',true);
        if(!$hasKeys){$hasKeys=array();}
        //下面找出不存在的key
        $not_has_keys=array();
        foreach($keys as $v){
            if(!in_array($v,$hasKeys)){$not_has_keys[]=$v;}
        }
        (new CacheLogic())->clear_all_setting();
        //下面处理已经存在的key
        if($hasKeys){
            foreach($hasKeys as $v){
                $return=M('setting')->where(array('name'=>$v))->save(array('data'=>$post[$v]));
                if($return===false){$this->errorjsonReturn('修改失败');die;}
            }
        }
        //下面处理不存在的key
        if($not_has_keys){
            $add=array();
            foreach($not_has_keys as $v){
                $add[]=array('name'=>$v, 'data'=>$post[$v]);
            }
            $return=M('setting')->addAll($add);
            if(!$return){$this->errorjsonReturn('修改失败');}
        }
        $this->setjsonReturn('修改成功');
    }

    private function edit_slide($post,$keyword){
        $array=array();
        foreach($post['image'] as $k=>$v){
            $array[]=array(
                'image'=>$v,
                'url'=>$post['url'][$k]
            );
        }
        (new CacheLogic())->clear_all_setting();
        if(M('setting')->where(array('name'=>$keyword))->count()>0){
            return M('setting')->where(array('name'=>$keyword))->save(array('data'=>json_encode($array)))===false?false:true;
        }else{
            return M('setting')->add(array('name'=>$keyword,'data'=>json_encode($array)))?true:false;
        }
    }

    public function slide_pc(){
        if(IS_POST){
            $return=$this->edit_slide(I('post.'),'slide_pc_pic');
            $return?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }ELSE{
            $info=M('setting')->where(array('name'=>'slide_pc_pic'))->getField('data');
            $info=json_decode($info,true);
            $this->assign('info',$info);
            $this->display();
        }
    }
    public function slide_wap(){
        if(IS_POST){
            $return=$this->edit_slide(I('post.'),'slide_wap_pic');
            $return?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }ELSE{
            $info=M('setting')->where(array('name'=>'slide_wap_pic'))->getField('data');
            $info=json_decode($info,true);
            $this->assign('info',$info);
            $this->display();
        }
    }

}

