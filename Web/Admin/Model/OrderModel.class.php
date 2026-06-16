<?php
namespace Admin\Model;
use Admin\Logic\CacheLogic;
use Think\Model;

class OrderModel extends Model
{
    /**
     * 获取订单的详情
     * @param $id
     * @return array
     */
    public function getOrderInfoModel($id,$userInfo){
        $all_config = (new CacheLogic())->get_all_config();
        $info = $this->where(array('id'=>$id))->find();
        $info['mobile'] = (new AuthModel())->doAuthToCustomerPhone($userInfo['look_customer_phone'],$info['mobile'],$userInfo['id'],$info['user_id']);
        $ext = json_decode($info['ext'],true);
        $ext = $ext?$ext:[];
        $customer_ext_filed = $all_config['customer_ext_filed'];
        $info['ext'] = [];
        foreach($customer_ext_filed as $key=>$value){
            $info['ext'][] = array(
                'filed'=>$key,
                'filed_name'=>$value,
                'value'=>$ext[$key]?$ext[$key]:""
            );
        }
        return $info;
    }

    /**
     * 根据上级创建客户并创建订单
     * @param $oneBusiness  商机信息
     * @param int $owner_user_id  负责人
     * @return bool  错误返回false  正确返回客户的id
     */
    public function addCustomerAndOrderByOneBusiness($oneBusiness,$city,$owner_user_id=0){
        $company_id = 0;
        if($owner_user_id){
            $userInfo = M('user')->where(array('id'=>$owner_user_id))->find();
            if(!$userInfo){return false;}
            $company_id = $userInfo['company_id'];
        }
        $number=$this->creatOrderNumber();
        $add = array();
        $add['number'] = $number;
        $add['money'] = $oneBusiness['money'];
        $add['channel'] = $oneBusiness['channel'];
        $add['create_time'] = time();
        $add['first_customer_id'] = $oneBusiness['id'];
        $add['name'] = $oneBusiness['name'];
        $add['sex'] = $oneBusiness['sex'];
        $add['mobile'] = $oneBusiness['mobile'];
        $add['city'] = $city;
        $add['user_id'] = $owner_user_id;
        $add['company_id'] = $company_id;
        $add['has_house'] = $oneBusiness['has_house']?$oneBusiness['has_house']:"无";
        $add['has_car'] = $oneBusiness['has_car']?$oneBusiness['has_car']:"无";
        $add['has_baodan'] = $oneBusiness['has_baodan']?$oneBusiness['has_baodan']:"无";
        $add['has_gongjijin'] = $oneBusiness['has_gongjijin']?$oneBusiness['has_gongjijin']:"无";
        $add['has_weilidai'] = $oneBusiness['has_weilidai']?$oneBusiness['has_weilidai']:"无";
        $add['has_nasui'] = $oneBusiness['has_nasui']?$oneBusiness['has_nasui']:"无";
        $add['is_from_allocate'] = 1;
        $add['ext'] = $oneBusiness['ext'];
        $add['change_user_time'] = time();
        $order_id = M('order')->add($add);
        if(!$order_id){return false;}
        return array('order_id'=>$order_id,'order_number'=>$number,'company_id'=>$company_id);
    }

    /**
     * 创建居间协议号码
     * 生成规则  随机2位字母+随机5位+时间截
     */
    public function creatOrderNumber(){
        $str1=$this->makeStr('ABCDEFGHIJKLMNOPQRSTUVWXYZ',2);
        $str2=$this->makeStr('0123456789',3);
        $str3=$this->makeStr('abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',5);
        $str4=time();
        return $str1.$str2.$str3.$str4;
    }

