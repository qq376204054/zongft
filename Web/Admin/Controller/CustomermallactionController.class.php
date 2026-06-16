<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Think\Controller;
/**
 * 客户买卖操作方法控制器
 * Class Customermall
 * @package app\admin\controller
 */
class CustomermallactionController extends BaseController
{

    //正在用接口：lfkjwl、jsyd、jtsj、sx_science、yulei、puff、fang、zhitong、eyw
    public $no_login_action = array(
        'lfkjwl','jsyd','jtsj','sx_science','yulei',
        'puff','fang','zhitong','eyw','shzq','szzyz',
        'kuaidai','jiaoyi','njyrw','ncm','ikcrm_101861','ikcrm_79337',
        'yxjinfu','saas_pull_data','sem_loan_do'
    );
    public $companyInfo = array();//外包合作商信息
    public $customerInfo = array();//客户信息

    /**
     * 自动执行函数
     */
    public function _initialize()
    {
        parent::_initialize();

        //下面开始默认的判断
        if(!$this->data['company_id']||!$this->data['first_customer_id']){
            $this->errorjsonReturn('数据有误，输出失败');
        }

        //下面获取买家的信息
        $this->companyInfo = M('mall_company')->where(array('id'=>$this->data['company_id']))->find();
        if(!$this->companyInfo){
            $this->errorjsonReturn('数据有误，输出失败');
        }
        $this->companyInfo['utm_source_config']=json_decode($this->companyInfo['utm_source_config'],true);

        //下面获取客户的信息
        $this->customerInfo=M('first_customer')->where(array('id'=>$this->data['first_customer_id']))->find();
        if(!$this->customerInfo){
            $this->errorjsonReturn('数据有误，输出失败');
        }

        //下面转化城市
        $allocateConfig = (new CacheLogic())->get_all_config();
        $this->customerInfo['city'] = filterCity($this->customerInfo['city'],$allocateConfig['allocate_filter_city']);
    }

    /**
     * 写入推送日志
     */
    public function addLog($api_ok,$api_error_msg,$api_back)
    {
        //开始写库
        $add=array();
        $add['first_customer_id'] = $this->data['first_customer_id'];
        $add['mall_company_id'] = $this->data['company_id'];
        $add['is_ok'] = $api_ok ? 1:2;
        $add['error_msg'] = $api_error_msg;
        $add['api_data'] = $api_back;
        $addReturn=M('mall_sale_log')->add($add);
        return $addReturn;
    }


