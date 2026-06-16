<?php
namespace Admin\Controller;
use Admin\Model\ApiModel;
use Think\Controller;

/**
 * 登录日志
 * Class Column
 * @package app\admin\controller
 */
class LoginLogController extends BaseController
{

    /**
     * 获取接口列表
     */
    public function index(){
        //下面找出所有的用户
        $allUser = M('user')->where(array('delete'=>1))->field('id,user_name')->select();

        $where=array();
        if($this->data['user_id']){$where['user_id']=array('eq',$this->data['user_id']);}

        if($this->data['start_time']){$where['create_time'][] = array('gt',$this->data['start_time']." 00:00:00");}
        if($this->data['end_time']){$where['create_time'][] = array('lt',$this->data['end_time']." 23:59:59");}
        $count = M('user_login_log')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list =  M('user_login_log')->where($where)->Page($this->p,$this->perpage)->order($this->desc)->select();
        $user_ids = array_column($list,'user_id');
        $userInfos = array();
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name',true);
        }
        foreach($list as $key=>$value){
            $list[$key]['user_name'] = $userInfos[$value['user_id']]?$userInfos[$value['user_id']]:"";
        }

        $this->assign('search', $this->data);
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $this->assign('list_table', true);
        $this->assign('allUser',$allUser);
        $this->display();
    }

    /**
     * 删除操作
     */
    public function delete(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result = M('user_login_log')->where(array('id'=>array('in',$ids)))->delete();
        if($result !== false){
            $this->setjsonReturn('删除成功');
        } else {
            $this->errorjsonReturn('删除失败');
        }
    }
}

