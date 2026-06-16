<?php
namespace Home\Controller;
use Admin\Logic\CacheLogic;
use Think\Controller;

class IndexController extends Controller {

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();

        //加载所有的网站配置
        $this->all_setting = (new CacheLogic())->get_all_setting();

        //关闭站点后直接访问后台
        if ($this->all_setting['site_status']!=1) {
            $this->redirect('/admin/index/index');
        }

        //判断是不是手机端访问
        if (isMobile()) {
            $this->redirect('/mobile/index/index');
        }
    }

    /**
     * 进入首页
     */
    public function index()
    {
        $this->display();
    }
}