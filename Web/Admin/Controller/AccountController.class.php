<?php
namespace Admin\Controller;
use Admin\Model\AccountModel;
use Admin\Model\ConfigModel;
use Think\Controller;
/**
 * 收入支出相关控制器
 * Class User
 * @package app\admin\controller
 */
class AccountController extends BaseController
{
    /**
     * 一个订单的账目列表   ----检查完毕
     */
    public function account_list(){
        if(!$this->data['order_id']){exit('非法操作');}
        $list = M('account')->where(array('order_id'=>$this->data['order_id'],'is_delete'=>0))->order('id desc')->select();
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 删除分类   ----检查完毕
     */
    public function delete(){
        $return=(new AccountModel())->deleteModel(I('get.id'),$this->userInfo['id']);
        $return===true?$this->setjsonReturn('操作成功'):$this->errorjsonReturn($return);
    }


    /**
     * 添加账单   ----检查完毕
     */
    public function add(){
        if(IS_POST){
            $post=I('post.');
            foreach($post as $k=>$v){$post[$k]=trim($v);}
            $return=(new AccountModel())->addModel($post,$this->userInfo['id']);
            $return===true?$this->setjsonReturn('操作成功'):$this->errorjsonReturn($return);
        }else{
            $this->assign('order_id',I('get.id'));
            $this->display();
        }
    }

    /**
     * 添加账单界面
     */
    public function form_add(){
        $type=I('get.type');
        if(!in_array($type,array(1,2))){exit('非法操作');}
        if($type==1){
            $list=(new ConfigModel())->getConfigByNameModel('account_income_type');
        }else{
            $list=(new ConfigModel())->getConfigByNameModel('account_pay_type');
        }
        $this->assign('money_pay_type',(new ConfigModel())->getConfigByNameModel('money_pay_type'));
        $this->assign('list',$list);
        $this->assign('type',$type);
        $this->display();
    }
}