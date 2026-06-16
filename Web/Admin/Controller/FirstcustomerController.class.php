<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Admin\Logic\ToolsLogic;
use Admin\Model\AuthModel;
use Admin\Model\CompanyBranchModel;
use Admin\Model\CustomerModel;
use Admin\Model\OrderModel;
use Think\Controller;
/**
 * 用户相关控制器
 * Class User
 * @package app\admin\controller
 */
class FirstcustomerController extends BaseController
{
    /**
     * 获取用户列表
     * @return mixed
     */
    public function index(){
        $this->assign('search', $this->data);
        $where = array();
        if($this->data['name']){ $where['name'] = array('like','%'.$this->data['name'].'%');}
        if($this->data['mobile']){ $where['mobile'] = array('like','%'.$this->data['mobile'].'%');}
        if($this->data['city']){ $where['city'] = array('like','%'.$this->data['city'].'%');}
        if($this->data['channel']){ $where['channel'] = array('like','%'.$this->data['channel'].'%');}

        if($this->data['start_time']){ $where['create_time'][] = array('gt',strtotime($this->data['start_time']));}
        if($this->data['end_time']){ $where['create_time'][] = array('lt',(strtotime($this->data['end_time'])+24*60*60));}

        if($this->data['has_house']){ $where['has_house'] = array('eq',$this->data['has_house']);}
        if($this->data['has_car']){ $where['has_car'] = array('eq',$this->data['has_car']);}
        if($this->data['has_baodan']){ $where['has_baodan'] = array('eq',$this->data['has_baodan']);}
        if($this->data['has_gongjijin']){ $where['has_gongjijin'] = array('eq',$this->data['has_gongjijin']);}
        if($this->data['has_weilidai']){ $where['has_weilidai'] = array('eq',$this->data['has_weilidai']);}
        if($this->data['has_nasui']){ $where['has_nasui'] = array('eq',$this->data['has_nasui']);}

        if($this->data['min_money']){ $where['money'][] = array('EGT',$this->data['min_money']);}
        if($this->data['max_money']){ $where['money'][] = array('ELT',$this->data['max_money']);}

        if(isset($this->data['is_repeat'])&&($this->data['is_repeat']!=="")){ $where['is_repeat'] = $this->data['is_repeat'];}
        if(isset($this->data['status'])&&($this->data['status']!=="")){ $where['status'] = $this->data['status'];}

        $count = M('first_customer')->where($where)->count();
        $Page = new \Think\Page($count,$this->perpage);
        $list =  M('first_customer')->where($where)->Page($this->p,$this->perpage)->order($this->desc)->select();
        foreach($list as $k=>$v){
            $list[$k]['mobile'] = (new AuthModel())->doAuthToFirstCustomerPhone($this->userInfo['look_customer_phone'],$v['mobile']);
        }

        $this->assign('status_name',array('0'=>'未处理','1'=>'已处理','2'=>'处理失败','3'=>'废弃','4'=>'申请频繁无效'));
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $big_menu = array('title' => '添加申请', 'iframe' => U('Firstcustomer/add'), 'id' => 'add', 'width' => '500', 'height' => '250',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    public function show(){
        $info=M('first_customer')->where(array('id'=>I('get.id')))->find();
        $info['mobile'] =  (new AuthModel())->doAuthToFirstCustomerPhone($this->userInfo['look_customer_phone'],$info['mobile']);

        $info['ext']=json_decode($info['ext'],true);
        $customer_ext_filed = (new CacheLogic())->get_all_config()['customer_ext_filed'];
        $this->assign('info', $info);
        $this->assign('ext', $info['ext']);
        $this->assign('customer_ext_filed', $customer_ext_filed);
        $this->display();
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需废弃项');}
        $result=M('first_customer')->where(array('id'=>array('in',$ids)))->save(array('status'=>3));
        $result===false?$this->errorjsonReturn('废弃失败'):$this->setjsonReturn('废弃成功');
    }

    /**
     * 添加修改界面
     */
    public function add(){
        if (IS_POST) {
            $post=I('post.');
            foreach($post as $k=>$value){$post[$k]=trim($value);}
            if(!$post['name']||!$post['mobile']||!$post['city']||!isset($post['money'])||!$post['channel']){$this->errorjsonReturn('请完善信息');}
            if(!is_phone($post['mobile'])){$this->errorjsonReturn('请仔细填写手机号，确保不能有非法字符');}
            $add = array();
            $add['name'] = $post['name'];
            $add['mobile'] = $post['mobile'];
            $add['city'] = $post['city'];
            $add['money'] = $post['money'];
            $add['channel'] = $post['channel'];
            if( $post['has_house']){
                $add['has_house'] = $post['has_house'];
            }
            if( $post['has_car']){
                $add['has_car'] = $post['has_car'];
            }
            if( $post['has_gongjijin']){
                $add['has_gongjijin'] = $post['has_gongjijin'];
            }
            if( $post['has_baodan']){
                $add['has_baodan'] = $post['has_baodan'];
            }
            if( $post['has_weilidai']){
                $add['has_weilidai'] = $post['has_weilidai'];
            }
            if( $post['has_nasui']){
                $add['has_nasui'] = $post['has_nasui'];
            }
            if( $post['create_time']){
                $add['create_time'] = strtotime($post['create_time']);
            }else{
                $add['create_time']=time();
            }

            $add['is_repeat']=M('first_customer')->where(array('mobile'=>$post['mobile']))->count()>0?1:0;
            M('first_customer')->add($add)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
        }else{
            $this->display();
        }
    }

    /**
     * 导入获客商机
     */
    public function import(){
        $this->display();
    }
    /**
     * 对csv文件加工处理
     */
    public function csvProcess(){
        if(IS_POST){
            $result =(new ToolsLogic())->uploadCsv($_FILES['csv_file']);//上传csv文件
            if(!is_array($result)){exit($result);}
            $array=array();
            $str="";
            $can_action=true;
            foreach($result as $key=>$value){
                $array[trim($value[1])]=array(
                    'name'=>trim($value[0]),
                    'mobile'=>trim($value[1]),
                    'utm_source'=>trim($value[2]),
                    'loan_amount'=>trim($value[3]),
                    'city'=>trim($value[4]),
                    'has_house'=>trim($value[5]),
                    'has_car'=>trim($value[6]),
                    'has_gongjijin'=>trim($value[7]),
                    'has_baodan'=>trim($value[8]),
                    'has_weilidai'=>trim($value[9]),
                    'has_nasui'=>trim($value[10]),
                    'create_time'=>trim($value[11]),
                );
                if(!trim($value[0])){$str=$str."第".($key+1)."行的名字不能识别！！";$can_action=false; }
                if(!trim($value[1])){$str=$str."第".($key+1)."行的手机号不能识别！！";$can_action=false; }
                if(!trim($value[2])){$str=$str."第".($key+1)."行的渠道不能识别！！";$can_action=false; }
                if(!trim($value[4])){$str=$str."第".($key+1)."行的渠道不能识别！！";$can_action=false; }
            }
            if($can_action===false){exit($str);}
            //提出所有的手机号，判断重复性
            $telephonenumbers=array_column($array,'mobile');
            $utm_sources=array_unique(array_filter(array_column($array,'utm_source')));
            $all_channel_key=(new CacheLogic())->get_channel_key_manger();
            $all_channel=array();
            foreach($all_channel_key as $value){
                $cc=$value['child']?$value['child']:array();
                $all_channel=array_merge($all_channel,$cc);
            }
            foreach($utm_sources as $utm_source){
                if(!in_array($utm_source,$all_channel)){exit('您导入的客户中存在系统中不存在的渠道，请先检查！，例如：'.$utm_source);}
            }
            if(!$telephonenumbers){exit('文档中无可用数据');}
            $has_telephonenumbers=M('first_customer')->where(array('mobile'=>array('in',$telephonenumbers)))->group('mobile')->field('mobile')->select();
            $has_telephonenumbers=array_column($has_telephonenumbers,'mobile');
            foreach($array as $key=>$v){
                if(in_array($v['mobile'],$has_telephonenumbers)){
                    $array[$key]['status']='重复商机';
                }else{
                    $array[$key]['status']='新商机';
                }
            }
            $this->assign('list',$array);
            $this->display();
        }
    }

    /**
     * 商机跟踪
     */
    public function guiji(){
        $get=I('get.');
        $this->assign('search', $get);
        $order='id desc';
        if($get['sort']&&$get['order']){$order=$get['sort'].' '.$get['order'];}
        $where=array();
        $where['status']=array('in',array(1,3));
        if($get['name']){$where['name']=array('like','%'.$get['name'].'%');}
        if($get['mobile']){$where['mobile']=array('like','%'.$get['mobile'].'%');}
        if($get['city']){$where['city']=array('like','%'.$get['city'].'%');}
        if($get['channel']){$where['channel']=array('like','%'.$get['channel'].'%');}
        if($this->data['min_money']){ $where['money'][] = array('EGT',$this->data['min_money']);}
        if($this->data['max_money']){ $where['money'][] = array('ELT',$this->data['max_money']);}
        if($this->data['start_time']){ $where['create_time'][] = array('gt',strtotime($this->data['start_time']));}
        if($this->data['end_time']){ $where['create_time'][] = array('lt',(strtotime($this->data['end_time'])+24*60*60));}
        if(isset($this->data['is_repeat'])&&($this->data['is_repeat']!=="")){ $where['is_repeat'] = $this->data['is_repeat'];}
        $count = M('first_customer')->where($where)->count();
        $Page = new \Think\Page($count, $this->perpage);
        $list =  M('first_customer')->field('id,name,mobile,city,money,channel,create_time,is_repeat')->where($where)->Page($this->p,$this->perpage)->order($order)->select();
        foreach($list as $k=>$v){
            $list[$k]['mobile'] = (new AuthModel())->doAuthToFirstCustomerPhone($this->userInfo['look_customer_phone'],$v['mobile']);
        }
        $ids = array_column($list,'id');
        $orderInfos = array();
        if($ids) {
            $orderInfos = (new OrderModel())->where(array('first_customer_id' => array('in', $ids)))->getField('first_customer_id,number,company_id,create_time', true);
        }
        $companyInfos = array();
        $company_ids = array_column($orderInfos,'company_id');
        if($company_ids){
            $companyInfos = (new CompanyBranchModel())->where(array('id'=>array('in',$company_ids)))->getField('id,name',true);
        }
        $this->assign('status_name',array('0'=>'未处理','1'=>'已处理','2'=>'处理失败','3'=>'废弃'));
        $this->assign('orderInfos', $orderInfos);
        $this->assign('companyInfos', $companyInfos);
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $this->assign('list_table', true);
        $this->display();
    }

}

