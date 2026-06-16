<?php
namespace Mobile\Controller;
use Admin\Logic\CacheLogic;
use Think\Controller;

class IndexController extends Controller {

    //构造函数
    public function __construct()
    {
        parent::__construct();
        //判断是不是手机端访问
        if (!isMobile()) {
           exit("<!DOCTYPE html>
                <html>
                <head>
                    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, user-scalable=0\">
                    <title>抱歉，出错了</title>
                    <meta charset=\"utf-8\">
                    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, user-scalable=0\">
                    <link rel=\"stylesheet\" type=\"text/css\" href=\"https://res.wx.qq.com/open/libs/weui/0.4.1/weui.css\">
                </head>
                <body>
                <div class=\"weui_msg\">
                    <div class=\"weui_icon_area\">
                        <i class=\"weui_icon_info weui_icon_msg\"></i>
                    </div>
                    <div class=\"weui_text_area\">
                        <h4 class=\"weui_msg_title\">请在手机客户端打开链接</h4>
                    </div>
                </div>
                </body>
                </html>");
        }
        $allConfig = (new CacheLogic())->get_all_config();
        $this->assign('all_config', $allConfig);
        $this->assign('customer_apply_city', $allConfig['customer_apply_city']);
    }
    
    //首页 模板一
    public function index()
    {
        $this->display('1');
    }

    //贷款申请页
    public function apply()
    {
        $tid = I('get.tid');
        $this->display($tid);
    }

    //贷款申请成功页面
    public function success()
    {
        $this->display();
    }

    //隐私协议、注册协议
    public function article()
    {
        $info = M('article')->field('content')->where('id', I('get.id'))->find();
        $this->assign('content',$info['content']);
        $this->display();
    }
}