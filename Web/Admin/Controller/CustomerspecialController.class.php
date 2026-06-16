<?php
namespace Admin\Controller;
use Admin\Model\CustomerModel;
use Admin\Model\MenuModel;
use Admin\Model\OrderModel;
use Think\Controller;

class CustomerspecialController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 进入特殊渠道首页   ----检查完毕
     */
    public function index(){
        $menuList=(new MenuModel())->getSubMenu(I('get.menuid'));
        $my_auth=S('AUTH_LIST_'.$this->userInfo['id']);
        foreach($menuList as $k=>$value){
            $str='admin_'.$value['module_name'].'_'.$value['action_name'];
            if(!in_array(mb_strtolower($str),$my_auth)){
                unset($menuList[$k]);
            }
        }
        $this->assign('menuList',$menuList);
        $this->display();
    }

    /**
     * 其他的渠道   ----检查完毕
     * @param $channel
     */
    public function _empty($channel){
        $get=I('get.');
        foreach($get as $k=>$value){$get[$k]=trim($value);}
        $this->assign('search', $get);
        $return = (new OrderModel())->getOrderListModel($this->userInfo,$get,'receiveSpecial',$this->userInfo['id'],$this->userInfo['city'],$channel);
        $this->assign('list', $return['list']);
        $this->assign('page',$return['pageShow'] );
        $this->assign('tableCount',$return['tableCount'] );
        $this->assign('list_table', true);
        $this->display('special_pool');
    }


}