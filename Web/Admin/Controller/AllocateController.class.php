<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Admin\Model\ConfigModel;
use Think\Controller;
/**
 * 分配队列相关控制器
 * Class User
 * @package app\admin\controller
 */
class AllocateController extends BaseController
{
    public function index(){
        //获取需要特殊处理分配的渠道
        $specialChannel=(new ConfigModel())->getConfigByNameModel('allocate_channel');
        $this->assign('specialChannel',$specialChannel);
        //获取能分配的城市
        $allocateCity=(new ConfigModel())->getConfigByNameModel('allocate_city');
        $cannot_allocate_city = (new ConfigModel())->getConfigByNameModel('cannot_allocate_city');
        $allocateCityAndConfig = array();
        foreach($allocateCity as $value){
            $one = array();
            $one['name'] = $value;
            $one['status'] = in_array($value,$cannot_allocate_city)?0:1;
            $allocateCityAndConfig[] = $one;
        }
        $this->assign('allocateCityAndConfig',$allocateCityAndConfig);
        $this->display();
    }


    public function allocate_city(){
        //获取能分配的城市
        $allocateCity=(new ConfigModel())->getConfigByNameModel('allocate_city');
        $this->assign('allocateCity',$allocateCity);
        $this->assign('channel',I('get.channel'));
        $this->display();
    }

    /**
     * 获取城市的公司列表
     */
    public function allocate_company(){
        //获取一个城市的所有公司
        if(!$this->data['city']){$this->errorjsonReturn("请选择城市！");}
        $cityCompanys = M('company_branch')->where(array('city'=>$this->data['city'],'status'=>1,'is_company'=>1,'is_delete'=>0))->order('ordid asc,id asc')->select();
        $this->assign('channel',$this->data['channel']);
        $this->assign('city',$this->data['city']);
        $this->assign('cityCompanys',$cityCompanys);
        $this->display();
    }

    /**
     * 获取一个公司的所有用户
     */
    public function table(){
        if(!$this->data['company_id']){exit('请选择公司');}
        if(!$this->data['city']){exit('城市有误');}
        $all_config = (new CacheLogic())->get_all_config();
        $channel = $this->data['channel'] ? $this->data['channel']:'';

        //下面搜寻公司的所有用户
        $userBranchInfo = M('user')->where(array('company_id'=>$this->data['company_id'],'delete'=>1))->field('id as user_id,branch_id,user_name,mobile,balance')->select();
        $branchInfos = array();
        $all_branch = (new CacheLogic())->get_all_branch();
        foreach($all_branch as $key=>$value){
            $branchInfos[$value['id']] = $value['name'];
        }

        //下面找出这些用户设置队列情况
        $user_ids=array_column($userBranchInfo,'user_id');
        $has_info=array();
        $mall_company_Infos = array();
        if($user_ids){
            $has_info=M('allocate_user')->where(array('user_id'=>array('in',$user_ids),'channel'=>$channel,'city'=>$this->data['city'],'is_delete'=>0))->getField('user_id,can_allocate,mall_company_id',true);
            $mall_company_ids = array_unique(array_filter(array_column($has_info,'mall_company_id')));
            if($mall_company_ids){
                $mall_company_Infos = M('mall_company')->where(array('id'=>array('in',$mall_company_ids)))->getField("id,name",true);
            }
        }
        //下面找出用户已经分配的情况
        if($user_ids){
            $start_time = date('Y-m-d 00:00:00',time());
            $end_time = date('Y-m-d 23:59:59',time());
            $where3 = array();
            $where3['a.remark'] = "自动分配";
            $where3['a.create_time'] = array('between',array($start_time,$end_time));
            $where3['a.new_user_id'] = array('in',$user_ids);
            if($channel){
                $where3['b.channel'] = $channel;
            }else{
                if($all_config['allocate_channel']){
                    $where3['b.channel'] = array('not in',$all_config['allocate_channel']);
                }
            }

            $allocateNumInfo = M('allocate_log')->alias('a')->where($where3)
                ->join('LEFT JOIN __FIRST_CUSTOMER__ as b ON a.first_customer_id = b.id')
                ->group('a.new_user_id')->getField("a.new_user_id,COUNT(*)");
        }

        foreach($userBranchInfo as $k=>$v){
            $userBranchInfo[$k]['branch_name']=$branchInfos[$v['branch_id']];
            if(isset($has_info[$v['user_id']])){
                $userBranchInfo[$k]['allocate_status']=1;
                $userBranchInfo[$k]['can_allocate'] = $has_info[$v['user_id']]['can_allocate'];
                $userBranchInfo[$k]['mall_company_id'] = $has_info[$v['user_id']]['mall_company_id'];
            }else{
                $userBranchInfo[$k]['allocate_status']=0;
                $userBranchInfo[$k]['can_allocate'] = 0;
                $userBranchInfo[$k]['mall_company_id'] = 0;
            }
            $userBranchInfo[$k]['has_allocate'] = isset($allocateNumInfo[$v['user_id']])?$allocateNumInfo[$v['user_id']]:0;
        }
        $this->assign('mall_company_Infos',$mall_company_Infos);
        $this->assign('userBranchInfo',$userBranchInfo);
        $this->assign('search',$this->data);
        $this->display();
    }

