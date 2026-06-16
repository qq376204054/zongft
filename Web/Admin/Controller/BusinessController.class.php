<?php
namespace Admin\Controller;
use Admin\Model\BusinessModel;
use Admin\Model\OrderModel;//用于生产密钥

/**
 * 租赁商户相关控制器
 */
class BusinessController extends BaseController
{
    /**
     * 获取租赁商户列表
     */
    public function index()
    {
        $list = BusinessModel::instance()->order('id desc')->select();
        foreach($list as $key=>$value){
            $list[$key]['create_time'] = date('Y-m-d',$value['create_time']);
            $list[$key]['end_time'] = date('Y-m-d',$value['end_time']);
        }
        $this->assign('list', $list);
        $big_menu = array('title' => '添加租赁商户', 'iframe' => U('Business/add'), 'id' => 'add', 'width' => '500', 'height' => '150',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 添加界面及操作
     */
    public function add()
    {
        if (IS_POST) {
            $model = BusinessModel::instance();
            $name = trim(I('post.name'));
            if (empty($name)) {
                $this->errorjsonReturn('请填写名称');
            }
            $mobile = trim(I('post.mobile'));
            if (empty($mobile)) {
                $this->errorjsonReturn('请填写手机号');
            }
            if(!is_phone($mobile)){
                $this->errorjsonReturn('手机格式不正确');
            }
            $white_ip = trim(I('post.white_ip'));
            if (empty($white_ip)) {
                $this->errorjsonReturn('请填写白名单IP地址');
            }
            if(!filter_var($white_ip, FILTER_VALIDATE_IP)) {
                $this->errorjsonReturn('IP地址不合法');
            }
            $end_time = trim(I('post.end_time'));
            if (empty($end_time)) {
                $this->errorjsonReturn('请选择到期时间');
            }
            if (strtotime($end_time) < time()) {
                $this->errorjsonReturn('到期时间不得小于当前时间');
            }
            $key = trim(I('post.key'));
            if (empty($key)) {
                $this->errorjsonReturn('请填写密匙');
            }
            //先判断密钥是否已被使用
            $count = $model->where(array('key'=>$key))->count();
            if ($count>0) {
                $this->errorjsonReturn('密匙已经存在');
            }
            //需要新增的数据
            $data = array(
                'name' => $name,
                'mobile' => $mobile,
                'key' => $key,
                'white_ip' => $white_ip,
                'remark' => trim(I('post.remark')),
                'end_time' => strtotime($end_time),
                'business_name' => trim(I('post.business_name')),
                'business_mobile' => trim(I('post.business_mobile')),
                'status' => 1,
                'create_time' => time(),
                'update_time' => time()
            );
            $result = $model->add($data);
            if($result !== false){
                $this->setjsonReturn('操作成功');
            } else {
                $this->errorjsonReturn('操作失败');
            }
        }else{
            $this->display();
        }
    }

    /**
     * 修改界面及操作
     */
    public function edit(){
        $model = BusinessModel::instance();
        if (IS_POST) {
            //编辑时必须有此项传值
            $id = intval(I('post.id'));
            if (empty($id)) {
                $this->errorjsonReturn('系统有误，请联系管理员');
            }
            $name = trim(I('post.name'));
            if (empty($name)) {
                $this->errorjsonReturn('请填写名称');
            }
            $mobile = trim(I('post.mobile'));
            if (empty($mobile)) {
                $this->errorjsonReturn('请填写手机号');
            }
            if(!is_phone($mobile)){
                $this->errorjsonReturn('手机格式不正确');
            }
            $white_ip = trim(I('post.white_ip'));
            if (empty($white_ip)) {
                $this->errorjsonReturn('请填写白名单IP地址');
            }
            if(!filter_var($white_ip, FILTER_VALIDATE_IP)) {
                $this->errorjsonReturn('IP地址不合法');
            }
            $end_time = trim(I('post.end_time'));
            if (empty($end_time)) {
                $this->errorjsonReturn('请选择到期时间');
            }
            if (strtotime($end_time) < time()) {
                $this->errorjsonReturn('到期时间不得小于当前时间');
            }
            $key = trim(I('post.key'));
            if (empty($key)) {
                $this->errorjsonReturn('请填写密匙');
            }
            //先判断密钥是否已被使用
            $count = $model->where(array('key'=>$key,'id'=>array('neq',$id)))->count();
            if ($count>0) {
                $this->errorjsonReturn('密匙已经存在');
            }
            //需要修改的数据
            $data = array(
                'name' => $name,
                'mobile' => $mobile,
                'key' => $key,
                'white_ip' => $white_ip,
                'remark' => trim(I('post.remark')),
                'end_time' => strtotime($end_time),
                'business_name' => trim(I('post.business_name')),
                'business_mobile' => trim(I('post.business_mobile')),
                'update_time' => time()
            );
            $result = $model->where("id=".$id)->save($data);
            if($result !== false){
                $this->setjsonReturn('操作成功');
            } else {
                $this->errorjsonReturn('操作失败');
            }
        } else {
            $id = intval(I('get.id'));
            $info = $model->where('id = '.$id)->find();
            $this->assign('info',$info);
            $this->display();
        }
    }

    /**
     * 删除操作
     */
    public function delete(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result = BusinessModel::instance()->where(array('id'=>array('in',$ids)))->delete();
        if($result !== false){
            $this->setjsonReturn('删除成功');
        } else {
            $this->errorjsonReturn('删除失败');
        }
    }

    /**
     * ajax更改状态
     */
    public function ajax_edit()
    {
        $get = I('get.');
        if(!$get['id'] || !$get['field']){
            $this->errorjsonReturn('修改失败');
        }
        $result = BusinessModel::instance()->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('修改成功');
    }

    /**
     * 随机创建密匙
     */
    public function make_key($length = 50)
    {
        $model = new OrderModel();
        $secret = $model->makeStr('abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',$length);
        $this->setjsonReturn($secret);
    }
}