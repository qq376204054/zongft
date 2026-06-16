<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Think\Controller;

/**
 * 后台首页
 * Class IndexController
 * @package Admin\Controller
 */
class IndexController extends BaseController {

    /**
     * 后台首页
     * @return mixed
     */
    public function index()
    {
        $this->assign('userInfo', $this->userInfo);
        $has_allocate = M('allocate_user')->where(array('user_id'=>$this->userInfo['id'],'is_delete'=>0))->count();
        //下面开始渲染界面
        $this->assign('can_allocate', $has_allocate>0?true:false);
        $my_menu=(new CacheLogic())->get_one_user_all_menu($this->userInfo['id']);
        foreach($my_menu as $value){if($value['pid']==0){$top_menus[]=$value;}}
        $all_setting = (new CacheLogic())->get_all_setting();
        $this->assign('all_setting', $all_setting);
        $this->assign('top_menus', $top_menus);
        $this->display();
    }

    /**
     * 左侧栏的菜单
     */
    public function left() {
        $menuid = I('menuid');
        $my_menu=(new CacheLogic())->get_one_user_all_menu($this->userInfo['id']);
        foreach($my_menu as $value){if($value['pid']==$menuid){$left_menu[]=$value;}}
        foreach ($left_menu as $key=>$val) {
            foreach($my_menu as $value){
                if($value['pid']==$val['id']){
                    $left_menu[$key]['sub'][]=$value;
                }
            }
        }
        $this->assign('left_menu', $left_menu);
        $this->display();
    }

    /**
     * 默认的右侧内容
     */
    public function panel(){
        $system_info = array(
            'host'=>$_SERVER['SERVER_NAME'],
            'server_domain' => $_SERVER['SERVER_NAME'] . ' [ ' . gethostbyname($_SERVER['SERVER_NAME']) . ' ]',
            'server_os' => PHP_OS,
            'web_server' => $_SERVER["SERVER_SOFTWARE"],
            'php_version' => PHP_VERSION,
            'mysql_version' => "",
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'max_execution_time' => ini_get('max_execution_time') . '秒',
            'safe_mode' => (boolean) ini_get('safe_mode') ?  'onCorrect' : 'onError',
            'zlib' => function_exists('gzclose') ?  'onCorrect' : 'onError',
            'curl' => function_exists("curl_getinfo") ? 'onCorrect' : 'onError',
            'timezone' => function_exists("date_default_timezone_get") ? date_default_timezone_get() : L('no')
        );
        $this->assign('system_info', $system_info);
        //下面展示用户的信息
        $this->assign('userInfo', $this->userInfo);
        $this->assign('time',date('Y-m-d H:i'));
        $this->assign('ip',get_client_ip());
        $this->display();
    }
}