    /**
     * 设置是否api分配
     */
    public function settingapi(){
        if (IS_POST) {
            M('allocate_user')->where(array('id'=>$this->data['id']))->save(array('mall_company_id'=>$this->data['mall_company_id']))!==false?$this->setjsonReturn('设置成功'):$this->errorjsonReturn('设置失败');
        }else{
            $channel = $this->data['channel'] ? $this->data['channel']:'';
            $user_id = $this->data['user_id'];
            $city = $this->data['city'];
            if(!$user_id){exit("非法操作");}
            if(!$city){exit("非法操作");}
            $info=M('allocate_user')->where(array('user_id'=>$user_id,'city'=>$city,'channel'=>$channel,'is_delete'=>0))->find();
            $list = M('mall_company')->where(array('status'=>1))->order('id asc')->select();
            $this->assign('info',$info);
            $this->assign('list',$list);
            $this->display();
        }
    }

    public function select_user_allocate(){
        $user_id=I('post.user_id');
        $city=I('post.city');
        $channel=I('post.channel')?I('post.channel'):'';
        if(!$user_id||!$city){$this->errorjsonReturn('非法操作');}
        //判断是否已加入队列
        $has_allocate = M('allocate_user')->field('id,create_time')->where(array('user_id'=>$user_id,'city'=>$city,'channel'=>$channel,'is_delete'=>0))->find();
        if (!empty($has_allocate)) {//踢出队列操作
            //软删除
            //$return = M('allocate_user')->where(array('user_id'=>$user_id,'city'=>$city,'channel'=>$channel,'is_delete'=>0))->save(array('update_time'=>time(),'is_delete'=>1));
            //真删除
            $return = M('allocate_user')->where(array('id'=>$has_allocate['id']))->delete();
            $return===false?$this->errorjsonReturn('操作失败'):$this->setjsonReturn('操作成功');
        }else{//如果已经不在队列中，加入队列
            $add=array();
            $add['user_id']=$user_id;
            $add['channel']=$channel;
            $add['city']=$city;
            $add['create_time'] = time();
            $add['update_time'] = time();
            M('allocate_user')->add($add)?$this->setjsonReturn('操作成功'):$this->errorjsonReturn('操作失败');
        }
    }

    /**
     * 数字设置能分多少个
     */
    public function edit_user_balance(){
        //获取当前选择用户
        $user_id = $this->data['user_id'];
        //判断是否表单提交
        if(IS_POST){
            if (!$user_id) {
                $this->errorjsonReturn('请先选择用户');
            }
            $info = M('user')->where(array('id'=>$user_id))->field('id,company_id,balance')->find();
            if (!$info) {
                $this->errorjsonReturn('非法操作');
            }
            $company = M('company_branch')->field('id,score')->where(array('id'=>$info['company_id']))->find();
            if (empty($company)) {
                $this->errorjsonReturn('非法操作');
            }
            $balance = $this->data['balance'];
            if (!$balance) {
                $this->errorjsonReturn('请填写变动金额');
            }
            $res = M('user')->where(array('id'=>$user_id))->save(array('balance'=>$info['balance'] + $balance));
            $result = M('company_branch')->where(array('id'=>$info['company_id']))->save(array('score'=>$company['score']+$balance));
            if ($res && $result) {
                //添加公司账户余额变动记录
                $addData = array(
                    'company_id' => $info['company_id'],
                    'user_id' => $user_id,
                    'score' => $balance,
                    'create_user_id' => $this->userInfo['id'],
                    'create_time' => time()
                );
                M('company_score_log')->add($addData);
                //添加用户余额变动记录
                $addData = array(
                    'user_id' => $user_id,
                    'balance' => $balance,
                    'create_user_id' => $this->userInfo['id'],
                    'create_time' => time()
                );
                M('user_balance_log')->add($addData);
                $this->setjsonReturn('修改成功');
            } else {
                $this->errorjsonReturn('修改失败');
            }
        } else {
            if(!$user_id){
                exit('请先选择用户');
            }
            $this->assign('user_id',$user_id);
            $this->display();
        }
    }


