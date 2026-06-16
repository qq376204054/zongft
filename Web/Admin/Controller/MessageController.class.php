<?php
namespace Admin\Controller;
use Message\Logic\MessageLogic;
use Think\Controller;

/**
 * 通知管理相关控制器
 * Class User
 * @package app\admin\controller
 */
class MessageController extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();
        header("Access-Control-Allow-Origin: * ");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
        header('Access-Control-Allow-Headers:X-Requested-With,x-requested-with,content-type,accept,Content-Type, Accept,requesttype');
    }

    /**
     * 消息列表首页
     */
    public function index(){
        $this->display();
    }
    public function msg_list(){
        $get=I('get.');
        $p = $get['p'] ? $get['p'] : 1 ;
        $perpage=8;
        $where=array();
        $where['user_id']=$this->userInfo['id'];
        $where['is_look']=0;
        $count = M('message_log')->where($where)->count();
        $pageNum=floor($count/$perpage);
        if($count%$perpage>0){$pageNum++;}
        $list =  M('message_log')->where($where)->Page($p,$perpage)->order('id desc')->select();
        $this->assign('list', $list);
        $this->assign('page', $p);
        $this->assign('pageNum', $pageNum);
        $this->assign('count', $count);
        $this->display();
    }
    /**
     * websockt绑定当前登录用户
     * @return mixed
     */
    public function bindclient()
    {
        $return=(new MessageLogic())->bindclient(I('post.client_id'),$this->userInfo['id']);
        $return===true?$this->setjsonReturn('绑定成功'):$this->errorjsonReturn($return);
    }
    /**
     * 设置已读
     */
    public function read_msg(){
        $return=M('message_log')->where(array('id'=>I('post.msg_id')))->save(array('is_look'=>1));
        $count=M('message_log')->where(array('user_id'=>$this->userInfo['id'],'is_look'=>0))->count();
        $return===false?$this->errorjsonReturn('操作失败'):$this->setjsonReturn($count);
    }

    /**
     * 全部设置已读
     */
    public function read_all()
    {
        $return = M('message_log')->where(array('user_id'=>$this->userInfo['id']))->save(array('is_look'=>1));
        $count = 0;
        $return===false?$this->errorjsonReturn('操作失败'):$this->setjsonReturn($count);
    }

    /**
     * 获取10条未读的消息
     */
    public function noread_msg(){
        $big_msg_id=I('big_msg_id');
        $where['user_id']=$this->userInfo['id'];
        $where['is_look']=0;
        $count=M('message_log')->where($where)->count();
        $where['id']=array('gt',$big_msg_id);
        $return=M('message_log')->where($where)->field('id,content,create_time')->limit(10)->select();
        $this->setjsonReturn(array('list'=>$return,'count'=>$count));
    }

    /**
     * 检查有没有闹铃提醒
     */
    public function naoling(){
        //下面再读取有没有下次需要跟进的内容
        $where2 = array();
        $where2['user_id'] = $this->userInfo['id'];
        $where2['last_contact_time'] = array('lt',(time()+5*60));
        $where2['is_tixing'] = 0;
        $want_co = M('last_contact')->where($where2)->find();
        if($want_co){
            M('last_contact')->where(array('id'=>$want_co['id']))->save(array('is_tixing'=>1));
        }
        $this->setjsonReturn(array('want_co'=>$want_co));
    }
}

