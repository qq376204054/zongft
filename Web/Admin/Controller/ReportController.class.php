<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Admin\Logic\TreeLogic;
use Admin\Model\AccountModel;
use Admin\Model\AllocateLogModel;
use Admin\Model\CommunicateModel;
use Admin\Model\CompanyBranchModel;
use Admin\Model\FirstCustomerModel;
use Admin\Model\UserBranchModel;
use Admin\Model\UserModel;
use Think\Controller;
/**
 * 报表相关控制器
 * Class User
 * @package app\admin\controller
 */
class ReportController extends BaseController
{
    /************************************************获客报表*****************************************************/
    /**
     * 报表搜索
     */
    public function first_customer(){
        $utmSourceConfig=(new CacheLogic())->get_channel_key_manger();
        $this->assign('utmSourceConfig',$utmSourceConfig);
        $this->assign('customer_apply_city',(new CacheLogic())->get_all_config()['customer_apply_city']);
        $this->display();
    }
    /**
     * 报表数据
     */
    public function first_customer_table(){
        $result=(new FirstCustomerModel())->report(I('get.'));
        if(!is_array($result)){exit($result);}
        $this->assign('list',$result);
        $this->display();
    }
    /************************************************获客报表*****************************************************/







    /************************************************分配报表*****************************************************/
    /**
     * 分配报表
     */
    public function allocate(){
        $this->assign('start_time',date('Y-m-d',time()));
        $this->assign('end_time',date('Y-m-d',time()));
        $this->makeBranchSelect();
        //非管理员登录时不显示公司部门下拉框 2020.04.21
        $is_show_branch_select = false;
        if (session('user_info_new.role_ids') == 1 || session('user_info_new.role_ids') == 15) {
            $is_show_branch_select = true;
        }
        $this->assign('is_show_branch_select',$is_show_branch_select);
        $this->display();
    }
    /**
     * 分配报表数据表格
     */
    public function allocate_table(){
        $get=I('get.');
        foreach($get as $k=>$v){
            if (empty($v) || $v == 'undefined') {
                unset($get[$k]);
            } else {
                $get[$k]=trim($v);
            }
        }
        $start_time=$get['start_time']?$get['start_time'].' 00:00:00':date('Y-m-d 00:00:00',time());
        $end_time=$get['end_time']?date("Y-m-d 00:00:00",strtotime($get['end_time'])+24*60*60):date("Y-m-d 00:00:00",strtotime("+1 day"));

        //找出我权限下面的所有人
        if($this->userInfo['data_auth'] == 1){
            $myAllSubUserId = (new UserModel())->getField('id',true);
        }else{
            $myAllSubUserId = (new UserModel())->getMyAllSubUserId($this->userInfo);
        }
        //有部门搜索，找出这个部门的所有人员
        if($get['branch_id']){
            $branch_search_user_ids = (new CacheLogic())->getOneBranchAllSubUser($get['branch_id']);
            $new_myAllSubUserId = array();
            foreach($myAllSubUserId as $key=>$value){
                if(in_array($value,$branch_search_user_ids)){
                    $new_myAllSubUserId[] = $value;
                }
            }
            $myAllSubUserId = $new_myAllSubUserId;
        }
        //下面是名字的搜索
        if($get['user_name']){
            $user_search_id = M('user')->where(array('user_name'=>array('like',"%".$get['user_name']."%")))->getField('id',true);
            $new_myAllSubUserId = array();
            foreach($myAllSubUserId as $key=>$value){
                if(in_array($value,$user_search_id)){
                    $new_myAllSubUserId[] = $value;
                }
            }
            $myAllSubUserId = $new_myAllSubUserId;
        }

        if(!$myAllSubUserId){$this->display();die();}

        if($get['user_delete']!=3){
            $user_delete = $get['user_delete']?$get['user_delete']:1;
            $myAllSubUserId = M('user')->where(array('id'=>array('in',$myAllSubUserId),'delete'=>$user_delete))->getField('id',true);
            if(!$myAllSubUserId){$this->display();die();}
        }

        //获取得到员工的日志统计
        $allocate_logs_in=(new AllocateLogModel())->getAllocateByRemarkIn($myAllSubUserId,array('自动分配','领取客户','转移客户'),$start_time,$end_time);
        //获取失去员工的日志统计
        $allocate_logs_out=(new AllocateLogModel())->getAllocateByRemarkOut($myAllSubUserId,array('丢回领取池','转移客户'),$start_time,$end_time);
        //获取所有的员工部门
        $branchInfos=(new UserModel())->getBranchInfo($myAllSubUserId);

        //下面获取员工的名字
        $userInfo=(new UserModel())->getUsersInfo($myAllSubUserId);
        //下面统计沟通客户数
        $customer_customer_num=(new CommunicateModel())->getCommunicateCustomerNum($myAllSubUserId,$start_time,$end_time);
        //下面统计沟通次数
        $customer_num=(new CommunicateModel())->getCommunicateNum($myAllSubUserId,$start_time,$end_time);
        $list=array();
        $count=array();


        foreach($myAllSubUserId as $value){
            $list[]=array(
                'user_name'   =>$userInfo[$value]['user_name']?$userInfo[$value]['user_name']:'',
                'status_name'   =>$userInfo[$value]['delete']==1?"在职":"离职",
                'branch_name' =>$branchInfos[$value]['name']?$branchInfos[$value]['name']:'',
                'diuhui'       =>$allocate_logs_out[$value]['丢回领取池']?$allocate_logs_out[$value]['丢回领取池']:0,
                'zidong_in'    =>$allocate_logs_in[$value]['自动分配']?$allocate_logs_in[$value]['自动分配']:0,
                'zhuanru_in'   =>$allocate_logs_in[$value]['转移客户']?$allocate_logs_in[$value]['转移客户']:0,
                'zhuanru_out' =>$allocate_logs_out[$value]['转移客户']?$allocate_logs_out[$value]['转移客户']:0,
                'lingqu'       =>$allocate_logs_in[$value]['领取客户']?$allocate_logs_in[$value]['领取客户']:0,
                'gou_kehu'    =>$customer_customer_num[$value]?$customer_customer_num[$value]:0,
                'gou_cishu'   =>$customer_num[$value]?$customer_num[$value]:0
            );
            $count['丢回客户']=(int)$count['丢回客户']+(int)$allocate_logs_out[$value]['丢回领取池'];
            $count['自动分配次数']=(int)$count['自动分配次数']+(int)$allocate_logs_in[$value]['自动分配'];
            $count['客户转入次数']=(int)$count['客户转入次数']+(int)$allocate_logs_in[$value]['转移客户'];
            $count['客户转出次数']=(int)$count['客户转出次数']+(int)$allocate_logs_out[$value]['转移客户'];
            $count['领取次数']=(int)$count['领取次数']+(int)$allocate_logs_in[$value]['领取客户'];
            $count['沟通次数']=(int)$count['沟通次数']+(int)$customer_num[$value];
            $count['沟通客户数']=(int)$count['沟通客户数']+(int)$customer_customer_num[$value];
        }
        $this->assign('list',$list);
        $this->assign('count',$count);
        $this->display();
    }
    /************************************************分配报表*****************************************************/