    /**
     * 从字符串中随机提取生成新的字符串
     * @param $chars
     * @param $length
     * @return string
     */
    public function makeStr($chars,$length){
        $secret = '';
        for ( $i = 0; $i < $length; $i++ ){
            $secret .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $secret;
    }


    /*************************************************
     * 各种列表逻辑**********************************
     ************************************************/
    /**
     * 获取客户列表
     * @param $get
     * @param $type  allCuseromer-所有的客户  receive-通用领取池子   myCuseromer-获取我的客户  receiveSpecial-领取特殊的渠道池
     * @param int $my_user_id
     * @param int $city
     * @return array
     */
    public function getOrderListModel($userInfo,$get,$type,$my_user_id=0,$city='',$channel=''){
        if(!in_array($type,array('all_customer','all_del_customer','leave_user_customer','shield_customer','no_company_customer' ,'receive',
            'index','new_customer','old_customer','important_customer','mianjian_customer','qianyue_customer','pidai_customer','judan_customer',
            'shenqian_customer','fangkuan_customer','company_customer','team'))){return array();}
        $customer_status_arr = array(1=>'普通客户',  2=>'重点客户', 3=>'会面客户',4=>'签约客户', 5=>'批款客户', 6=>'放款客户',7=>'拒单客户');
        $p = $get['p'] ? $get['p'] : 1 ;
        $order='change_user_time desc,create_time desc';
        if($get['sort']&&$get['order']){$order=$get['sort'].' '.$get['order'];}
        $perpage=$get['perpage'];
        $where = $this->markCustomerWhere($type,$get,$userInfo,$channel,$get['search_fenzu']);
        if($where===false){return array();}
        $count = $this->where($where)->count();
        $Page = new \Think\Page($count,$perpage);
        $list = $this->where($where)->Page($p,$perpage)->order($order)->select();
        //下面分析列表中需要沟通记录还是什么
        $all_config = (new CacheLogic())->get_all_config();
        if($all_config['customer_list_remark']!='remark'){
            //下面找出客户的沟通日志
            $ids = array_column($list,'id');
            $all_communite = array();
            if($ids){
                $all_communite = M('communicate')->where(array('order_id'=>array('in',$ids)))->order('id desc')->select();
                $communite_user_ids = array_column($all_communite,'user_id');
                $communite_user_ids = $communite_user_ids?$communite_user_ids:array();
            }
            $communiteuserInfos = array();
            if($communite_user_ids){
                $communiteuserInfos=M('user')->where(array('id'=>array('in',$communite_user_ids)))->getField('id,user_name',true);
            }
            $all_communite_new = array();
            foreach($all_communite as $key=>$value){
                $value['user_name'] = $communiteuserInfos[$value['user_id']]?$communiteuserInfos[$value['user_id']]:"";
                $all_communite_new[$value['order_id']][] = $value;
            }
        }
        foreach($list as $k=>$v){
            $list[$k]['mobile'] = (new AuthModel())->doAuthToCustomerPhone($userInfo['look_customer_phone'],$v['mobile'],$userInfo['id'],$v['user_id']);
            $list[$k]['customer_status_name'] = $customer_status_arr[$v['customer_status']];
            //下面分析用户沟通状态  1-从未被沟通   2-分到我头上后没有沟通  3-被沟通过
            if($v['communicate_time']==0){
                $communicate_status = 1;
            }elseif($v['change_user_time']>$v['communicate_time']){
                $communicate_status = 2;
            }else{
                $communicate_status = 3;
            }
            $list[$k]['communicate_status'] = $communicate_status;
            $list[$k]['communicate_list'] = $all_communite_new[$v['id']]?$all_communite_new[$v['id']]:array();
        }
        //下面统计沟通数的统计
        //1天未跟进的统计
        $wherecount = $this->markCustomerWhere($type,$get,$userInfo,$channel,'countOneDay');
        $countOneDay = $this->where($wherecount)->count();
        //3天未跟进的统计
        $wherecount = $this->markCustomerWhere($type,$get,$userInfo,$channel,'countThreeDay');
        $countThreeDay = $this->where($wherecount)->count();
        //5天未跟进的统计
        $wherecount = $this->markCustomerWhere($type,$get,$userInfo,$channel,'countFiveDay');
        $countFiveDay = $this->where($wherecount)->count();
        //7天未跟进的统计
        $wherecount = $this->markCustomerWhere($type,$get,$userInfo,$channel,'countSerDay');
        $countSerDay = $this->where($wherecount)->count();
        //1天未跟进的二星以上统计
        $wherecount = $this->markCustomerWhere($type,$get,$userInfo,$channel,'countOneDayxing1');
        $countOneDayxing1 = $this->where($wherecount)->count();
        //1天未跟进的二星以上统计
        $wherecount = $this->markCustomerWhere($type,$get,$userInfo,$channel,'countOneDayxing2');
        $countOneDayxing2 = $this->where($wherecount)->count();
        $tableCount = array(
            'countOneDay'=>$countOneDay?$countOneDay:0,
            'countThreeDay'=>$countThreeDay?$countThreeDay:0,
            'countFiveDay'=>$countFiveDay?$countFiveDay:0,
            'countSerDay'=>$countSerDay?$countSerDay:0,
            'countOneDayxing1'=>$countOneDayxing1?$countOneDayxing1:0,
            'countOneDayxing2'=>$countOneDayxing2?$countOneDayxing2:0
        );
        return array('pageShow'=>$Page->show(),'list'=>$list,'tableCount'=>$tableCount);
    }

    /**
     * 构建公用的wher查询条件
     * @param $type
     * @param $get
     * @param $my_user_id
     * @param $data_auth   数据权限（1-整个平台的   2-自己部门及下属部门 3-自己的部门 4-仅仅自己的）
     * @return array
     */
    public function markCustomerWhere($type,$get,$userInfo,$channel,$search_fenzu=""){
        $all_config = (new CacheLogic())->get_all_config();
        $where=array();
        if($get['name']){$where['name'] = array('like','%'.$get['name'].'%');}
        if($get['mobile']){$where['mobile'] = array('like','%'.$get['mobile'].'%');}
        if($get['city']){$where['city'] = array('like','%'.$get['city'].'%');}
        if($get['channel']){$where['channel'] = array('like','%'.$get['channel'].'%');}
        if($get['level']){$where['level'][] = array('like','%'.$get['level'].'%');}
        if($get['number']){$where['number'] = array('like','%'.$get['number'].'%');}
        if($get['create_type']){$where['create_type'] = array('eq',$get['create_type']);}
        if($get['start_time']){$where['create_time'][] = array('gt',strtotime($get['start_time']));}
        if($get['end_time']){$where['create_time'][] = array('lt',(strtotime($get['end_time'])+24*60*60));}
        if(isset($get['is_yixiang'])&&($get['is_yixiang']!=="")){$where['is_yixiang'] = $get['is_yixiang'];}
        if(isset($get['is_jietong'])&&($get['is_jietong']!=="")){$where['is_jietong'] = $get['is_jietong'];}
        if($get['start_time1']){$where['communicate_time'][] = array('gt',strtotime($get['start_time1']));}
        if($get['end_time1']){$where['communicate_time'][] = array('lt',(strtotime($get['end_time1'])+24*60*60));}
        if($get['min_money']){ $where['money'][] = array('EGT',$get['min_money']);}
        if($get['max_money']){ $where['money'][] = array('ELT',$get['max_money']);}
        if($get['has_house']){
            if($get['has_house']=="有"){
                $where['has_house'] = array('eq','有');
            }else{
                $where['has_house'] = array('neq','有');
            }
        }
        if($get['customer_status']){$where['customer_status'] = array('eq',$get['customer_status']);}
        if($get['has_car']){
            if($get['has_car']=="有"){
                $where['has_car'] = array('eq','有');
            }else{
                $where['has_car'] = array('neq','有');
            }
        }
        if($get['has_baodan']){
            if($get['has_baodan']=="有"){
                $where['has_baodan'] = array('eq','有');
            }else{
                $where['has_baodan'] = array('neq','有');
            }
        }
        if($get['has_gongjijin']){
            if($get['has_gongjijin']=="有"){
                $where['has_gongjijin'] = array('eq','有');
            }else{
                $where['has_gongjijin'] = array('neq','有');
            }
        }
        if($get['has_weilidai']){
            if($get['has_weilidai']=="有"){
                $where['has_weilidai'] = array('eq','有');
            }else{
                $where['has_weilidai'] = array('neq','有');
            }
        }
        if($get['has_nasui']){
            if($get['has_nasui']=="有"){
                $where['has_nasui'] = array('eq','有');
            }else{
                $where['has_nasui'] = array('neq','有');
            }
        }
        if($get['branch_id']){
            //先看看这个部门是不是公司
            if((new CacheLogic())->branchIsCompany($get['branch_id'])){
                $where['company_id'][] = array('eq',$get['branch_id']);
            }else{
                $branch_search_user_ids = (new CacheLogic())->getOneBranchAllSubUser($get['branch_id']);
                $where['user_id'][] = array('in',$branch_search_user_ids);
            }
        }
        //下面是业务员名字搜索
        if($get['user_name']){
            $user_search_id = M('user')->where(array('user_name'=>array('like',"%".$get['user_name']."%")))->getField('id',true);
            if($user_search_id){
                $where['user_id'][] = array('in',$user_search_id);
            }
        }

        switch($type){
            //全部有效客户
            case "all_customer":
                if($userInfo['data_auth'] != 1){
                    //只能看到自己公司的客户
                    $where['company_id'][] = array('eq',$userInfo['company_id']);
                }
                $where['is_delete'] = array('eq',0);
                break;
            //全部删除客户
            case "all_del_customer":
                if($userInfo['data_auth'] != 1){
                    //只能看到自己公司的客户
                    $where['company_id'][] = array('eq',$userInfo['company_id']);
                }
                $where['is_delete'] = array('eq',1);
                break;
            //离职员工客户
            case "leave_user_customer":
                $where['is_delete'] = array('eq',0);
                //查出系统中所有的离职人员
                $all_leave_user = (new UserModel())->where(array('delete'=>2))->getField('id',true);
                if(!$all_leave_user){return false;}
                $where['user_id'][] = array('in',$all_leave_user);
                if($userInfo['data_auth'] != 1){
                    //只能看到自己公司离职的客户
                    $where['company_id'][] = array('eq',$userInfo['company_id']);
                }
                break;
            //黑名单客户 已删除此功能
            case "shield_customer":
                $where['is_delete'] = array('eq',0);
                $where['is_shield'] = array('eq',1);
                if($userInfo['data_auth'] != 1){
                    //只能看到自己公司的客户
                    $where['company_id'][] = array('eq',$userInfo['company_id']);
                }
                break;
            //无归属客户
            case "no_company_customer":
                $where['is_delete'] = array('eq',0);
                $where['company_id'][] = array('eq',0);
                break;
            //公共领取池
            case "receive":
                $where['is_delete'] = array('eq',0);
                $where['user_id'][] = array('eq',0);
                if($userInfo['data_auth'] != 1){
                    //只能看到自己公司的客户
                    $where['company_id'][] = array('eq',$userInfo['company_id']);
                }
                break;
            //我的客户
            case "index":
                $where['is_delete'] = array('eq',0);
                $where['user_id'][] = array('eq',$userInfo['id']);
                break;
            //新分配客户
            case "new_customer":
                $where['is_delete'] = array('eq',0);
                $where['customer_status'] = array('eq',1);
                $where['is_from_allocate'] = array('eq',1);
                $where['user_id'][] = array('eq',$userInfo['id']);
                break;
            //再分配客户
            case "old_customer":
                $where['is_delete'] = array('eq',0);
                $where['customer_status'] = array('eq',1);
                $where['is_from_allocate'] = array('eq',0);
                $where['user_id'][] = array('eq',$userInfo['id']);
                break;
            //重点跟进客户
            case "important_customer":
                $where['is_delete'] = array('eq',0);
                $where['customer_status'] = array('eq',2);
                $where['user_id'][] = array('eq',$userInfo['id']);
                break;
            //面见客户
            case "mianjian_customer":
                $where['is_delete'] = array('eq',0);
                $where['customer_status'] = array('eq',3);
                $where['user_id'][] = array('eq',$userInfo['id']);
                break;
            //已签约客户
            case "qianyue_customer":
                $where['is_delete'] = array('eq',0);
                $where['customer_status'] = array('eq',4);
                $where['user_id'][] = array('eq',$userInfo['id']);
                break;
            //已批款客户 已不用此功能
            case "pidai_customer":
                $where['is_delete'] = array('eq',0);
                $where['customer_status'] = array('eq',5);
                $where['user_id'][] = array('eq',$userInfo['id']);
                break;
            //已拒单客户
            case "judan_customer":
                $where['is_delete'] = array('eq',0);
                $where['customer_status'] = array('eq',7);
                $where['user_id'][] = array('eq',$userInfo['id']);
                break;
            //审签客户
            case "shenqian_customer":
                $where['is_delete'] = array('eq',0);
                $where['customer_status'] = array('in',array(4,5));
                if($userInfo['data_auth'] == 1){ //管理员登录
                    $where['user_id'][] = array('neq',0);
                }elseif($userInfo['data_auth'] == 2){ //公司登录
                    if(!$userInfo['id']){ return false; }
                    $myAllSubUserId = (new UserModel())->getMyAllSubUserId($userInfo);
                    $myAllSubUserId = array_filter($myAllSubUserId);
                    if(!$myAllSubUserId){ return false; }
                    $where['user_id'][] = array('in',$myAllSubUserId);
                }elseif($userInfo['data_auth'] == 3){ //部门登录
                    $user_ids = M('user')->where(array('branch_id'=>$userInfo['branch_id']))->getField('id',true);
                    $user_ids = array_filter($user_ids);
                    if(!$user_ids){ return false; }
                    $where['user_id'][] = array('in',$user_ids);
                }else{ //自己
                    $where['user_id'][] = array('eq',$userInfo['id']);
                }
                break;
            //放款客户
            case "fangkuan_customer":
                $where['is_delete'] = array('eq',0);
                $where['customer_status'] = array('eq',6);
                if($userInfo['data_auth'] == 1){ //整个平台
                    $where['user_id'][] = array('neq',0);
                }elseif($userInfo['data_auth'] == 2){ //自己部门及下属
                    if(!$userInfo['id']){ return false; }
                    $myAllSubUserId = (new UserModel())->getMyAllSubUserId($userInfo);
                    $myAllSubUserId = array_filter($myAllSubUserId);
                    if(!$myAllSubUserId){ return false; }
                    $where['user_id'][] = array('in',$myAllSubUserId);
                }elseif($userInfo['data_auth'] == 3){ //自己部门
                    $user_ids = M('user')->where(array('branch_id'=>$userInfo['branch_id']))->getField('id',true);
                    $user_ids = array_filter($user_ids);
                    if(!$user_ids){ return false; }
                    $where['user_id'][] = array('in',$user_ids);
                }else{ //自己
                    $where['user_id'][] = array('eq',$userInfo['id']);
                }
                break;
            //公司客户
            case "company_customer":
                $where['is_delete'] = array('eq',0);
                $where['company_id'][] = array('eq',$userInfo['company_id']);
                break;
            //团队客户
            case "team":
                $where['is_delete'] = array('eq',0);
                if($userInfo['data_auth'] == 1){                 //整个平台
                    $where['user_id'][] = array('neq',0);
                }elseif($userInfo['data_auth'] == 2){           //自己部门及下属
                    if(!$userInfo['id']){ return false; }
                    $myAllSubUserId = (new UserModel())->getMyAllSubUserId($userInfo);
                    $myAllSubUserId = array_filter($myAllSubUserId);
                    if(!$myAllSubUserId){ return false; }
                    $where['user_id'][] = array('in',$myAllSubUserId);
                }elseif($userInfo['data_auth'] == 3){           //自己部门
                    $user_ids = M('user')->where(array('branch_id'=>$userInfo['branch_id']))->getField('id',true);
                    $user_ids = array_filter($user_ids);
                    if(!$user_ids){ return false; }
                    $where['user_id'][] = array('in',$user_ids);
                }else{                                            //自己
                    $where['user_id'][] = array('eq',$userInfo['id']);
                }
                break;
            default:
                return false;
        }

        //下面跟进选择的场景进行搜索
        switch($search_fenzu){
            //获取全部有效的订单
            case "countOneDay":
                $where['communicate_time'][] = array('lt',time()-1*24*60*60);
                break;
            case "countThreeDay":
                $where['communicate_time'][] = array('lt',time()-3*24*60*60);
                break;
            case "countFiveDay":
                $where['communicate_time'][] = array('lt',time()-5*24*60*60);
                break;
            case "countSerDay":
                $where['communicate_time'][] = array('lt',time()-7*24*60*60);
                break;
            case "countOneDayxing1":
                $where['communicate_time'][] = array('lt',time()-1*24*60*60);
                $where['level'][] = array('eq','A');
                break;
            case "countOneDayxing2":
                $where['communicate_time'][] = array('lt',time()-1*24*60*60);
                $where['level'][] = array('eq','B');
                break;
            default:
        }
        return $where;
    }
}