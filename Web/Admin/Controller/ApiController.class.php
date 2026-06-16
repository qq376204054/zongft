<?php
namespace Admin\Controller;
use Admin\Model\ApiModel;
use Think\Controller;

/**
 * api接口管理工具
 * Class Column
 * @package app\admin\controller
 */
class ApiController extends BaseController
{
    /**
     * 获取接口列表
     */
    public function index(){
        $list=M('api')->where(array('is_delete'=>0))->select();
        $this->assign('list', $list);
        $big_menu = array('title' => '添加接口', 'iframe' => U('Api/add'), 'id' => 'add', 'width' => '500', 'height' => '350',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 删除分类
     */
    public function delete(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result=M('api')->where(array('id'=>array('in',$ids)))->save(array('is_delete'=>1));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }

    /**
     * 创建接口
     */
    public function add(){
        if(IS_POST){
            $post=I('post.');
            if(!$post['name']||!$post['action']||!$post['type']||!$post['field']){return '请完善数据';}
            $add['name']=$post['name'];
            $add['action']=$post['action'];
            $add['type']=$post['type'];
            $add['remark']=$post['remark'];
            //下面包装field配置
            $arr=array();
            foreach($post['field']['key'] as $k=>$v){
                if(trim($v)!=''){
                    $arr[$v]=array($post['field']['name'][$k],$post['field']['value'][$k]);
                }
            }
            $add['config']=json_encode($arr);
            if($post['id']){
                $saveReturn=M('api')->where(array('id'=>$post['id']))->field('name,action,type,remark,config')->save($add);
                $saveReturn!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
            }else{
                $add['create_time']=time();
                $addReturn=M('api')->field('name,action,type,remark,config,create_time')->add($add);
                $addReturn?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
            }
        }else{
            if(I('get.id')){
                $info=M('api')->where(array('id'=>I('get.id')))->find();
                $info['config']=json_decode($info['config'],true);
                $this->assign('info',$info);
            }
            $this->display();
        }
    }

    /**
     * 测试接口
     */
    public function test(){
        $id=I('get.id');
        if(!$id){exit('非法操作');}
        $info=M('api')->where(array('id'=>$id))->find();
        $info['config']=json_decode($info['config'],true);
        $info['url']=U($info['action']);
        $this->assign(['info'=>$info]);
        $this->display();
    }

    /**
     * 获取接口列表
     */
    public function log()
    {
        $this->assign('search', $this->data);
        $get = I('get.');
        $where = array();
        if($get['url']){$where['url']=array('like','%'.$get['url'].'%');}
        if($get['key']){$where['key']=array('like','%'.$get['key'].'%');}
        if($get['type']){$where['type']=array('like','%'.$get['type'].'%');}
        if($get['sort'] && $get['order']){
            $order = $get['sort'].' '.$get['order'];
        } else {
            $order = 'id desc';
        }
        $count = M('api_log')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list =  M('api_log')->where($where)->Page($this->p,$this->perpage)->order($order)->field('id,url,key,type,create_time')->select();
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 查看一个详情
     */
    public function look(){
        $data=M('api_log')->where(array('id'=>I('get.id')))->getField('data');
        dump(json_decode($data,true));
    }
}