    /**
     * 和明文接口对接---------对接1
     */
    public function mingwen(){
        //上面开始嶂库
        $tel=md5($this->customerInfo['mobile']);
        $url='http://47.93.10.34/crm/api/apic.php?pid='.$tel.'&cid=20';
        $html = file_get_contents($url);
        $htm = json_decode($html,true);
        $rep = $htm['stats']=='1'? 2 : 1;
        //下面开始推送客户
        $data=array();
        $data['cid'] = '20';
        $data['key'] = 'SWttZUOUmZlvbpo=';
        $data['username'] = $this->customerInfo['name'];
        $data['userphone'] = $this->customerInfo['mobile'];
        $data['rep'] = $rep;
        $data['city'] = $this->customerInfo['city'];
        $data['loan_amount'] = $this->customerInfo['money']/10000;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://47.93.10.34/crm/api/apia.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $output = curl_exec($ch);
        curl_close($ch);
        $res=json_decode($output,true);

        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $output;
        //接口解不出来
        if(!$res){
            $api_ok = false; $api_error_msg = '接口挂了';
        }elseif ($res['stats']!=1){
            //这里面只要推过了，就是成功
            $api_error_msg = $res['info']?$res['info']:'接口挂了';
        }

        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 苏州助易资----接口2
     */
    public function zuyizi(){
        $url = "http://47.103.81.204:8098/Customer/Add";
        $data=array();
        $data['Mobile'] = $this->customerInfo['mobile'];
        $data['CustomerName'] = $this->customerInfo['name'];
        $data['Sex'] = true;
        $data['Age'] = 0;
        $data['ApplyCity'] = $this->customerInfo['city'];
        $data['Money'] = $this->customerInfo['money']/10000;
        $data['LoanUse'] = "";
        $data['WageWay'] = "";
        $data['House'] = "";
        $data['Car'] = "";
        $data['Social'] = "";
        $data['Provident'] = "";
        $data['Warranty'] = "";
        $ret = http_post_json($url,json_encode($data),"");
        $http_status = $ret[0];
        $http_ret = json_decode($ret[1],true);

        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $ret[1];

        if($http_status!="200"){
            $api_ok = false; $api_error_msg = '接口挂了,httpCode:'.$http_status;
        } elseif (!$http_ret){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif ($http_ret['Result']!="Success"){
            //这里面只要推过了，就是成功
            $api_error_msg = $http_ret['Message']?$http_ret['Message']:'接口挂了';
        }

        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }


    /**
     * 和明文接口对接2----接口3
     */
    public function mingwen2(){
        //上面开始嶂库
        $tel=md5($this->customerInfo['mobile']);
        $url='http://47.93.10.34/crm/api/apic.php?pid='.$tel.'&cid=23';
        $html = file_get_contents($url);
        $htm = json_decode($html,true);
        $rep = $htm['stats']=='1'? 2 : 1;
        //下面开始推送客户
        $data=array();
        $data['cid'] = '23';
        $data['key'] = 'UWttZSOSmZlvbpo=';
        $data['username'] = $this->customerInfo['name'];
        $data['userphone'] = $this->customerInfo['mobile'];
        $data['rep'] = $rep;
        $data['city'] = $this->customerInfo['city'];
        $data['loan_amount'] = $this->customerInfo['money']/10000;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://47.93.10.34/crm/api/apia.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $output = curl_exec($ch);
        curl_close($ch);
        $res=json_decode($output,true);

        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $output;
        //接口解不出来
        if(!$res){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif ($res['stats']!=1){
            //这里面只要推过了，就是成功
            $api_error_msg = $res['info']?$res['info']:'接口挂了';
        }

        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 和成都api接口对接---------对接4
     */
    public function chengduapi(){
        //定义接口密匙
        $Token = "4F331BD2-BBA3-446A-B7F7-3869F7353C34";
        $CustomerCode = "0755190822093729N091";
        $url = "https://c.olakeji.cn/SendCustomer";


        $param = '{"customerCode": "'.$CustomerCode.'","accessTime": "'.date('Y-m-d H:i:s',time())
            .'","people": [{"CustomerCode": "'.$CustomerCode.'","Name": "'.$this->customerInfo['name'].'","Phone": "'.$this->customerInfo['mobile'].'","Sex": "'.$this->customerInfo['sex'].
            '","City": "'.$this->customerInfo['city'].'","NeedMoney": "'.$this->customerInfo['money'].'","CreateTime": "'.date('Y-m-d H:i:s',time()).
            '","Profession": "H5","IP": "192.168.0.1","Income": "7000","age": "40","car": "有","credit": "有信用卡或信用良好","housing": "有","lifeInsurancePolicy": "有","microLoan": "无","profession": "私营业主","providentFund": "有","salary": "银行","socialLnsurance": "有","source": "h5","workingHours": "4224","Birthday": "1988-08-15"}]}';

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param); // Post提交的数据包
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'x-ol-authtoken-ssl: '.$Token,
                'Content-Length: ' . strlen($param)
            )
        );
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作

        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $tmpInfo;
        curl_close($curl); // 关闭CURL会话
        $arra = json_decode($tmpInfo,true);

        if(!$arra){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif (!$arra['ReturnResult']){
            //这里面只要推过了，就是成功
            $api_error_msg = '接口返回结果格式有误';
        } elseif (!$arra['ReturnResult'][0]){
            //这里面只要推过了，就是成功
            $api_error_msg = '接口返回结果格式有误';
        } elseif ($arra['ReturnResult'][0]['Result']<1){
            //这里面只要推过了，就是成功
            $api_error_msg = $arra['ReturnResult'][0]['Remark']?$arra['ReturnResult'][0]['Remark']:'接口有误';
        }

        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }



    /**
     * 云管家的对接
     */
    public function yunguanjia(){
        $url = "http://data.pujiangpay.com/api/pjwstg34/add_customer";
        $url = $url."?mobile=".$this->customerInfo['mobile']."&name=".$this->customerInfo['name']."&age=30&city=".$this->customerInfo['city']."市&is_house=1&sex=1&is_car=1&is_company=0&is_credit=1&is_insurance=1&is_social=1&is_work=1&is_fund=1&is_tax=1&money_demand=".$this->customerInfo['money']."&remarks=&source=F&webank=0";
        $output = get_url($url);
        $res = json_decode($output,true);

        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $output;
        if(!$res){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif ($res['status']!="success"){
            //这里面只要推过了，就是成功
            $api_ok = false;
            $api_error_msg = $res['msg']?$res['msg']:'接口挂了';
        }
        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 陈涛的系统
     */
    public function chentao(){
        $url = "http://www.njyrw.com/index.php/api/index/add.html";
        $param = array();
        $param['key'] = 'b2eIjxuuDkNWFONEemPM2p7X65dBt88R7m8Pl2zsSYo3I74pTt';
        $param['name'] = $this->customerInfo['name'];
        $param['mobile'] = $this->customerInfo['mobile'];
        $param['city'] = $this->customerInfo['city'];
        $param['money'] = $this->customerInfo['money'];
        $output = post_url($url,$param);
        $res = json_decode($output,true);

        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $output;
        if(!$res){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif($res['errNum']!=0){
            //这里面只要推过了，就是成功
            $api_ok = false;
            $api_error_msg = $res['retData']? $res['retData']:'接口挂了';
        }
        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }


    /**
     * 陈涛的系统
     */
    public function shandaiwang(){
        $url = "https://www.hrongcms.com/sem/loan_do.html";
        $param = array();
        $param['name'] = $this->customerInfo['name'];
        $param['mobile'] = $this->customerInfo['mobile'];
        $param['city'] = $this->customerInfo['city'];
        $param['money'] = $this->customerInfo['money']/10000;
        $param['source'] = "pyq1";
        $param['time'] = time();
        $output = post_https_url($url,$param);
        $putNum = (int)$output;

        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $output;
        if(!$putNum){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif($putNum<10000){
            $api_ok = false;
            if($putNum==3){
                $api_error_msg = '指定时间内重复申请';
            }else if($putNum==5){
                $api_error_msg = '失败';
            }else if($putNum==6){
                $api_error_msg = '恶意IP';
            }else if($putNum==7){
                $api_error_msg = '恶意电话';
            }else if($putNum==8){
                $api_error_msg = '不在23岁-58岁范围内';
            }else if($putNum==9){
                $api_error_msg = '无房，无车，无寿险保单，无公积金，微粒贷额度在2万以下';
            }else if($putNum==10){
                $api_error_msg = '不是需求城市';
            }else if($putNum==11){
                $api_error_msg = '00:00-08:00内不允许入库';
            }else{
                $api_error_msg = '其他错误';
            }
        }

        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 汇融的系统
     */
    public function huirong(){
        $url = "https://www2.hrongcms.com/sem/loan_do.html";
        $param = array();
        $param['name'] = $this->customerInfo['name'];
        $param['mobile'] = $this->customerInfo['mobile'];
        $param['city'] = $this->customerInfo['city'];
        $param['money'] = $this->customerInfo['money']/10000;
        $param['source'] = "pyq2";
        $param['time'] = time();
        $output = post_https_url($url,$param);
        $putNum = (int)$output;

        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $output;
        if(!$putNum){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif($putNum<10000){
            $api_ok = false;
            if($putNum==3){
                $api_error_msg = '指定时间内重复申请';
            }else if($putNum==5){
                $api_error_msg = '失败';
            }else if($putNum==6){
                $api_error_msg = '恶意IP';
            }else if($putNum==7){
                $api_error_msg = '恶意电话';
            }else if($putNum==8){
                $api_error_msg = '不在23岁-58岁范围内';
            }else if($putNum==9){
                $api_error_msg = '无房，无车，无寿险保单，无公积金，微粒贷额度在2万以下';
            }else if($putNum==10){
                $api_error_msg = '不是需求城市';
            }else if($putNum==11){
                $api_error_msg = '00:00-08:00内不允许入库';
            }else{
                $api_error_msg = '其他错误';
            }
        }

        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }


    /**
     * 超冠教育科技有限公司
     */
    public function chaoguan(){
        $url = "http://121.41.47.218/index.php/api/index/add.html";
        $param = array();
        $param['key'] = 'hotPbSdGlCUjolPh4mdE0h2YDY4yLSFaeC8yIQ6soFY4IM20Og';
        $param['name'] = $this->customerInfo['name'];
        $param['mobile'] = $this->customerInfo['mobile'];
        $param['city'] = $this->customerInfo['city'];
        $param['money'] = $this->customerInfo['money'];
        $output = post_url($url,$param);
        $res = json_decode($output,true);

        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $output;
        if(!$res){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif($res['errNum']!=0){
            //这里面只要推过了，就是成功
            $api_ok = false;
            $api_error_msg = $res['retData']? $res['retData']:'接口挂了';
        }
        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }


    /**
     * 上海亓亘商务咨询有限公司
     */
    public function qigen(){
        $url = "http://47.96.8.207/index.php/api/index/add.html";
        $param = array();
        $param['key'] = '5mK2UhlXQLAzsUNpfrGQ9pun51dCRMJO96gvdcm5aWkedkdkUF';
        $param['name'] = $this->customerInfo['name'];
        $param['mobile'] = $this->customerInfo['mobile'];
        $param['city'] = $this->customerInfo['city'];
        $param['money'] = $this->customerInfo['money'];
        $output = post_url($url,$param);
        $res = json_decode($output,true);

        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $output;
        if(!$res){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif($res['errNum']!=0){
            //这里面只要推过了，就是成功
            $api_ok = false;
            $api_error_msg = $res['retData']? $res['retData']:'接口挂了';
        }
        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 小微合作方
     */
    public function xiaowei(){
        $url = "http://data.xiaoweiws.com/data/api.datasource/add";
        $param = array();
        $param['code'] = 'jttg01';
        $param['flag'] = 'jttg01';
        $param['name'] = $this->customerInfo['name'];
        $param['phone'] = $this->customerInfo['mobile'];
        $param['city'] = $this->customerInfo['city'].'市';
        $param['money'] = $this->customerInfo['money'];
        $param['age'] = "30";
        $param['sex'] = $this->customerInfo['sex']=='男'?1:0;
        $param['house'] = 1;
        $param['car'] = 1;
        $param['insurance'] = 1;
        $param['fund'] = 1;
        $param['webank'] =1;
        $param['tax'] = 1;
        $param['work'] = 1;
        $param['social'] = 1;
        $param['credit'] = 1;
        $output = post_url($url,$param);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $output;
        if(!$res){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif($res['code']!=500){
            $api_ok = false;
            $api_error_msg = $res['msg']? $res['msg']:'接口挂了';
        }
        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /*========================== 正在用接口 start  ==============================*/
    /**
     * 垒服网络信息科技有限公司
     */
    public function lfkjwl(){
        $url = "http://www.xsdlfkj.com/index.php/api/index/add.html";
        $param = array();
        $param['key'] = 'gpZ2uYxD5xyphQnr7Ie8ImSNoKHuXz741Nrx5mUFGZSOvPgT6u';
        $param['name'] = $this->customerInfo['name'];
        $param['mobile'] = $this->customerInfo['mobile'];
        $param['city'] = $this->customerInfo['city'];
        $param['money'] = $this->customerInfo['money'];
        $output = post_url($url,$param);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $output;
        if(!$res){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif($res['errNum']!=0){
            //这里面只要推过了，就是成功
            $api_ok = false;
            $api_error_msg = $res['retData']? $res['retData']:'接口挂了';
        }
        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 建商易贷
     */
    public function jsyd(){
        //先进行验重
        $url = "http://jianshang.heiym.com/api/nsxf/add_user_info";
        $param = array();
        $param['mobile'] = $this->customerInfo['mobile'];
        $param['name'] = $this->customerInfo['name'];
        $param['age'] = 30;
        $param['sex'] = $this->customerInfo['sex']=='男'?1:2;
        $param['ip'] = '127.0.0.1';
        $param['city'] = $this->customerInfo['city'];
        $param['loan_amount'] = $this->customerInfo['money']/10000;
        $param['houses'] = $this->customerInfo['has_house']=='无'?1:3;
        $param['car'] = $this->customerInfo['has_car']=='无'?1:3;
        $param['life_policy'] = $this->customerInfo['has_baodan']=='无'?2:1;
        $param['social_security '] = 1;
        $param['reg_time'] = time();
        $param['particle_loan'] = $this->customerInfo['has_weilidai']=='无'?1:3;
        $output = post_url($url,$param);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $output;
        if(!$res){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif($res['code']!=200){
            //这里面只要推过了，就是成功
            $api_ok = false;
            $api_error_msg = $res['msg']? $res['msg']:'接口挂了';
        }
        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);

    }

    /**
     * 天明天逸云 据通数据
     */
    public function jtsj(){
        $url = "https://api.tmtianyiyun.com/fjs/tiger-backend/api/index/add?key=abdCFmwDMbKfHF5H4HnEWFFY445sfnTb&access_token=e10adc3949ba59abbe56e057f20f883e";
        $param = array();
        $param['customer_source'] = "获客";
        $param['utm_source'] = "jutongcpa";
        $param['telephonenumber'] = $this->customerInfo['mobile'];
        $param['name'] = $this->customerInfo['name'];
        $param['loan_amount'] = (int)$this->customerInfo['money'];
        $param['city'] = $this->customerInfo['city'];
        $param['accumulation_fund'] = $this->customerInfo['has_gongjijin']=='无'?0:1;
        $param['social_security'] = 1;
        $param['life_insurance'] = 0;
        $param['policy'] =  $this->customerInfo['has_baodan']=='无'?0:1;
        $param['particle_loan'] = $this->customerInfo['has_weilidai']=='无'?0:1;
        $param['credit_card'] = 1;
        $param['car'] = $this->customerInfo['has_car']=='无'?0:1;
        $param['house'] = $this->customerInfo['has_house']=='无'?0:1;
        $ret = https_post_json($url,json_encode($param),"");
        $http_status = $ret[0];
        $http_ret = json_decode($ret[1],true);
        //开始分析推送结果
        $api_ok = true;
        $api_error_msg = "";
        $api_back = $ret[1];
        if($http_status!="200"){
            $api_ok = false; $api_error_msg = '接口挂了,httpCode:'.$http_status;
        } elseif (!$http_ret){
            $api_ok = false; $api_error_msg = '接口挂了';
        } elseif ($http_ret['errNum']!="1"){
            //这里面只要推过了，就是成功
            $api_ok = false;
            $api_error_msg = $http_ret['errMsg']?$http_ret['errMsg']:'接口挂了';
        }
        $this->addLog($api_ok,$api_error_msg,$api_back);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 晟兴科技
     */
    public function sx_science(){
        $url = "www.shenglidai.net/index.php/api/index/add.html";
        $params = array(
            'name' => $this->customerInfo['name'],//客户姓名
            'mobile' => $this->customerInfo['mobile'],//手机号
            'city' => $this->customerInfo['city'],//城市
            'money' => (int)$this->customerInfo['money'],//需求金额
            'key' => 'ptlpE8D3gFA3gRQbiUQB351MD1u9KcjY9u3thBZNt2aJxV6Xij',//密钥
            'sex' => $this->customerInfo['sex'],//性别（男/女）
            'has_house' => $this->customerInfo['has_house'],//是否有房（有/无）
            'has_car' => $this->customerInfo['has_car'],//是否有车
            'has_baodan' => $this->customerInfo['has_baodan'],//有无保单
            'has_gongjijin' => $this->customerInfo['has_gongjijin'],//是否有公积金
            'has_weilidai' => $this->customerInfo['has_weilidai'],//有无微粒贷
            'wenlidai' => '',//微粒贷额度
            'has_nasui' => $this->customerInfo['has_nasui'],//有无纳税
            'mayi_score' => '',//蚂蚁积分值
            'has_job' => '',//有无工作
            'has_credit_card' => '',//有无信用卡
            'can_phone_time' => '',//能电话沟通时间
            'channel' => '',//渠道分支
        );
        $filtered_array	= $this->para_filter($params);
        $output = post_url($url, $filtered_array);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true; $api_error_msg = "";
        if(!$res){
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } elseif($res['errNum']!=0){
            $api_ok = false;
            $api_error_msg = $res['errMsg'];
        }
        $this->addLog($api_ok,$api_error_msg,$output);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 毓雷咨询
     * 2020-05-26
     */
    public function yulei()
    {
        $url = "http://121.43.173.48/index.php/api/index/add.html";
        $params = array(
            'name' => $this->customerInfo['name'],//客户姓名
            'mobile' => $this->customerInfo['mobile'],//手机号
            'city' => $this->customerInfo['city'],//城市
            'money' => (int)$this->customerInfo['money'],//需求金额
            'key' => '89OL5MyWtyn1pULFww3Q7D55LIbxUTQge3b51eWVut8voKlsNp',//密钥
            'sex' => $this->customerInfo['sex'],//性别（男/女）
            'has_house' => $this->customerInfo['has_house'],//是否有房（有/无）
            'has_car' => $this->customerInfo['has_car'],//是否有车
            'has_baodan' => $this->customerInfo['has_baodan'],//有无保单
            'has_gongjijin' => $this->customerInfo['has_gongjijin'],//是否有公积金
            'has_weilidai' => $this->customerInfo['has_weilidai'],//有无微粒贷
            'has_nasui' => $this->customerInfo['has_nasui'],//有无纳税
            'channel' => '',//主渠道分支，未填写时为默认渠道
        );
        $filtered_array	= $this->para_filter($params);
        $output = post_url($url, $filtered_array);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true; $api_error_msg = "";
        if(!$res){
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } elseif($res['errNum']!=0){
            $api_ok = false;
            $api_error_msg = $res['errMsg'];
        }
        $this->addLog($api_ok,$api_error_msg,$output);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 小泡芙
     * 2020-06-09
     */
    public function puff()
    {
        $url = "http://47.103.131.133/index.php/api/index/add.html";
        $params = array(
            'name' => $this->customerInfo['name'],//客户姓名
            'mobile' => $this->customerInfo['mobile'],//手机号
            'city' => $this->customerInfo['city'],//城市
            'money' => (int)$this->customerInfo['money'],//需求金额
            'key' => '0gU1QfiCnDPolN9qU4IdeglDUwOgAKGVbQ5FQH20yrZySAf8mz',//密钥
            'sex' => $this->customerInfo['sex'],//性别（男/女）
            'has_house' => $this->customerInfo['has_house'],//是否有房（有/无）
            'has_car' => $this->customerInfo['has_car'],//是否有车
            'has_baodan' => $this->customerInfo['has_baodan'],//有无保单
            'has_gongjijin' => $this->customerInfo['has_gongjijin'],//是否有公积金
            'has_weilidai' => $this->customerInfo['has_weilidai'],//有无微粒贷
            'has_nasui' => $this->customerInfo['has_nasui'],//有无纳税
            'channel' => '',//主渠道分支，未填写时为默认渠道
        );
        $filtered_array	= $this->para_filter($params);
        $output = post_url($url, $filtered_array);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true; $api_error_msg = "";
        if(!$res){
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } elseif($res['errNum']!=0){
            $api_ok = false;
            $api_error_msg = $res['errMsg'];
        }
        $this->addLog($api_ok,$api_error_msg,$output);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 北京景鑫
     * 2020-06-09
     */
    public function fang()
    {
        $url = "http://crm.fangjingxin.com/api/addoutsitecustomer/insert_customer/key/166a192b7917e14c54554d18c28d48e6595428fb";
        $params = array(
            'name' => $this->customerInfo['name'],//姓名
            'phone' => $this->customerInfo['mobile'],//手机号
            'age' => '',//年龄
            'city' => $this->customerInfo['city'],//城市
            'amount' => (int)$this->customerInfo['money'],//需求金额
            'term' => '',//分期数
            'car' => $this->customerInfo['has_car'] == '有' ? 1 : 2,//是否有车
            'house' => $this->customerInfo['has_house'] == '有' ? 1 : 4,//是否有房 商品房、经济适用房、有房无贷、无房、有房有贷、其他
            'insurance' => $this->customerInfo['has_baodan'] == '有' ? 1 : 2,//有无保险
            'social_security' => '',//有无社保
            'particle_loan' => $this->customerInfo['has_weilidai'] == '有' ? 1 : 2,//有无微粒贷
            'id_card' => '',//身份证号
        );
        $filtered_array	= $this->para_filter($params);
        $output = post_url($url, $filtered_array);
        $res = json_decode($output, true);
        //开始分析推送结果
        $api_ok = true; $api_error_msg = "数据中心添加成功!";
        if(!$res){
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } elseif($res['code'] != 2000){
            $api_ok = false;
            $api_error_msg = $res['info'];
        }
        $this->addLog($api_ok,$api_error_msg,$output);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 上海致同
     * 2020-06-10
     */
    public function zhitong()
    {
        $url = "http://47.96.80.159/index.php/api/index/add.html";
        $params = array(
            'name' => $this->customerInfo['name'],//客户姓名
            'mobile' => $this->customerInfo['mobile'],//手机号
            'city' => $this->customerInfo['city'],//城市
            'money' => (int)$this->customerInfo['money'],//需求金额
            'key' => 'Y0WdHAtsQNKhenYXn6JeebhdqiNhlsfCQyN4byDfbDNqbAZA7E',//密钥
            'sex' => $this->customerInfo['sex'],//性别（男/女）
            'has_house' => $this->customerInfo['has_house'],//是否有房（有/无）
            'has_car' => $this->customerInfo['has_car'],//是否有车
            'has_baodan' => $this->customerInfo['has_baodan'],//有无保单
            'has_gongjijin' => $this->customerInfo['has_gongjijin'],//是否有公积金
            'has_weilidai' => $this->customerInfo['has_weilidai'],//有无微粒贷
            'has_nasui' => $this->customerInfo['has_nasui'],//有无纳税
            'channel' => '',//主渠道分支，未填写时为默认渠道
        );
        $filtered_array	= $this->para_filter($params);
        $output = post_url($url, $filtered_array);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true; $api_error_msg = "";
        if(!$res){
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } elseif($res['errNum']!=0){
            $api_ok = false;
            $api_error_msg = $res['errMsg'];
        }
        $this->addLog($api_ok,$api_error_msg,$output);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * e云网 先手机号验重
     * 2020-06-15
     */
    public function eyw()
    {
        //初始化推送结果
        $api_ok = true; $api_error_msg = "";
        //参数组装
        $username = '00000017';//用户名
        $password = 'eyw017';//密码
        $mobile = $this->customerInfo['mobile'];
        $params = array(
            'mobile' => md5($mobile),//手机号 md5加密
            'username' => $username,//用户名
            'password' => strtolower(md5($username.$password))//用户名+密码 md5加密
        );
        //调手机号验重接口
        $url = "http://121.40.54.91:18180/system/source/checkMobile";
        $output = http_post_json($url, json_encode($params));
        if ($output[0] !== 200) {
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } else {
            $res = json_decode($output[1], true);
            if ($res && $res['code']==0) {
                //开始推入数据
                $url = "http://121.40.54.91:18180/system/source/api/";
                $params = array(
                    'username' => $username,//e云网用户账号
                    'password' => strtolower(md5($username.$password)),//用户名+密码 md5加密
                    'name' => $this->customerInfo['name'],//客户姓名
                    'mobile' => $this->customerInfo['mobile'],//手机号
                    'age' => '1',//年龄
                    'city' => $this->customerInfo['city'],//城市
                    'sex' => '1',//性别（1.男2.女）
                    'house' => $this->customerInfo['has_house'] == '有' ? 2 : 1,//是否有房：1.无房，2.有房
                    'houseType' => '1',//房子类型 1.商品房，2.商铺，3.小产权房，4.厂房，5.宅基地/自建房，6.办公楼，7.其他
                    'car' => $this->customerInfo['has_car'] == '有' ? 2 : 1,//是否有车：1.无车，2.有车
                    'sheBao' => '1',//社保  1.无社保，2.有社保
                    'sheBaoTime' => '1',//社保时长 1.缴纳半年内，2.缴纳超半年
                    'fund' => $this->customerInfo['has_gongjijin'] == '有' ? 2 : 1,//公积金 1.无公积金，2.有公积金
                    'fundBase' => '',//公积金基数 （元）
                    'fundTime' => '',//公积金时长（月）
                    'xyCard' => '1',//信用卡：1无，2有
                    'xyCardLimit' => '',//信用卡额度（万元）
                    'shouXian' => $this->customerInfo['has_baodan'] == '有' ? 2 : 1,//人寿保险 1.无人寿保险，2.有人寿保险
                    'shouXianCost' => '',//人寿保险年缴额度  1.2400以内，2.2400以上
                    'weiLiDai' => $this->customerInfo['has_weilidai'] == '有' ? 2 : 1,//微粒贷: 1:无,2:有
                    'weiLiDaiLimit' => '',//微粒贷额度
                    'payway' => '',//发薪方式 1.银行卡代发，2.现金发放
                    'hire' => '',//职业 1.上班族，2.企业主
                    'income' => '',//收入(万元)
                    'loanAmount' => $this->customerInfo['money'] / 10000,//贷款金额 (单位：万)
                    'liveTime' => '',//居住时间(年)
                    'remark' => '',//备注
                    'province' => '',//所在省
                    'createBy' => '',//创建者
                );
                $filtered_array	= $this->para_filter($params);
                $output = http_post_json($url, json_encode($filtered_array));
                if ($output[0] !== 200) {
                    $api_ok = false;
                    $api_error_msg = '接口挂了';
                } else {
                    $response = json_decode($output[1], true);
                    if ($response && $response['code']==0) {
                        $api_ok = true;
                    } else {
                        $api_ok = false;
                        $api_error_msg = $response['msg'] ? $response['msg'] : '接口挂了';
                    }
                }
            } else {
                $api_ok = false;
                $api_error_msg = $res['msg'] ? $res['msg'] : '接口挂了';
            }
        }
        $this->addLog($api_ok,$api_error_msg,$output[1]);
        $api_ok ? $this->setjsonReturn('推送成功！') : $this->errorjsonReturn($api_error_msg);
    }

    /**
     * 上海盏清信息科技有限公司
     * 2020-06-18
     */
    public function shzq()
    {
        $url = "http://139.196.55.44/index.php/api/index/add.html";
        $params = array(
            'name' => $this->customerInfo['name'],//客户姓名
            'mobile' => $this->customerInfo['mobile'],//手机号
            'city' => $this->customerInfo['city'],//城市
            'money' => (int)$this->customerInfo['money'],//需求金额
            'key' => 'Y0WdHAtsQNKhenYXn6JeebhdqiNhlsfCQyN4byDfbDNqbAZA7E',//密钥
            'sex' => $this->customerInfo['sex'],//性别（男/女）
            'has_house' => $this->customerInfo['has_house'],//是否有房（有/无）
            'has_car' => $this->customerInfo['has_car'],//是否有车
            'has_baodan' => $this->customerInfo['has_baodan'],//有无保单
            'has_gongjijin' => $this->customerInfo['has_gongjijin'],//是否有公积金
            'has_weilidai' => $this->customerInfo['has_weilidai'],//有无微粒贷
            'has_nasui' => $this->customerInfo['has_nasui'],//有无纳税
            'channel' => '',//主渠道分支，未填写时为默认渠道
        );
        $filtered_array	= $this->para_filter($params);
        $output = post_url($url, $filtered_array);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true; $api_error_msg = "";
        if(!$res){
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } elseif($res['errNum']!=0){
            $api_ok = false;
            $api_error_msg = $res['errMsg'];
        }
        $this->addLog($api_ok,$api_error_msg,$output);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 苏州助易资
     * 2020-06-22
     */
    public function szzyz()
    {
        $url = "http://api.zhuyidai.net/Customer/Add";
        $params = array(
            'Mobile' => $this->customerInfo['mobile'],//客户手机号
            'CustomerName' => $this->customerInfo['name'],//客户姓名
            'Sex' => $this->customerInfo['sex'] == '男' ? false : true,//性别 false为男，true为女
            'Age' => '',//年龄
            'ApplyCity' => $this->customerInfo['city'],//申请城市
            'Money' => $this->customerInfo['money'] / 10000,//贷款金额 (单位：万)
            'LoanUse' => '',//贷款用途
            'WageWay' => '',//工资方式枚举（非必填） 枚举值为小写 转账：zz、现金：xj、代发：df
            'House' => $this->customerInfo['has_house'] == '有' ? 'yfyd' : 'wf',//名下房产枚举（非必填） 枚举值为小写 月供房：ygf、无房：wf、有房：yfyd、全款房：yfwd
            'Car' => $this->customerInfo['has_car'] == '有' ? 'y' : 'wc',//名下车产枚举（非必填） 枚举值为小写 有车：y、无车：wc、月供车：ycyd、全款车：ycwd
            'Social' => '',//社保情况枚举（非必填） 枚举值为小写 有：cbn、无：wsb
            'Provident' => $this->customerInfo['has_gongjijin'] == '有' ? 'ynn' : 'wgjj',//公积金枚举（非必填） 枚举值为小写 有：ynn、无：wgjj
            'Warranty' => $this->customerInfo['has_baodan'] == '有' ? 'ybd' : 'wbd',//有无保单枚举（非必填） 枚举值为小写 有：ybd、无：wbd
            'Source' => '',//
        );
        $filtered_array	= $this->para_filter($params);
        $output = http_post_json($url, json_encode($filtered_array));
        //初始化推送结果
        $api_ok = true; $api_error_msg = "";
        if ($output[0] !== 200) {
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } else {
            $response = json_decode($output[1], true);
            if ($response && $response['IsSuccess']==true) {
                $api_ok = true;
            } else {
                $api_ok = false;
                $api_error_msg = $response['Message'] ? $response['Message'] : '接口挂了';
            }
        }
        $this->addLog($api_ok,$api_error_msg,$output[1]);
        $api_ok ? $this->setjsonReturn('推送成功！') : $this->errorjsonReturn($api_error_msg);
    }

    /**
     * 快贷网
     * 2020-06-29
     */
    public function kuaidai()
    {
        $api_ok = true; $api_error_msg = "";$api_data="";//初始化推送结果
        $token = 'KhBQKxfMeElLPUNSQ66R0y9yW5kio4XM';//身份识别码
        $mobile = $this->customerInfo['mobile'];//手机号
        //先进行手机号验重
        $url = "http://crm.kuaidai188.com:8080/api/v1/customer/checkPhoneExists?token=".$token."&phone=".$mobile;
        $output = get_url($url);
        $response = json_decode($output, true);
        //判断手机号是否存在第三方系统
        if ($response && $response['code']==10000) {
            //注销不用元素
            unset($output);unset($response);
            //开始推送数据
            $url = "http://crm.kuaidai188.com:8080/api/v1/customer/add/";
            $params = array(
                'token' => 'KhBQKxfMeElLPUNSQ66R0y9yW5kio4XM',//token信息
                "name" => $this->customerInfo['name'],//姓名
                "phone" => $this->customerInfo['mobile'],//手机号
                "city" => $this->customerInfo['city'],//城市
                "apply_quota" => $this->customerInfo['money'] / 10000,//申请额度（单位：万）
                "remark" => array( //备注信息
                    "是否有车" => $this->customerInfo['has_car'],
                    "是否有公积金" => $this->customerInfo['has_gongjijin'],
                    "有无纳税" => $this->customerInfo['has_nasui']
                )
            );
            //开始请求接口
            $output = http_post_json($url, json_encode($params, JSON_UNESCAPED_UNICODE));
            if ($output[0] !== 200) {
                $api_ok = false;
                $api_error_msg = '接口挂了';
            } else {
                $response = json_decode($output[1], true);
                if ($response && $response['code']==10000) {
                    $api_ok = true;
                    $api_data = $output[1];
                } else {
                    $api_ok = false;
                    $api_error_msg = $response['msg'] ? $response['msg'] : '接口挂了';
                    $api_data = $output[1];
                }
            }
        } else {
            $api_ok = false;
            $api_error_msg = $response['msg'] ? $response['msg'] : '接口挂了';
            $api_data = $output;
        }
        $this->addLog($api_ok,$api_error_msg,$api_data);
        $api_ok ? $this->setjsonReturn('推送成功！') : $this->errorjsonReturn($api_error_msg);
    }

    /**
     * 骄奕商务信息
     * 2020-07-08
     */
    public function jiaoyi()
    {
        $url = "http://121.196.183.90/index.php/api/index/add.html";
        $params = array(
            'name' => $this->customerInfo['name'],//客户姓名
            'mobile' => $this->customerInfo['mobile'],//手机号
            'city' => $this->customerInfo['city'],//城市
            'money' => (int)$this->customerInfo['money'],//需求金额
            'key' => 'Y0WdHAtsQNKhenYXn6JeebhdqiNhlsfCQyN4byDfbDNqbAZA7E',//密钥
            'sex' => $this->customerInfo['sex'],//性别（男/女）
            'has_house' => $this->customerInfo['has_house'],//是否有房（有/无）
            'has_car' => $this->customerInfo['has_car'],//是否有车
            'has_baodan' => $this->customerInfo['has_baodan'],//有无保单
            'has_gongjijin' => $this->customerInfo['has_gongjijin'],//是否有公积金
            'has_weilidai' => $this->customerInfo['has_weilidai'],//有无微粒贷
            'has_nasui' => $this->customerInfo['has_nasui'],//有无纳税
            'channel' => '',//主渠道分支，未填写时为默认渠道
        );
        $filtered_array	= $this->para_filter($params);
        $output = post_url($url, $filtered_array);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true; $api_error_msg = "";
        if(!$res){
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } elseif($res['errNum']!=0){
            $api_ok = false;
            $api_error_msg = $res['errMsg'];
        }
        $this->addLog($api_ok,$api_error_msg,$output);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 诺进-银行贷
     * 2020-07-08
     */
    public function njyrw()
    {
        $url = "http://www.njyrw.com/index.php/api/index/add.html";
        $params = array(
            'name' => $this->customerInfo['name'],//客户姓名
            'mobile' => $this->customerInfo['mobile'],//手机号
            'city' => $this->customerInfo['city'],//城市
            'money' => (int)$this->customerInfo['money'],//需求金额
            'key' => 'b2eIjxuuDkNWFONEemPM2p7X65dBt88R7m8Pl2zsSYo3I74pTt',//密钥
            'sex' => $this->customerInfo['sex'],//性别（男/女）
            'has_house' => $this->customerInfo['has_house'],//是否有房（有/无）
            'has_car' => $this->customerInfo['has_car'],//是否有车
            'has_gongjijin' => $this->customerInfo['has_gongjijin'],//是否有公积金
        );
        $filtered_array	= $this->para_filter($params);
        $output = post_url($url, $filtered_array);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true; $api_error_msg = "";
        if(!$res){
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } elseif($res['errNum']!=0){
            $api_ok = false;
            $api_error_msg = $res['errMsg'];
        }
        $this->addLog($api_ok,$api_error_msg,$output);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * NCM 上海益秒信息科技
     * 2020-07-13
     */
    public function ncm()
    {
        //初始化接口请求结果
        $api_ok = false; $api_error_msg = "接口挂了";$api_data="";
        //客户手机号
        $mobile = $this->customerInfo['mobile'];
        //key值
        $key = 'dsjjkjjljlajfrijkji';
        //渠道号
        $channel = 100009;
        //手机号验重URL
        $url = "https://api.zgzdzx.cn/API/CustExists.ashx";
        //手机号验重参数
        $params = array(
            'Sign' => md5(md5($mobile).$key),//签名
            "Md5code" => md5($mobile),//手机号码MD5加密串
            "ChannelId" => $channel//渠道号
        );
        //开始请求接口
        $output = https_post_json($url, json_encode($params, JSON_UNESCAPED_UNICODE));
        if ($output[0] == 200) {
            $response = json_decode($output[1], true);
            if ($response && $response['ResultCode']==1) {
                //注销不用元素
                unset($params);unset($output);unset($response);
                //数据推入URL
                $url = "https://api.zgzdzx.cn/API/Customer.ashx";
                //数据推入参数
                $params = array(
                    'Sign' => md5($mobile.$key.'nsxf'),//签名 加密方法MD5(Phone+key+Adsid)
                    "Name" => $this->customerInfo['name'],//姓名
                    "Phone" => $this->customerInfo['mobile'],//手机号
                    'Sex' => $this->customerInfo['sex'] == '女' ? 2 : 1,//性别 0末知、1男、2女
                    "City" => $this->customerInfo['city'],//城市（不带市、地区等后缀）
                    'IsMarried' => 0,//婚否 0未知、1未婚、2已婚
                    'CareerType' => 0,//职业 0未知、1白领、2公务员、3私营业主、4企业主
                    'MonthlySalary' => 0,//月收入 0未知、1(4千以内) 、2(4千-1万)、3(1万以上)
                    'HousingFund' => 0,//公积金 0(未知)、1(1年以内)、2(1年以上)、3(无公积金)
                    'SheBao' => 0,//社保 0末知，1（1年以内），2（1年以上）3（无社保）
                    'HouseProperty' => 0,//房产情况 0(未知)、1(有房贷)、2(有房无贷)、3(无房)
                    'PropertyOwnership' => 0,//房产归属地 0末知1 本地房2 外地房
                    'CarProperty' => 0,//车产情况 0(未知)、1(有车贷)、2(有车无贷)、3(无车)
                    'IsHasBx' => 0,//该客户是否有保单 0 未知1有2无
                    'RecentCredit' => 0,//信用情况 0 未知1信用良好2少数逾期 3征信不良 4无信用卡或无贷款
                    'LoanAmount' => $this->customerInfo['money'] / 10000,//客户申请的额度（万元）
                    'IsInterview' => 0,//是否接受面签 0未知 1不接受 2接受
                    'HasWeilidai' => 0,//是否有微粒贷 0未知 1无 2有
                    'AdvType' => 0,//数据类型 0在线获客数据 1其它数据
                    'ChannelId' => $channel,//渠道标识（由公司分配）
                    'AdsId' => 'nsxf',//合作方内部的渠道标识
                    'CreateDate' => date('Y-m-d H:i:s')//客户申请时间（yyyy-MM-dd HH:mm:ss）
                );
                //开始请求接口
                $output = https_post_json($url, json_encode($params, JSON_UNESCAPED_UNICODE));
                if ($output[0] == 200) {
                    $response = json_decode($output[1], true);
                    if ($response && $response['ResultCode']==1) {
                        $api_ok = true;
                        $api_error_msg = '';
                        $api_data = $output[1];
                    } else {
                        $api_error_msg = $response['ResultMsg'] ? $response['ResultMsg'] : '接口挂了';
                        $api_data = $output[1];
                    }
                }
            } else {
                $api_error_msg = $response['ResultMsg'] ? $response['ResultMsg'] : '接口挂了';
                $api_data = $output[1];
            }
        }
        $this->addLog($api_ok,$api_error_msg,$api_data);
        $api_ok ? $this->setjsonReturn('推送成功！') : $this->errorjsonReturn($api_error_msg);
    }

    /**
     * 深圳市前海中天振华投资有限公司
     * 爱客CRM系统对接
     * http://apidoc.weiwenjia.com/docs/crm_open_api
     * 2020-07-15
     */
    public function ikcrm_101861()
    {
        //初始化接口请求结果
        $api_ok = false; $api_error_msg = "接口挂了";$api_data="";
        //先调用登录接口获取user_token
        $url = "https://api.ikcrm.com/api/v2/auth/login";
        //登录接口参数
        $params = array(
            'device' => 'open_api',//固定值
            "login" => '17621842266',//用户手机号
            "password" => 'As123456',//密码
            "corp_id" => 'wwjPad6RKgxonUPqabcn'//企业ID
        );
        //开始请求接口
        $output = https_post_json($url, json_encode($params, JSON_UNESCAPED_UNICODE));
        if ($output[0] == 200) {
            $response = json_decode($output[1], true);
            if ($response && $response['code']==0 && $response['data']['user_token']) {
                //获取用户token
                $token = $response['data']['user_token'];
                //注销不用元素
                unset($params);unset($output);unset($response);
                //数据推入URL
                $url = "https://api.ikcrm.com/api/v2/common_customers";
                //数据推入参数
                $params = array(
                    "common_id" => '101861',//公海ID,不能删除
                );
                $params['customer'] = array(
                    "name" => $this->customerInfo['name'],//客户姓名
                    "address_attributes" => array(
                        "phone" => $this->customerInfo['mobile'],//手机号码
                        "detail_address" => $this->customerInfo['city'],//城市
                    ),
                    "text_asset_5755b9" => $this->customerInfo['sex'] == '女' ? 'sel_22b9' : 'sel_2628',//性别 男sel_2628 女sel_22b9
                    'text_asset_22996e' => $this->customerInfo['has_house'] == '有' ? 'sel_214f' : 'sel_13fd',//是否有房 有sel_214f 无sel_13fd
                    'text_asset_a2e550' => $this->customerInfo['has_car'] == '有' ? 'sel_5069' : 'sel_2575',//是否有车 有sel_5069 无sel_2575
                    'text_asset_ec7776' => $this->customerInfo['has_baodan'] == '有' ? 'sel_7390' : 'sel_b6e3',//有无保单 有sel_7390 无sel_b6e3
                    'text_asset_953932' => $this->customerInfo['has_gongjijin'] == '有' ? 'sel_3a21' : 'sel_afa7',//是否有公积金 有sel_3a21 无sel_afa7
                    'text_asset_6f88bc' => $this->customerInfo['has_weilidai'] == '有' ? 'sel_e8c9' : 'sel_3ebb',//有无微粒贷 有sel_e8c9 无sel_3ebb
                    'numeric_asset_1f938f' => (int)$this->customerInfo['money'],//贷款金额（万元）
                );
                //请求参数json串
                $jsonStr = json_encode($params, JSON_UNESCAPED_UNICODE);
                //通过GET方式获取自定义扩展字段
                //$url = "https://api.ikcrm.com/api/v2/custom_fields/customer/by_group";
                //通过CURL请求接口
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);// 访问地址
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
                curl_setopt($ch, CURLOPT_POST, 1); //请求方式
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr); //请求json串
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);// 获取的信息以文件流的形式返回
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json;charset=utf-8',
                        'Content-Length: ' . strlen($jsonStr),
                        'Authorization: Token token=' .$token. ', device=open_api, version_code=9.9.9'
                    )
                );
                $output = curl_exec($ch);
                curl_close($ch);
                $response = json_decode($output, true);
                if ($response && $response['code']==0) {
                    $api_ok = true;
                    $api_error_msg = '';
                    $api_data = json_encode(array('code'=>0,'msg'=>'自定义推送成功'), JSON_UNESCAPED_UNICODE);
                } else {
                    $api_error_msg = $response['message'] ? $response['message'] : '接口挂了';
                    $api_data = $output;
                }
            } else {
                $api_error_msg = $response['message'] ? $response['message'] : '接口挂了';
                $api_data = $output[1];
            }
        }
        $this->addLog($api_ok, $api_error_msg, $api_data);
        $api_ok ? $this->setjsonReturn('推送成功！') : $this->errorjsonReturn($api_error_msg);
    }

    /**
     * 亿榕金服
     * 爱客CRM系统对接
     * http://apidoc.weiwenjia.com/docs/crm_open_api
     * 2020-07-15
     */
    public function ikcrm_79337()
    {
        //初始化接口请求结果
        $api_ok = false; $api_error_msg = "接口挂了";$api_data="";
        //先调用登录接口获取user_token
        $url = "https://api.ikcrm.com/api/v2/auth/login";
        //登录接口参数
        $params = array(
            'device' => 'open_api',//固定值
            "login" => '13816004904',//用户手机号
            "password" => 'Jxd19901126',//密码
            "corp_id" => 'wwjvaaj9bVzQxLFKvnso'//企业ID
        );
        //开始请求接口
        $output = https_post_json($url, json_encode($params, JSON_UNESCAPED_UNICODE));
        if ($output[0] == 200) {
            $response = json_decode($output[1], true);
            if ($response && $response['code']==0 && $response['data']['user_token']) {
                //获取用户token
                $token = $response['data']['user_token'];
                //注销不用元素
                unset($params);unset($output);unset($response);
                //数据推入URL
                $url = "https://api.ikcrm.com/api/v2/common_customers";
                //数据推入参数
                $params = array(
                    "common_id" => '79337',//公海ID,不能删除
                );
                $params['customer'] = array(
                    "name" => $this->customerInfo['name'],//客户姓名
                    "address_attributes" => array(
                        "tel" => $this->customerInfo['mobile'],//手机号码
                        "detail_address" => $this->customerInfo['city'],//城市
                    ),
                    "text_asset_f6430a" => $this->customerInfo['sex'] == '女' ? 'sel_d402' : 'sel_335b',//性别 男 女
                    'text_asset_2dd446' => $this->customerInfo['has_house'] == '有' ? 'sel_de62' : 'sel_f7d1',//是否有房 有 无
                    'text_asset_6560ab' => $this->customerInfo['has_car'] == '有' ? 'sel_fbc5' : 'sel_9d25',//是否有车 有 无
                    'text_asset_43ff71' => $this->customerInfo['has_baodan'] == '有' ? 'sel_c838' : 'sel_e470',//有无保单 有 无
                    'text_asset_8e7187' => $this->customerInfo['has_gongjijin'] == '有' ? 'sel_297d' : 'sel_8eb5',//是否有公积金 有 无
                    'text_asset_ba229a' => $this->customerInfo['has_weilidai'] == '有' ? 'sel_eb94' : 'sel_44da',//有无微粒贷 有 无
                    'numeric_asset_a61cc3' => (int)$this->customerInfo['money'],//贷款金额（万元）
                );
                //请求参数json串
                $jsonStr = json_encode($params, JSON_UNESCAPED_UNICODE);
                //通过CURL请求接口
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);// 访问地址
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
                curl_setopt($ch, CURLOPT_POST, 1); //请求方式
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr); //请求json串
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);// 获取的信息以文件流的形式返回
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json;charset=utf-8',
                        'Content-Length: ' . strlen($jsonStr),
                        'Authorization: Token token=' .$token. ', device=open_api, version_code=9.9.9'
                    )
                );
                $output = curl_exec($ch);
                curl_close($ch);
                $response = json_decode($output, true);
                if ($response && $response['code']==0) {
                    $api_ok = true;
                    $api_error_msg = '';
                    $api_data = json_encode(array('code'=>0,'msg'=>'自定义推送成功'), JSON_UNESCAPED_UNICODE);
                } else {
                    $api_error_msg = $response['message'] ? $response['message'] : '接口挂了';
                    $api_data = $output;
                }
            } else {
                $api_error_msg = $response['message'] ? $response['message'] : '接口挂了';
                $api_data = $output[1];
            }
        }
        $this->addLog($api_ok, $api_error_msg, $api_data);
        $api_ok ? $this->setjsonReturn('推送成功！') : $this->errorjsonReturn($api_error_msg);
    }

    /**
     * 中天农商
     * 2020-07-28
     */
    public function yxjinfu(){
        $url = "http://qudao.yxjinfu.com/api/qudao/import_ztns";
        $params = array(
            'file_id' => 422,//固定值
            'name' => $this->customerInfo['name'],//客户姓名
            'mobile' => $this->customerInfo['mobile'],//手机号码
            'age' => 0,//年龄
            'sex' => 0,//性别（1:为男,2:为女,无传0）
            'city' => $this->customerInfo['city'],//城市
            'is_house' => $this->customerInfo['has_house'] == '有' ? 1 : 0,//是否有房（有1/无0）
            'is_car' => $this->customerInfo['has_car'] == '有' ? 1 : 0,//是否有车
            'is_company' => 0,//是否有公司营业执照
            'is_credit' => 0,//是否有信用卡
            'is_insurance' => $this->customerInfo['has_baodan'] == '有' ? 1 : 0,//是否有保单
            'is_social' => 0,//是否有社保
            'is_fund' => $this->customerInfo['has_gongjijin'] == '有' ? 1 : 0,//是否有公积金
            'is_work' => 0,//是否有打卡工资
            'is_tax' => 0,//是否有营业税
            'webank' => 0,//微粒贷额度
            'money_demand' => (int)$this->customerInfo['money'],//申请金额
        );
        $output = post_url($url, $params);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true; $api_error_msg = "";
        if(!$res){
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } elseif($res['code'] != 0){
            $api_ok = false;
            $api_error_msg = $res['msg'];
        }
        $this->addLog($api_ok,$api_error_msg,$output);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }


    /**
     * 外推客户数据接口
     * 2020-08-05
     */
    public function saas_pull_data()
    {
        $url = "http://139.196.226.77:15081/api/pull/oderData";
        $params = array(
            'realName' => $this->customerInfo['name'],//客户姓名
            'phone' => $this->customerInfo['mobile'],//手机号码
            'cstSource' => 402,//数据来源，不可设置为100和200
            'loanMoney' => (int)$this->customerInfo['money'] / 10000,//借款金额（单位：万元）
            'city' => $this->customerInfo['city'],//城市
//            'age' => '',//年龄
//            'sex' => '',//性别
//            'province' => '',//省份
//            'idcard' => '',//身份证号码
//            'sesameScore' => 0,//芝麻分 0：无芝麻分 1：600分以下 2：600~650分 3：650~700分 4：700分以上
//            'loanExpiresMonth' => '',//借款期限（单位：月）
//            'house' => $this->customerInfo['has_house'] == '有' ? 1 : 0,//是否有房
//            'car' => $this->customerInfo['has_car'] == '有' ? 1 : 0,//是否有车
//            'baodanScop' => $this->customerInfo['has_baodan'] == '有' ? 1 : 0,//是否有保单
//            'gjjScop' => $this->customerInfo['has_gongjijin'] == '有' ? 1 : 0,//是否有公积金
//            'isLoans' => $this->customerInfo['has_weilidai'] == '有' ? 1 : 0,//有无微粒贷
        );
        $output = post_url($url,$params);
        $res = json_decode($output,true);
        //开始分析推送结果
        $api_ok = true; $api_error_msg = "";
        if(!$res){
            $api_ok = false;
            $api_error_msg = '接口挂了';
        } elseif($res['code'] != 1){
            $api_ok = false;
            $api_error_msg = $res['msg'];
        }
        $this->addLog($api_ok, $api_error_msg, $output);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 外推客户数据接口
     * 2020-08-12
     */
    public function sem_loan_do()
    {
        $url = "https://qian.fanghujin.com/sem/loan_do.html";
        $params = array(
            'name' => $this->customerInfo['name'],//客户姓名
            'mobile' => $this->customerInfo['mobile'],//手机号码
            'city' => $this->customerInfo['city'],//城市
            'car' => $this->customerInfo['has_car'] == '有' ? '有':'无',//是否有车
            'house' => $this->customerInfo['has_house'] == '有' ? '有':'无',//是否有房
            'baodan_is' => $this->customerInfo['has_baodan'] == '有' ? '有':'无',//是否有保单
            'money' => (int)$this->customerInfo['money'] / 10000,//借款金额（单位：万元）
            'source' => 'xinyidai',//贷款来源: 固定值
            'gongjijin' => $this->customerInfo['has_gongjijin'] == '有' ? '有':'无',//是否有公积金
            'ip' => get_client_ip(),//客户实际申请IP
            'time' => time(),//申请时间
        );
        $output = post_https_url($url, $params);
        if ($output && $output > 80000) {
            $api_ok = true;
            $api_error_msg = "";
        } else {
            $api_ok = false;
            $api_error_msg = "推送失败，错误码：".$output;
        }
        $this->addLog($api_ok, $api_error_msg, $output);
        $api_ok ? $this->setjsonReturn('推送成功！'):$this->errorjsonReturn($api_error_msg);
    }

    /**
     * 除去数组中的空值和签名模式
     */
    private function para_filter($params) {
        $para = array();
        while (list ($key, $val) = each ($params)) {
            if($key == "sign" || $val == ""){
                continue;
            }else{
                $para[$key] = $params[$key];
            }
        }
        return $para;
    }
}