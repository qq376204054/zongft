<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Admin\Model\AuthModel;
use Admin\Model\ConfigModel;
use Admin\Model\OrderModel;
use Think\Controller;

/**
 * 沟通相关控制器
 */
class CommunicateController extends BaseController
{
    /**
     * 添加沟通日志       -----检查好了
     */
    public function add(){
        if(IS_POST){
            if(!$this->data['name']){$this->errorjsonReturn('请填写客户名');}
            if(!$this->data['money']){$this->errorjsonReturn('请填写需求金额');}
            //下面构建扩展的字段
            $ext = [];
            $all_config = (new CacheLogic())->get_all_config();
            $customer_ext_filed = $all_config['customer_ext_filed'];
            foreach($customer_ext_filed as $key=>$value){
                $ext[$key] = $this->data[$key];
            }
            //下面添加修改信息
            $save = array(
                'money'=>$this->data['money'],
                'name'=>$this->data['name'],
                'sex'=>$this->data['sex'],
                'address'=>$this->data['address'],
                'has_house'=>$this->data['has_house'],
                'house_address'=>$this->data['house_address'],
                'follow_type'=>$this->data['follow_type'],
                'level'=>$this->data['level'],
                'communicate_time'=>time(),
                'ext'=>json_encode($ext),
                'has_car'=>$this->data['has_car'],
                'has_baodan'=>$this->data['has_baodan'],
                'has_gongjijin'=>$this->data['has_gongjijin'],
                'has_weilidai'=>$this->data['has_weilidai'],
                'has_nasui'=>$this->data['has_nasui'],
                'is_jietong'=>$this->data['is_jietong'],
                'is_yixiang'=>$this->data['is_yixiang'],
            );
            if($this->data['remark']){
                $save['remark'] = $this->data['remark'];
            }
            if($this->data['id']){
                if(!$this->data['follow_type']){$this->errorjsonReturn('请选择跟进状态');}
                if($this->data['is_jietong']===""){$this->errorjsonReturn('请选择是否接通');}
                if($this->data['is_yixiang']===""){$this->errorjsonReturn('请选择是否意向');}
                if(!$this->data['content']){$this->errorjsonReturn('请填写沟通内容');}
                if(!$this->data['remark']){$this->errorjsonReturn('请填写客户的备注描述');}
                $orderInfo=M('order')->where(array('id'=>$this->data['id']))->field('user_id')->find();
//                if($orderInfo['user_id'] != $this->userInfo['id']){$this->errorjsonReturn('你没有权限');}

                $return1=M('order')->where(array('id'=>$this->data['id']))->save($save);
                if($return1===false){$this->errorjsonReturn('添加失败');}
                $return2=M('communicate')->add(array(
                    'order_id'=>$this->data['id'],
                    'user_id'=> $this->userInfo['id'],
                    'content'=>$this->data['content'],
                    'money'=>$this->data['money'],
                    'follow_type'=>$this->data['follow_type'],
                ));
                $return2?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
            }else{
                //必要的字段不能为空
                if(!$this->data['name']||!$this->data['mobile']||!$this->data['city']){$this->errorjsonReturn('请完善信息');}
                if(!is_phone($this->data['mobile'])){$this->errorjsonReturn('请仔细填写手机号，确保不能有非法字符');}
                $save['mobile'] = $this->data['mobile'];
                $save['channel'] = '线下添加';
                $save['create_time'] = time();
                $save['user_id'] = $this->userInfo['id'];
                $save['change_user_time'] = time();
                $save['company_id'] = $this->userInfo['company_id'];
                $save['number'] = (new OrderModel())->creatOrderNumber();
                $save['create_user_id'] = $this->userInfo['id'];
                $save['create_type'] = 2;
                $save['city'] = $this->data['city'];
                $save['communicate_time'] = 0;

                M('order')->add($save)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
            }
        }else{
            $all_config = (new CacheLogic())->get_all_config();
            $this->assign('customer_top_star', intval($all_config['customer_top_star']));//客户最高等级
            $this->assign('allocateCity',$all_config['job_city']);//可选城市列表
            $this->assign('customer_follow_type',$all_config['customer_follow_type']);//跟进类型
            $info = (new OrderModel())->getOrderInfoModel($this->data['id'],$this->userInfo);
            $this->assign('info', $info);
            $this->display();
        }
    }

    /**
     * 获取沟通列表      -----检查好了
     */
    public function list1(){
        $order_id=I('get.order_id');
        $list=M('communicate')->where(array('order_id'=>$order_id))->order('id desc')->select();
        $user_ids=array_column($list,'user_id');
        if($user_ids){
            $userInfo=M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name');
            $this->assign('userInfo',$userInfo);
        }
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 获取沟通列表      -----检查好了
     */
    public function contact_list(){
        $list=M('last_contact')->where(array('order_id'=>$this->data['order_id']))->order('id desc')->select();
        $user_ids=array_column($list,'user_id');
        if($user_ids){
            $userInfo = M('user')->where(array('id'=>array('in',$user_ids)))->getField('id,user_name');
            $this->assign('userInfo',$userInfo);
        }
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 添加联系
     */
    public function addLastLianxi(){
        if(!$this->data['id']){$this->errorjsonReturn('非法操作');}
        if(!$this->data['last_lianxi_time']){$this->errorjsonReturn('请选择下次联系时间');}
        if(!$this->data['last_lianxi_content']){$this->errorjsonReturn('请填写下次联系提醒内容');}

        $add = array();
        $add['user_id'] = $this->userInfo['id'];
        $add['order_id'] = $this->data['id'];
        $add['last_contact_time'] = strtotime($this->data['last_lianxi_time']);
        $add['content'] = $this->data['last_lianxi_content'];
        $add['create_time'] = time();
        $return2 = M('last_contact')->add($add);
        $return2?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
    }
}