    /**
     * 设置分配数量
     */
    public function set_allocate_num(){
        $this->data['is_delete'] = 0;
        $info = M('allocate_user')->where($this->data)->find();
        if(!$info){$this->errorjsonReturn("请先设置允许分配");}
        if(($this->data['type']==2)&&($info['can_allocate']<1)){
            $this->errorjsonReturn("已经是0了");
        }
        $num = $this->data['type']==1 ? ($info['can_allocate']+1):($info['can_allocate']-1);
        $result = M('allocate_user')->where(array('id'=>$info['id']))->save(array('can_allocate'=>$num,'is_over'=>0,'over_time'=>""));
        $result===false?$this->errorjsonReturn("操作失败"):$this->setjsonReturn("操作成功");
    }

    /**
     * 数字设置能分多少个
     */
    public function editCanAllocate(){
        if(IS_POST){
            $channel = $this->data['channel']?$this->data['channel']:"";
            $info = M('allocate_user')->where(array('is_delete'=>0,'user_id'=>$this->data['user_id'],'city'=>$this->data['city'],'channel'=>$channel))->find();
            if(!$info){$this->errorjsonReturn("请先设置允许分配");}
            $result = M('allocate_user')->where(array('id'=>$info['id']))->save(array('can_allocate'=>$this->data['num'],'is_over'=>0,'over_time'=>""));
            $result===false?$this->errorjsonReturn("操作失败"):$this->setjsonReturn("操作成功");
        }ELSE{
            $user_id = $this->data['user_id'];
            $city = $this->data['city'];
            $channel = $this->data['channel']?$this->data['channel']:"";
            $company_id = $this->data['company_id'];
            if(!$user_id){exit('请选择用户');}
            if(!$city){exit('请选择城市');}
            if(!$company_id){exit('请选择公司');}
            $info = M('allocate_user')->where(array('is_delete'=>0,'user_id'=>$this->data['user_id'],'city'=>$this->data['city'],'channel'=>$channel))->find();
            if(!$info){exit('暂未设置允许分配');}
            $this->assign('num',$info['can_allocate']);
            $this->assign('search',$this->data);
            $this->display();
        }
    }




    /**
     * 设置城市的停用启用状态
     */
    public function set_city_allocate_status(){
        //获取能分配的城市
        $allocateCity=(new ConfigModel())->getConfigByNameModel('allocate_city');
        if(!in_array($this->data['city'],$allocateCity)){$this->errorjsonReturn('非法操作');}

        $cannot_allocate_city = (new ConfigModel())->getConfigByNameModel('cannot_allocate_city');
        $cannot_allocate_city = $cannot_allocate_city?$cannot_allocate_city:array();
        //清除缓存
        (new CacheLogic())->clear_all_config();
        //如果以前在暂停的里面，就去除
        if(in_array($this->data['city'],$cannot_allocate_city)){
            foreach($cannot_allocate_city as $key=>$value){
                if($value==$this->data['city']){unset($cannot_allocate_city[$key]);}
            }
            $cannot_allocate_city = array_values($cannot_allocate_city);
        }else{
            $cannot_allocate_city[] = $this->data['city'];
        }


        $save['value']=json_encode($cannot_allocate_city);
        $result=M('config')->where(array('name'=>'cannot_allocate_city'))->save($save);

        $result===false?$this->errorjsonReturn('操作失败'):$this->setjsonReturn('切换成功');
    }




    /**
     * 获取队列列表
     */
    public function allocate_user_list(){
        $return=M()->query('SELECT u.user_name,a.user_id from '.C('DB_PREFIX').'allocate_user a
             LEFT JOIN '.C('DB_PREFIX').'user u ON a.user_id=u.id WHERE a.channel="'.I('get.channel').'"
             AND a.city="'.I('get.city').'" AND a.is_delete=0 order by a.update_time asc');

        $this->assign('list',$return);
        $this->display();
    }
}

