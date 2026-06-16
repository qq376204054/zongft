<?php
namespace Admin\Controller;
use Message\Logic\MessageLogic;
use Think\Controller;
/**
 * 客户买卖控制器
 * Class Customermall
 * @package app\admin\controller
 */
class YingxiaoSmsController extends BaseController
{
    /**
     * 买方合作公司管理
     */
    public function index(){
        $list = M('sms_yingxiao')->order('id asc')->select();
        $this->assign('list', $list);
        $big_menu = array('title' => '添加营销方案', 'iframe' => U('add'), 'id' => 'add', 'width' => '500', 'height' => '150',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }
    /**
     * 添加营销工具
     */
    public function add(){
        if(IS_POST){
            if(!$this->data['name']){$this->errorjsonReturn('请填写活动名称');}
            if(!$this->data['qianming']){$this->errorjsonReturn('请填写活动签名');}
            if(!$this->data['sms_key']){$this->errorjsonReturn('请填写短信对接key');}
            if(!$this->data['content']){$this->errorjsonReturn('请填写短信内容');}
            if($this->data['id']){
                $return=M('sms_yingxiao')->where(array('id'=>$this->data['id']))->save($this->data);
                $return=$return===false?false:true;
            }else{
                $this->data['create_time']=time();
                $return=M('sms_yingxiao')->add($this->data);
                $return=$return?true:false;
            }
            $return===true?$this->setjsonReturn('操作成功'):$this->errorjsonReturn('操作失败');
        }else{
            $id=I('get.id');
            if($id){
                $info=M('sms_yingxiao')->where(array('id'=>$id))->find();
                $this->assign('info',$info);
            }
            $this->display();
        }
    }

    /**
     * 操作输出客户的方法
     */
    public function doAction(){
        if(!$this->data['id']){exit('非法操作');}
        $yingxiaoInfo = M('sms_yingxiao')->where(array('id'=>$this->data['id']))->find();
        //提出系统的所有手机号码
        $where = array();
        if($this->data['name']){
            $where['name'] = array('like',"%".$this->data['name']."%");
        }
        if($this->data['mobile']){
            $where['mobile'] = array('like',"%".$this->data['mobile']."%");
        }
        if($this->data['city']){
            $where['city'] = array('like',"%".$this->data['city']."%");
        }
        if($this->data['channel']){
            $where['channel'] = array('eq',$this->data['channel']);
        }
        if($this->data['start_time']){
            $where['create_time'] = array('egt',strtotime($this->data['start_time']));
        }
        if($this->data['end_time']){
            $where['create_time'] = array('elt',(strtotime($this->data['end_time'])+24*3600));
        }
        if($where){
            $allMobile = M('first_customer')->where($where)->field('DISTINCT(mobile)')->select();
            $return = array_column($allMobile,'mobile');
        }else{
            $return = array();
        }

        $this->assign('search',$this->data);
        $this->assign('list',$return);
        $this->assign('yingxiaoInfo',$yingxiaoInfo);
        $this->display();
    }

    /**
     * 操作输出客户的方法
     */
    public function doActionUser(){
        if(!$this->data['id']){exit('非法操作');}
        $yingxiaoInfo = M('sms_yingxiao')->where(array('id'=>$this->data['id']))->find();
        //提出系统的所有手机号码
        $where = array();
        if($this->data['name']){
            $where['user_name'] = array('like',"%".$this->data['name']."%");
        }
        if($this->data['mobile']){
            $where['mobile'] = array('like',"%".$this->data['mobile']."%");
        }
        if($this->data['delete']){
            $where['delete'] = $this->data['delete'];
        }
        if($where){
            $allMobile = M('user')->where($where)->field('DISTINCT(mobile)')->select();
            $return = array_column($allMobile,'mobile');
        }else{
            $return = array();
        }

        $this->assign('search',$this->data);
        $this->assign('list',$return);
        $this->assign('yingxiaoInfo',$yingxiaoInfo);
        $this->display();
    }

    /**
     * 开始发送短信
     */
    public function postsms(){
        if(!$this->data['id'] || !$this->data['mobile']){$this->errorjsonReturn('出错');}
        $info = M('sms_yingxiao')->where(array('id'=>$this->data['id']))->find();
        if(!$info){$this->errorjsonReturn('出错');}
        $return = (new MessageLogic())->postYingxiaoSms($info['sms_key'],$this->data['mobile'],"【".$info['qianming']."】".$info['content']);
        if($return){
            $add = array();
            $add['yingxiao_id'] = $this->data['id'];
            $add['mobile'] = $this->data['mobile'];
            $add['content'] = "【".$info['qianming']."】".$info['content'];
            M('sms_yingxiao_log')->add($add);
            $this->errorjsonReturn('发送成功');
        }else{
            $this->errorjsonReturn('发送失败');
        }
    }

    /**
     * 推送日志
     */
    public function hasTable(){
        $perpage = 100;
        $get = I('get.');
        $p = $get['p'] ? $get['p'] : 1 ;
        $id=I('get.id');
        if(!$id){exit('非法操作');}

        $where = array();
        $where['yingxiao_id'] = array('eq',$id);
        if($get['mobile']){$where['mobile']=array('like','%'.$get['mobile'].'%');}

        $count = M('sms_yingxiao_log')->where($where)->count();
        $Page = new \Think\Page($count,$perpage);
        $list  = M('sms_yingxiao_log')->where($where)
            ->Page($p,$perpage)->order('id desc')
            ->select();

        $this->assign('search',$get);
        $this->assign('count',$count);
        $this->assign('list', $list);
        $this->assign('page',$Page->show());
        $this->display();
    }

}