    /************************************************业绩报表*****************************************************/
    /**
     * 报表搜索
     */
    public function account(){
        $this->assign('start_time',date('Y-m-d',time()));
        $this->assign('end_time',date('Y-m-d',time()));
        $this->makeBranchSelect();
        //非管理员登录时不显示公司部门下拉框 2020.04.21
        $is_show_branch_select = false;
        if (session('user_info_new.role_ids') == 1 || session('user_info_new.role_ids') == 15) {
            $is_show_branch_select = true;
        }
        $this->assign('is_show_branch_select',$is_show_branch_select);
        $this->display();
    }
    /**
     * 报表数据
     */
    public function account_table(){
        $get = I('get.');
        foreach($get as $k=>$v){
            if (empty($v) || $v == 'undefined') {
                unset($get[$k]);
            } else {
                $get[$k]=trim($v);
            }
        }
        $start_time=$get['start_time']?$get['start_time'].' 00:00:00':date('Y-m-d 00:00:00',time());
        $end_time=$get['end_time']?date("Y-m-d 00:00:00",strtotime($get['end_time'])+24*60*60):date("Y-m-d 00:00:00",strtotime("+1 day"));

        //找出我权限下面的所有人
        if($this->userInfo['data_auth'] == 1){
            $myAllSubUserId = (new UserModel())->getField('id',true);
        }else{
            $myAllSubUserId = (new UserModel())->getMyAllSubUserId($this->userInfo);
        }

        //有部门搜索，找出这个部门的所有人员
        if(!empty($get['branch_id'])){
            $branch_search_user_ids = (new CacheLogic())->getOneBranchAllSubUser($get['branch_id']);
            $new_myAllSubUserId = array();
            foreach($myAllSubUserId as $key=>$value){
                if(in_array($value,$branch_search_user_ids)){
                    $new_myAllSubUserId[] = $value;
                }
            }
            $myAllSubUserId = $new_myAllSubUserId;
        }

        //下面是名字的搜索
        if(!empty($get['user_name'])){
            $user_search_id = M('user')->where(array('user_name'=>array('like',"%".$get['user_name']."%")))->getField('id',true);
            $new_myAllSubUserId = array();
            foreach($myAllSubUserId as $key=>$value){
                if(in_array($value,$user_search_id)){
                    $new_myAllSubUserId[] = $value;
                }
            }
            $myAllSubUserId = $new_myAllSubUserId;
        }
        if(!$myAllSubUserId){$this->display();die();}
        if($get['user_delete']!=3){
            $user_delete = $get['user_delete']?$get['user_delete']:1;
            $myAllSubUserId = M('user')->where(array('id'=>array('in',$myAllSubUserId),'delete'=>$user_delete))->getField('id',true);
            if(!$myAllSubUserId){$this->display();die();}
        }

        //下面统计每个人的账单
        $account=(new AccountModel())->sumAccountByUserModel($myAllSubUserId,$start_time,$end_time);

        //获取所有的员工部门
        $branchInfos=(new UserModel())->getBranchInfo($myAllSubUserId);
        //下面获取员工的名字
        $userInfo=(new UserModel())->getUsersInfo($myAllSubUserId);
        foreach($account['data'] as $key=>$value){
            $account['data'][$key]['user_name']=$userInfo[$key]['user_name']?$userInfo[$key]['user_name']:'';
            $account['data'][$key]['status_name'] = $userInfo[$key]['delete']==1?"在职":"离职";
            $account['data'][$key]['branch_name']=$branchInfos[$key]['name']?$branchInfos[$key]['name']:'';
        }
        //下面统计账目的种类
        $config=(new CacheLogic())->get_all_config();
        $this->assign('account_income_type',$config['account_income_type']);
        $this->assign('account_pay_type',$config['account_pay_type']);
        $this->assign('list',$account);
        $this->display();
    }
    /************************************************业绩报表*****************************************************/






