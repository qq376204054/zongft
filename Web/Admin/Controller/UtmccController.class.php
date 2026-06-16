<?php
namespace Admin\Controller;
use Admin\Logic\TreeLogic;
use Admin\Model\AuthModel;
use Admin\Model\UserModel;
use Think\Controller;

/**
 * 渠道方对渠道管理
 * Class UtmccController
 * @package Admin\Controller
 */
class UtmccController extends BaseController
{

    //定义能管理的渠道变量
    protected $canMangerUtm=array();

    /**
     * 设置管理人员
     */
    public function setting(){
        $tree = new TreeLogic();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result = M('utm_customer_manger')->where(array('is_delete'=>0))->order('id asc')->select();
        $user_ids = array_column($result,'user_id');
        if($user_ids){
            $userInfos = (new UserModel())->where(array('id'=>array('in',$user_ids)))->getField('id,user_name');
        }
        $array = array();
        foreach($result as $r) {
            if($r['pid']==0){
                $r['name'] = $userInfos[$r['user_id']];
                $r['str_manage'] = '<a href="javascript:;" class="J_showdialog" data-uri="'.U('add_channel',array('pid'=>$r['id'])).'" data-title="添加用户能管理的渠道" data-id="add_child" data-width="500" data-height="80">添加管理渠道</a> |
                                <a href="javascript:;" data-acttype="ajax" class="J_confirmurl" data-uri="'.U('utmdelete',array('id'=>$r['id'])).'" data-msg="确认要删除 - '.$r['name'].' - 吗？">删除</a>';
            }else{
                $r['name'] = $r['channel'];
                $r['str_manage'] = '<a href="javascript:;" data-acttype="ajax" class="J_confirmurl" data-uri="'.U('utmdelete',array('id'=>$r['id'])).'" data-msg="确认要删除 - '.$r['name'].' - 吗？">删除</a>';
            }
            $array[] = $r;
        }

        $str  = "<tr id='node-\$id'>
                <td align='center'><input type='checkbox' value='\$id' class='J_checkitem'></td>
                <td>\$spacer \$name</td>
                <td align='center'>\$str_manage</td>
                </tr>";
        $tree->init($array);
        $list = $tree->get_tree(0, $str);
        $this->assign('list', $list);
        $big_menu = array('title' => '添加管理用户', 'iframe' => U('add_user'), 'id' => 'add_user', 'width' => '500', 'height' => '80',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 添加渠道管理用户
     */
    public function add_user(){
        if(IS_POST){
            if(!$this->data['mobile']){$this->errorjsonReturn('手机号不能为空');}
            $user_id = (new UserModel())->where(array('mobile'=>$this->data['mobile'],'delete'=>1))->getField('id');
            if(!$user_id){$this->errorjsonReturn('用户不存在，请检查');}
            $add = array();
            $add['user_id'] = $user_id;
            $add['create_time'] = time();
            if(M('utm_customer_manger')->add($add)){
                $this->setjsonReturn('修改成功');
            }else{
                $this->errorjsonReturn('修改失败');
            }
        }else{
            $this->display();
        }
    }

    /**
     * 添加用户能管理的渠道
     */
    public function add_channel(){
        if(IS_POST){
            if(!$this->data['channel']){$this->errorjsonReturn('渠道名不能为空');}
            if(!$this->data['pid']){$this->errorjsonReturn('请选择用户');}

            //下面判断渠道存在不存在
            $hasChannel = M('cooperation')->where(array('name'=>$this->data['channel'],'is_delete'=>0))->find();
            if(!$hasChannel){$this->errorjsonReturn('渠道不存在');}

            $pidInfo = M('utm_customer_manger')->where(array('id'=>$this->data['pid'],'is_delete'=>0))->find();
            $add = array();
            $add['user_id'] = $pidInfo['user_id'];
            $add['pid'] = $this->data['pid'];
            $add['channel'] = $this->data['channel'];
            $add['create_time'] = time();
            if(M('utm_customer_manger')->add($add)){
                $this->setjsonReturn('修改成功');
            }else{
                $this->errorjsonReturn('修改失败');
            }
        }else{
            $this->assign('pid', $this->data['pid']);
            $this->display();
        }
    }

    /**
     * 删除用户或者渠道
     */
    public function utmdelete(){
        $return = M('utm_customer_manger')->where(array('id'=>$this->data['id']))->save(array('is_delete'=>1));
        if($return!==false){
            $this->setjsonReturn('删除成功');
        }else{
            $this->errorjsonReturn('删除失败');
        }
    }


    /**
     * 验证权限
     * @return bool
     */
    private function validAuth($channel){
        $where['user_id'] = $this->userInfo['id'];
        $where['channel'] = array('neq','');
        $where['is_delete'] = array('eq',0);
        $this->canMangerUtm = M('utm_customer_manger')->where($where)->getField('channel',true);
        if(!$this->canMangerUtm){return false;}
        if(!$channel){$channel = $this->canMangerUtm[0];}
        if(!in_array($channel,$this->canMangerUtm)){return false;}
        return true;
    }

    /**
     * 推送客户列表
     */
    public function customer()
    {
        //获取当前登录用户管理渠道列表
        $channel = M('utm_customer_manger')->where(array('user_id'=>$this->userInfo['id'],'channel'=>array('neq',''),'is_delete'=>0))->getField('channel',true);
        if (empty($channel)) {
            exit('您没有设置管理渠道');
        }
        //组装查询条件
        $where = array();
        if(!$this->data['channel']){
            $this->data['channel'] = $channel[0];
        }
        $this->assign('utm_sources',$channel);
        $this->assign('search', $this->data);
        if($this->data['name']){ $where['name'] = array('like','%'.$this->data['name'].'%');}
        if($this->data['mobile']){ $where['mobile'] = array('like','%'.$this->data['mobile'].'%');}
        if($this->data['city']){ $where['city'] = array('like','%'.$this->data['city'].'%');}
        if($this->data['channel']){ $where['channel']=array('like','%'.$this->data['channel'].'%');}
        if($this->data['min_money']){ $where['money'][] = array('EGT',$this->data['min_money']);}
        if($this->data['max_money']){ $where['money'][] = array('ELT',$this->data['max_money']);}
        if($this->data['start_time']){ $where['create_time'][] = array('gt',strtotime($this->data['start_time']));}
        if($this->data['end_time']){ $where['create_time'][] = array('lt',(strtotime($this->data['end_time'])+24*60*60));}
        if(isset($this->data['is_repeat'])&&($this->data['is_repeat']!=="")){ $where['is_repeat'] = $this->data['is_repeat'];}
        $count = M('first_customer')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list =  M('first_customer')->where($where)->Page($this->p,$this->perpage)->order($this->desc)->select();
        foreach($list as $k=>$v){
            $list[$k]['mobile'] = (new AuthModel())->doAuthToFirstCustomerPhone($this->userInfo['look_customer_phone'],$v['mobile']);
        }
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $this->assign('list_table', true);
        $this->display();
    }
}

