<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Think\Controller;

class CacheController extends BaseController
{
    /**
     * 缓存列表
     */
    public function index(){
        $this->display();
    }

    /**
     * 查看缓存
     */
    public function look_cache(){
        $name=I('get.name');
        if($name=='channel_key_manger'){
            $return=(new CacheLogic())->get_channel_key_manger();
        }elseif($name=='all_config'){
            $return=(new CacheLogic())->get_all_config();
        }elseif($name=='all_setting'){
            $return=(new CacheLogic())->get_all_setting();
        }elseif($name=='all_system_menu_rules'){
            $return=(new CacheLogic())->get_all_system_menu_rules();
        }elseif($name=='one_user_all_menu'){
            $return=(new CacheLogic())->get_one_user_all_menu($this->userInfo['id']);
        }elseif($name=='all_system_menu'){
            $return=(new CacheLogic())->get_all_system_menu();
        }else{
            $return=S($name);
        }
        dump($return);
    }

    /**
     * 清除缓存
     */
    public function clear_cache(){
        $name=I('get.name');
        if($name=='channel_key_manger'){
            (new CacheLogic())->clear_channel_key_manger();
        }elseif($name=='all_config'){
            (new CacheLogic())->clear_all_config();
        }elseif($name=='all_setting'){
            (new CacheLogic())->clear_all_setting();
        }elseif($name=='all_system_menu_rules'){
            (new CacheLogic())->clear_all_system_menu_rules();
        }elseif($name=='one_user_all_menu'){
            (new CacheLogic())->clear_one_user_all_menu($this->userInfo['id']);
        }elseif($name=='all_system_menu'){
            (new CacheLogic())->clear_all_system_menu();
        }
        $this->setjsonReturn('成功');
    }

}