    /************************************************账单报表*****************************************************/
    /**
     * 报表搜索
     */
    public function scoreCost(){
        $all_branch = (new CacheLogic())->get_all_branch();
        $all_company = array();
        foreach($all_branch as $key=>$value){
            if($value['is_company']==1){
                $all_company[$value['id']] = $value;
            }
        }
        //下面是所有的公司
        $this->assign('all_company',$all_company);
        if(!$this->data['start_time']){$this->data['start_time'] = date('Y-m-d',time());}
        if(!$this->data['end_time']){$this->data['end_time'] = date('Y-m-d',time());}
        $this->assign('search', $this->data);

        //下面查询列表
        $start_time = $this->data['start_time']?$this->data['start_time'].' 00:00:00':date('Y-m-d 00:00:00',time());
        $end_time = $this->data['end_time']?date("Y-m-d 00:00:00",strtotime($this->data['end_time'])+24*60*60):date("Y-m-d 00:00:00",strtotime("+1 day"));

        $where = array();
        $where['create_time'][] = array('gt',strtotime($start_time));
        $where['create_time'][] = array('lt',strtotime($end_time));

        //找出我权限下面的所有人
        if($this->userInfo['data_auth'] != 1){
            $where['company_id'][] = array('eq',$this->userInfo['company_id']);
        }
        if($this->data['company_id']){
            $where['company_id'][] = array('eq',$this->data['company_id']);
        }
        //加还是减
        if($this->data['type']==1){
            $where['score'][] = array('gt',0);
        }elseif($this->data['type']==2){
            $where['score'][] = array('lt',0);
        }

        //下面进行统计总剩余积分数
        $where1['id'] = $where['company_id']?$where['company_id']:array();
        $where1['id'][] = array('in',array_column($all_company,'id'));
        $totalRemainSore = M('company_branch')->where($where1)->sum('score');
        $totalRemainSore = $totalRemainSore?$totalRemainSore:0;
        $this->assign('totalRemainSore',$totalRemainSore);

        //下面统计总充值积分，总消耗积分
        $where2 = $where;
        $where2['score'][] = array('gt',0);
        $totalSore1 = M('company_score_log')->where($where2)->sum('score');
        $totalSore1 = $totalSore1?$totalSore1:0;
        $this->assign('totalSore1',$totalSore1);

        $where3 = $where;
        $where3['score'][] = array('lt',0);
        $totalSore2 = M('company_score_log')->where($where3)->sum('score');
        $totalSore2 = $totalSore2?$totalSore2:0;
        $this->assign('totalSore2',$totalSore2);

        //下面是搜索列表
        $count = M('company_score_log')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list =  M('company_score_log')->where($where)->Page($this->p,$this->perpage)->order($this->desc)->select();
        foreach($list as $k=>$v){
            $list[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            $list[$k]['company_name'] = $all_company[$v['company_id']]['name'];
        }
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $this->assign('list_table', true);
        //非管理员登录时不显示公司部门下拉框 2020.04.21
        $is_show_branch_select = false;
        if (session('user_info_new.role_ids') == 1 || session('user_info_new.role_ids') == 15) {
            $is_show_branch_select = true;
        }
        $this->assign('is_show_branch_select',$is_show_branch_select);
        $this->display();
    }
    /************************************************账单报表*****************************************************/



    /************************************************消费明细*****************************************************/
    /**
     * 分配报表
     */
    public function cost_detail(){
        $all_branch = (new CacheLogic())->get_all_branch();
        $all_company = array();
        foreach($all_branch as $key=>$value){
            if($value['is_company']==1){
                $all_company[$value['id']] = $value;
            }
        }
        //下面是所有的公司
        $this->assign('all_company',$all_company);
        if(!$this->data['start_time']){$this->data['start_time'] = date('Y-m-d',time());}
        if(!$this->data['end_time']){$this->data['end_time'] = date('Y-m-d',time());}
        $this->assign('search', $this->data);

        //下面查询列表
        $start_time = $this->data['start_time']?$this->data['start_time'].' 00:00:00':date('Y-m-d 00:00:00',time());
        $end_time = $this->data['end_time']?date("Y-m-d 00:00:00",strtotime($this->data['end_time'])+24*60*60):date("Y-m-d 00:00:00",strtotime("+1 day"));

        $where = array();
        $where['create_time'][] = array('gt',$start_time);
        $where['create_time'][] = array('lt',$end_time);
        $where['price'][] = array('gt',0);

        //找出我权限下面的所有人
        if($this->userInfo['data_auth'] != 1){
            $where['new_user_id'][] = array('eq',$this->userInfo['id']);
        }
        if($this->data['company_id']){
            $user_ids = M('user')->where(array('company_id'=>array('eq',$this->data['company_id'])))->getField('id',true);
            if(!$user_ids){$this->display();die();}
            $where['new_user_id'][] = array('in',$user_ids);
        }
        //下面是名字的搜索
        if($this->data['user_name']){
            $user_search_id = M('user')->where(array('user_name'=>array('like',"%".$this->data['user_name']."%")))->getField('id',true);
            if(!$user_search_id){$this->display();die();}
            $where['new_user_id'][] = array('in',$user_search_id);
        }

        $totalRemainSore = M('allocate_log')->where($where)->sum('price');
        $totalRemainSore = $totalRemainSore?$totalRemainSore:0;
        $count = M('allocate_log')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list = M('allocate_log')->where($where)->Page($this->p,$this->perpage)->order($this->desc)->select();

        $new_user_ids = array_column($list,'new_user_id');
        if($new_user_ids){
            $userInfos = M('user')->where(array('id'=>array('in',$new_user_ids)))->getField('id,user_name,mobile');
        }
        $order_ids = array_column($list,'order_id');
        if($order_ids){
            $customerInfos = M('order')->where(array('id'=>array('in',$order_ids)))->getField('id,number,name');
        }

        $this->assign('totalRemainSore', $totalRemainSore);
        $this->assign('userInfos', $userInfos);
        $this->assign('customerInfos', $customerInfos);
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $this->assign('list_table', true);
        //非管理员登录时不显示公司部门下拉框 2020.04.21
        $is_show_branch_select = false;
        if (session('user_info_new.role_ids') == 1 || session('user_info_new.role_ids') == 15) {
            $is_show_branch_select = true;
        }
        $this->assign('is_show_branch_select',$is_show_branch_select);
        $this->display();
    }
    /************************************************消费明细*****************************************************/


    /************************************************业绩报表*****************************************************/
    /**
     * 报表搜索
     */
    public function customer_detail(){
        $this->makeBranchSelect(0);
        //非管理员登录时不显示公司部门下拉框 2020.04.21
        $is_show_branch_select = false;
        if (session('user_info_new.role_ids') == 1 || session('user_info_new.role_ids') == 15) {
            $is_show_branch_select = true;
        }
        $this->assign('is_show_branch_select',$is_show_branch_select);
        $this->display();
    }

    /**
     * 客户报表
     */
    public function customer_table(){
        $get=I('get.');
        foreach($get as $k=>$v){
            if (empty($v) || $v == 'undefined') {
                unset($get[$k]);
            } else {
                $get[$k]=trim($v);
            }
        }

        //找出我权限下面的所有人
        if($this->userInfo['data_auth'] == 1){
            $myAllSubUserId = (new UserModel())->getField('id',true);
        }else{
            $myAllSubUserId = (new UserModel())->getMyAllSubUserId($this->userInfo);
        }
        //有部门搜索，找出这个部门的所有人员
        if($get['branch_id']){
            $branch_search_user_ids = (new CacheLogic())->getOneBranchAllSubUser($get['branch_id']);
            $new_myAllSubUserId = array();
            foreach($myAllSubUserId as $key=>$value){
                if(in_array($value,$branch_search_user_ids)){
                    $new_myAllSubUserId[] = $value;
                }
            }
            $myAllSubUserId = $new_myAllSubUserId;
        }
        //下面是名字的搜索
        if($get['user_name']){
            $user_search_id = M('user')->where(array('user_name'=>array('like',"%".$get['user_name']."%")))->getField('id',true);
            $new_myAllSubUserId = array();
            foreach($myAllSubUserId as $key=>$value){
                if(in_array($value,$user_search_id)){
                    $new_myAllSubUserId[] = $value;
                }
            }
            $myAllSubUserId = $new_myAllSubUserId;
        }

        if(!$myAllSubUserId){$this->display();die();}
        if($get['user_delete']!=3){
            $user_delete = $get['user_delete']?$get['user_delete']:1;
            $myAllSubUserId = M('user')->where(array('id'=>array('in',$myAllSubUserId),'delete'=>$user_delete))->getField('id',true);
            if(!$myAllSubUserId){$this->display();die();}
        }

        $where=array();
        if($this->data['start_time']){$where['create_time'][]=array('egt',strtotime($this->data['start_time']));}
        if($this->data['end_time']){$where['create_time'][]=array('lt',strtotime($this->data['end_time'])+24*60*60);}
        $where['user_id']=array('in',$myAllSubUserId);

        $meetingData = M('order')->where($where)->group('user_id,level')->field('user_id,level,COUNT(*) AS num')->select();
        $newlist = array();
        foreach($meetingData as $value){
            $newlist[$value['user_id']][$value['level']] = $value['num'];
        }
        //获取所有的员工部门
        $branchInfos=(new UserModel())->getBranchInfo($myAllSubUserId);
        //下面获取员工的名字
        $userInfo=(new UserModel())->getUsersInfo($myAllSubUserId);

        $customer_star_level = array('A','B','C','D','E','F');
        $customer_star_level_name = array('A'=>'1星','B'=>'2星','C'=>'3星','D'=>'4星','E'=>'5星','F'=>'6星');

        $list = array();
        foreach($myAllSubUserId as $value){
            $one = array();
            foreach($customer_star_level as $one_level){
                $one[$one_level] = $newlist[$value][$one_level]?$newlist[$value][$one_level]:0;
            }

            $list[]=array(
                'user_name'   =>$userInfo[$value]['user_name']?$userInfo[$value]['user_name']:'',
                'status_name'   =>$userInfo[$value]['delete']==1?"在职":"离职",
                'branch_name' =>$branchInfos[$value]['name']?$branchInfos[$value]['name']:'',
                'list'=>$one
            );
        }
        $this->assign('level_arr',$customer_star_level);
        $this->assign('level_arr_name',$customer_star_level_name);
        $this->assign('list',$list);
        //非管理员登录时不显示公司部门下拉框 2020.04.21
        $is_show_branch_select = false;
        if (session('user_info_new.role_ids') == 1 || session('user_info_new.role_ids') == 15) {
            $is_show_branch_select = true;
        }
        $this->assign('is_show_branch_select',$is_show_branch_select);
        $this->display();
    }
}

