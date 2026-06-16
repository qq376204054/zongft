<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Admin\Model\AllocateLogModel;
use Admin\Model\AuthModel;
use Admin\Model\ConfigModel;
use Admin\Model\CustomerModel;
use Admin\Model\MenuModel;
use Admin\Model\OrderModel;
use Admin\Model\UserBranchModel;
use Admin\Model\UserModel;
use Message\Logic\MessageLogic;
use Think\Controller;

/**
 * 客户管理相关控制器
 */
class CustomerController extends BaseController
{
    /**
     * 自动执行函数
     */
    public function _initialize(){
        parent::_initialize();
        //获取系统设置的客户最高星级
        $all_config = (new CacheLogic())->get_all_config();
        $this->assign('customer_top_star', intval($all_config['customer_top_star']));
    }
    /**
     * 全部有效客户
     */
    public function all_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'all_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'all_customer');
        $this->display('customerlist');
    }

    /**
     * 全部删除客户
     */
    public function all_del_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'all_del_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'all_del_customer');
        $this->display('customerlist');
    }

    /**
     * 离职员工客户
     */
    public function leave_user_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'leave_user_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'leave_user_customer');
        $this->display('customerlist');
    }

    /**
     * 黑名单客户
     * 此方法暂时不用 2020-04-23 fanlei
     */
    public function shield_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'shield_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'shield_customer');
        $this->display('customerlist');
    }

    /**
     * 公共领取池
     */
    public function receive(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'receive',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'receive');
        $this->display('customerlist');
    }

    /**
     * 无归属客户（没有公司归属的客户）
     */
    public function no_company_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'no_company_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'no_company_customer');
        $this->display('customerlist');
    }


    /**
     * 我的客户
     */
    public function index(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'index',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'index');
        $this->display('customerlist');
    }

    /**
     * 新分配客户 由系统分配
     */
    public function new_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'new_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'new_customer');
        $this->display('customerlist');
    }

    /**
     * 再分配客户 自己领取的客户
     */
    public function old_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'old_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'old_customer');
        $this->display('customerlist');
    }

    /**
     * 我的重点跟进客户
     */
    public function important_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'important_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'important_customer');
        $this->display('customerlist');
    }

    /**
     * 我的面见客户
     */
    public function mianjian_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'mianjian_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'mianjian_customer');
        $this->display('customerlist');
    }

    /**
     * 我的签约客户
     */
    public function qianyue_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'qianyue_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'qianyue_customer');
        $this->display('customerlist');
    }

    /**
     * 我的批贷客户
     * 此方法暂时不用 2020-04-23 fanlei
     */
    public function pidai_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'pidai_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'pidai_customer');
        $this->display('customerlist');
    }

    /**
     * 我的拒单客户
     */
    public function judan_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'judan_customer',$this->userInfo['id']);
        $user_ids = array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'judan_customer');
        $this->display('customerlist');
    }

    /**
     * 最后修改：2020-04-23 fanlei
     * 我的审签客户
     * 根据登录用户角色查看
     * 业务员只能查看自己客户，部门可查看部门客户，公司可查看整个公司客户
     */
    public function shenqian_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'shenqian_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'shenqian_customer');
        $this->display('customerlist');
    }

    /**
     * 最后修改：2020-04-23 fanlei
     * 我的放款收佣客户
     * 根据登录用户角色查看
     * 业务员只能查看自己客户，部门可查看部门客户，公司可查看整个公司客户
     */
    public function fangkuan_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'fangkuan_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'fangkuan_customer');
        $this->display('customerlist');
    }

    /**
     * 团队客户
     */
    public function team(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'team',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'team');
        $this->display('customerlist');
    }

    /**
     * 公司客户
     */
    public function company_customer(){
        $this->assign('search', $this->data);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$this->data,'company_customer',$this->userInfo['id']);
        $user_ids=array_column($return['list'],'user_id');
        if($user_ids){
            $userInfos=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name,mobile');
        }
        $this->assign('list', $return['list']);
        $this->assign('data_auth',$this->userInfo['data_auth']);
        $this->assign('userInfos',$userInfos);
        $this->makeBranchSelect($this->data['branch_id']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->assign('list_type', 'company_customer');
        $this->display('customerlist');
    }


    /**
     * 设为重要跟进客户
     */
    public function changeIsZhongyao(){
        $customerInfo = M('order')->where(array('id'=>$this->data['id']))->find();
        if(!$customerInfo){$this->errorjsonReturn('非法操作');}
        if(!in_array($customerInfo['customer_status'],array(1,2))){
            $this->errorjsonReturn('只能在普通客户和重点跟进客户之间变更');
        }
        $change_status = $customerInfo['customer_status']==1?2:1;
        if($change_status==2){
            //判断我标记的是不是上限了
            $all_config = (new CacheLogic())->get_all_config();
            $zhongyao_num = M('order')->where(array('user_id'=>$this->userInfo['id'],'customer_status'=>2,'is_delete'=>0))->count();
            $max_important_customer_num = $all_config['max_important_customer_num']?$all_config['max_important_customer_num']:0;
            if($zhongyao_num>=$max_important_customer_num){
                $this->errorjsonReturn('您标记的重要客户已达上限，上限数量为'.$max_important_customer_num.'个');
            }
        }
        $return = M('order')->where(array(array('id'=>$this->data['id'])))->save(array('customer_status'=>$change_status));
        if($return===false){
            $this->errorjsonReturn('更改失败');
        }
        $this->setjsonReturn($change_status);
    }

    /**
     * 设为已批款
     */
    public function changeIsPikuan()
    {
        $customerInfo = M('order')->where(array('id'=>$this->data['id']))->find();
        if(!$customerInfo){$this->errorjsonReturn('非法操作');}
        if($customerInfo['customer_status']!=4){
            $this->errorjsonReturn('不是签约客户状态无法转化为批款客户');
        }
        //M('order')->where(array('user_id'=>$this->userInfo['id'],'customer_status'=>2,'is_delete'=>0))->count();
        $return = M('order')->where(array('id'=>$this->data['id']))->save(array('customer_status'=>5));
        if($return===false){
            $this->errorjsonReturn('更改失败');
        }
        $this->setjsonReturn("设置成功");
    }

    /**
     * 设为已拒单
     */
    public function changeIsJudan()
    {
        $customerInfo = M('order')->where(array('id'=>$this->data['id']))->find();
        if(!$customerInfo){$this->errorjsonReturn('非法操作');}
        if($customerInfo['customer_status']!=4){
            $this->errorjsonReturn('不是签约客户状态无法转化为拒单客户');
        }
        $return = M('order')->where(array('id'=>$this->data['id']))->save(array('customer_status'=>7));
        if($return===false){
            $this->errorjsonReturn('更改失败');
        }
        $this->setjsonReturn("设置成功");
    }

    /**
     * 设为已放款
     */
    public function changeIsFangkuan(){
        $customerInfo = M('order')->where(array('id'=>$this->data['id']))->find();
        if(!$customerInfo){$this->errorjsonReturn('非法操作');}
        if($customerInfo['customer_status']!=5){
            $this->errorjsonReturn('不是批款客户状态无法转化为放款客户');
        }
        $return = M('order')->where(array('id'=>$this->data['id']))->save(array('customer_status'=>6));
        if($return===false){
            $this->errorjsonReturn('更改失败');
        }
        $this->setjsonReturn("设置成功");
    }


    /**
     * 查看客户的详情信息  ----检查完毕
     */
    public function look(){
        $this->assign('id',I('get.id'));
        $this->display();
    }

    /**
     * 领取客户的动作  ----检查完毕
     */
    public function receive_action(){
        $order_id=$this->data['order_id'];
        $orderInfo = M('order')->where(array('id'=>$order_id))->find();
        if(!$order_id){$this->errorjsonReturn('非法操作');}
        //下面获取每天最大的领取数
        $receive_max=(new ConfigModel())->getConfigByNameModel('receive_max');
        //下面获取我已经领了多少次
        $start_time = date("Y-m-d",time());
        $end_time=date("Y-m-d",strtotime($start_time)+86400);
        $has_receive_num =M('allocate_log')->where(array('create_time'=>array('between',$start_time.','.$end_time),'operation'=>'领取客户','new_user_id'=>$this->userInfo['id']))->count();
        if($has_receive_num>=$receive_max){$this->errorjsonReturn('今日领取已达上限');}
        //下面开始领取操作
        $return1=M('order')->where(array('id'=>$order_id,'user_id'=>0))->save(array('user_id'=>$this->userInfo['id'],'change_user_time'=>time(),'is_from_allocate'=>0));
        if($return1===false){$this->errorjsonReturn('领取失败');}
        M('allocate_log')->add(array(
            'first_customer_id'=>$orderInfo['first_customer_id'],
            'order_id'=>$order_id,
            'new_user_id'=>$this->userInfo['id'],
            'old_user_id'=>0,
            'remark'=>'领取客户'
        ))?$this->setjsonReturn('领取成功'):$this->errorjsonReturn('领取失败');
    }


    /**
     * 领取客户的动作  ----检查完毕
     */
    public function piliang_receive_action(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需领取项');}
        $orderInfos = M('order')->where(array('id'=>array('in',$ids),'user_id'=>0))->getField('id,name,first_customer_id',true);
        if(count($orderInfos)!=count($ids)){$this->errorjsonReturn('选择的客户中有客户已经被领取，请刷新后重新领取');}
        //下面获取每天最大的领取数
        $receive_max=(new ConfigModel())->getConfigByNameModel('receive_max');
        //下面获取我已经领了多少次
        $start_time = date("Y-m-d",time());
        $end_time=date("Y-m-d",strtotime($start_time)+86400);
        $has_receive_num =M('allocate_log')->where(array('create_time'=>array('between',$start_time.','.$end_time),'operation'=>'领取客户','new_user_id'=>$this->userInfo['id']))->count();
        if(($has_receive_num+count($ids))>$receive_max){$this->errorjsonReturn('您之前已经领取'.$has_receive_num.'个，最多只能领取'.$receive_max.'个，但是你选中了'.count($ids).'个');}

        //下面开始领取操作
        $return1=M('order')->where(array('id'=>array('in',$ids)))->save(array('user_id'=>$this->userInfo['id'],'change_user_time'=>time(),'is_from_allocate'=>0));
        if($return1===false){$this->errorjsonReturn('领取失败');}
        $addAll = [];
        foreach($orderInfos as $key=>$value){
            $one = array(
                'first_customer_id'=>$value['first_customer_id'],
                'order_id'=>$value['id'],
                'new_user_id'=>$this->userInfo['id'],
                'old_user_id'=>0,
                'remark'=>'领取客户'
            );
            $addAll[] = $one;
        }
        $return2 = M('allocate_log')->addAll($addAll);
        $this->setjsonReturn('领取成功');
    }

    /**
     * 丢到领取池   -----检查完毕
     */
    public function throw_away(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需丢到领取池项');}
        //下面找出客户的负责人
        $orderInfos = M('order')->where(array('id'=>array('in',$ids)))->getField('id,user_id,first_customer_id',true);
        $user_ids = array_column($orderInfos,'user_id');
        $user_ids=implode(',',array_filter(array_unique($user_ids)));
        if($user_ids!=$this->userInfo['id']){$this->errorjsonReturn('选择的客户中包含不是自己的客户，请检查');}
        $result=M('order')->where(array('id'=>array('in',$ids)))->save(array('user_id'=>0,'change_user_time'=>time()));
        if($result===false){$this->errorjsonReturn('丢到领取池失败');}
        //下面写入日志
        $add=array();
        foreach($ids as $id){
            $add[]=array(
                'first_customer_id'=>$orderInfos[$id]['first_customer_id'],
                'order_id'=>$id,
                'new_user_id'=>0,
                'old_user_id'=>$this->userInfo['id'],
                'remark'=>'丢回领取池'
            );
        }
        if($add){
            $return=M('allocate_log')->addAll($add);
            if(!$return){$this->errorjsonReturn('丢到领取池失败');}
        }
        $this->setjsonReturn('丢到领取池成功');
    }

    /**
     * 丢到黑名单   ----检查完毕
     */
    public function throw_shield(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需丢到黑名单项');}
        //下面找出客户的负责人
        $user_ids=M('order')->where(array('id'=>array('in',$ids)))->getField('user_id',true);
        $user_ids=implode(',',array_filter(array_unique($user_ids)));
        if($user_ids!=$this->userInfo['id']){$this->errorjsonReturn('选择的客户中包含不是自己的客户，请检查');}
        $result=M('order')->where(array('id'=>array('in',$ids)))->save(array('is_shield'=>1,'change_user_time'=>time()));
        if($result===false){$this->errorjsonReturn('丢入黑名单失败');}
        $this->setjsonReturn('丢入黑名单成功');
    }

    /**
     * 移出黑名单    ---------已确认
     */
    public function out_shield(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需移出黑名单项');}
        //下面找出客户的负责人
        $user_ids=M('order')->where(array('id'=>array('in',$ids)))->getField('user_id',true);
        $user_ids=implode(',',array_filter(array_unique($user_ids)));
        if($user_ids){
            if($user_ids!=$this->userInfo['id']){$this->errorjsonReturn('选择的客户中拥有有负责人但不是自己的客户，请检查');}
        }
        $result=M('order')->where(array('id'=>array('in',$ids)))->save(array('is_shield'=>0,'change_user_time'=>time()));
        if($result===false){$this->errorjsonReturn('移出黑名单失败');}
        $this->setjsonReturn('移出黑名单成功');
    }


    /**
     * 获取客户的信息    ---------已确认
     */
    public function one_info(){
        if(I('get.achievement_id')){
            $order_id = M('achievement')->where(array('id'=>I('get.achievement_id')))->getField('order_id');
        }elseif(I('get.id')){
            $order_id = I('get.id');
        }else{exit('非法操作');}
        $info=M('order')->where(array('id'=>$order_id))->find();
        $info['mobile'] = (new AuthModel())->doAuthToCustomerPhone($this->userInfo['look_customer_phone'],$info['mobile'],$this->userInfo['id'],$info['user_id']);
        $userInfo=M('user')->where(array('id'=>$info['user_id']))->getField('user_name');
        $info['ext']=json_decode($info['ext'],true);
        $this->assign('ext', $info['ext']);
        $this->assign('customer_ext_filed', (new CacheLogic())->get_all_config()['customer_ext_filed']);
        $this->assign('info',$info);
        $this->assign('userInfo',$userInfo);
        $this->display();
    }

    /**
     * 获取一个客户的分配日志    ---------已确认
     */
    public function allocateInfo(){
        $id=I('get.id');
        $list=M('allocate_log')->where(array('order_id'=>$id))->select();
        if($list){
            $old_user_ids=array_column($list,'old_user_id');$old_user_ids=$old_user_ids?$old_user_ids:array();
            $new_user_ids=array_column($list,'new_user_id');$new_user_ids=$new_user_ids?$new_user_ids:array();
            $user_ids=array_unique(array_filter(array_merge($old_user_ids,$new_user_ids)));
            if($user_ids){
                $user_infos=M('user')->where(array('id'=>array('in',$user_ids)))->order('id desc')->getField('id,user_name',true);
                $this->assign('user_infos',$user_infos);
            }
        }
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 移动负责人    ---------已确认
     */
    public function remove_user(){
        if(IS_POST){
            $ids=array_unique(array_filter(explode(',',I('post.ids'))));
            $user_id=I('post.user_id');
            if(!$ids){return $this->errorjsonReturn('非法操作');}
            if(!$user_id){return $this->errorjsonReturn('请选择负责人');}
            $userBranch = M('user')->where(array('id'=>$user_id))->find();
            $order_infos = M('order')->where(array('id'=>array('in',$ids)))->field('id,name,user_id,company_id,first_customer_id')->select();
            //下面转移负责人
            $return=M('order')->where(array('id'=>array('in',$ids)))->save(array('user_id'=>$user_id,'company_id'=>$userBranch['company_id'],'change_user_time'=>time(),'is_from_allocate'=>0));
            if($return!==false){
                //发送短信通知
                (new MessageLogic())->addMessage('move_customer','',$order_infos,$user_id,0);
                //添加转移记录
                (new AllocateLogModel())->addMoveCustomerLog($order_infos,$user_id);
            }
            $return===false?$this->errorjsonReturn('操作失败'):$this->setjsonReturn('操作成功');
        }else{
            $this->assign('ids',I('get.remove_user'));
            $this->display();
        }
    }

    /**
     * 操作修改客户的重要数据  ----检查完毕
     */
    public function update_important(){
        if (IS_POST) {
            if(!$this->data['id']){$this->errorjsonReturn('非法操作');}
            //必要的字段不能为空
            if(!$this->data['channel']||!$this->data['city']){$this->errorjsonReturn('请完善信息');}
            //下面查询渠道存在不存在
            $has_channel=M('cooperation')->where(array('name'=>$this->data['channel'],'key'=>'','is_delete'=>0))->count();
            if($has_channel==0){$this->errorjsonReturn('渠道不存在，请检查');}
            M('order')->where(array('id'=>$this->data['id']))->save($this->data)!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }else{
            $info=M('order')->where(array('id'=>I('get.id')))->find();
            if(!$info){exit('非法操作');}
            $this->assign('info',$info);
            $allocateCity=(new ConfigModel())->getConfigByNameModel('job_city');
            $this->assign('allocateCity',$allocateCity);
            $this->display();
        }
    }

    /**
     * 全部客户盘的丢到领取池 ----检查完毕
     */
    public function throw_away_all(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需丢到领取池项');}
        $orderInfos = M('order')->where(array('id'=>array('in',$ids)))->getField('id,user_id,first_customer_id',true);
        $result=M('order')->where(array('id'=>array('in',$ids),'user_id'=>array('neq',0)))->save(array('user_id'=>0,'change_user_time'=>time()));
        if($result===false){$this->errorjsonReturn('丢到领取池失败');}
        //下面写入日志
        $add=array();
        foreach($ids as $id){
            $add[]=array(
                'first_customer_id'=>$orderInfos[$id]['first_customer_id'],
                'order_id'=>$id,
                'new_user_id'=>0,
                'old_user_id'=>$this->userInfo['id'],
                'remark'=>'丢回领取池'
            );
        }
        if($add){
            $return=M('allocate_log')->addAll($add);
            if(!$return){$this->errorjsonReturn('丢到领取池失败');}
        }
        $this->setjsonReturn('丢到领取池成功');
    }
}