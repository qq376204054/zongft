<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 外包合作商控制器
 * Class Customermall
 * @package app\admin\controller
 */
class CustomermallController extends BaseController
{
    /**
     * 买方合作公司管理
     */
    public function company(){
        $list = M('mall_company')->where(array('is_delete'=>0))->order('id asc')->select();
        $this->assign('list', $list);
        $big_menu = array('title' => '添加买方合作商', 'iframe' => U('companyAdd'), 'id' => 'add', 'width' => '500', 'height' => '150',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 添加买方合作商
     */
    public function companyAdd(){
        if(IS_POST){
            $post=I('post.');
            $name=trim($post['name']);
            $action=trim($post['action']);
            if(!$name){$this->errorjsonReturn('请填写名称');}
            $keys=$post['key'];
            foreach($keys as $key=>$v){$keys[$key]=trim($v);}
            $values=$post['value'];
            foreach($values as $key=>$v){$values[$key]=trim($v);}
            //下面进行双向检查
            $valid=true;
            foreach($keys as $k=>$v){
                if($v&&!$values[$k]){$valid=false;}
            }
            foreach($values as $k=>$v){
                if($v&&!$keys[$k]){$valid=false;}
            }
            if($valid===false){$this->errorjsonReturn('请完善渠道转换信息');}

            $array=array();
            for ($i=0; $i<count($keys); $i++) {
                if($keys[$i]){
                    $array[$keys[$i]]=$values[$i];
                }
            }
            $save=array();
            $save['name']=$name;
            $save['action']=$action;
            $save['utm_source_config']=json_encode($array);
            if(I('post.id')){
                $return=M('mall_company')->where(array('id'=>I('post.id')))->save($save);
                $return=$return===false?false:true;
            }else{
                $save['create_time']=time();
                $return=M('mall_company')->add($save);
                $return=$return?true:false;
            }
            $return===true?$this->setjsonReturn('操作成功'):$this->errorjsonReturn('操作失败');
        }else{
            $id=I('get.id');
            if($id){
                $info=M('mall_company')->where(array('id'=>$id))->find();
                $info['utm_source_config']=json_decode($info['utm_source_config'],true);
                $this->assign('info',$info);
            }
            $this->display();
        }
    }

    /**
     * 操作输出客户的方法
     */
    public function doAction(){
        $mall_company_id=I('get.id');
        if(!$mall_company_id){exit('非法操作');}
        $companyInfo=M('mall_company')->where(array('id'=>$mall_company_id))->find();
        $companyInfo['utm_source_config']=json_decode($companyInfo['utm_source_config'],true);
        $get=I('get.');
        $sql="";
        if($get['name']||$get['mobile']||$get['city']||$get['channel']||$get['start_time']||$get['end_time']){
            if($get['name']){
                $sql=$sql." AND a.name like '%".$get['name']."%'";
            }
            if($get['mobile']){
                $sql=$sql." AND a.mobile like '%".$get['mobile']."%'";
            }
            if($get['city']){
                $sql=$sql." AND a.city like '%".$get['city']."%'";
            }
            if($get['channel']){
                $sql=$sql." AND a.channel='".$get['channel']."' ";
            }
            if($get['start_time']){
                $sql = $sql." AND a.create_time>'".strtotime($get['start_time'])."' ";
            }
            if($get['end_time']){
                $sql = $sql." AND a.create_time<'".(strtotime($get['end_time'])+24*3600)."' ";
            }
            $return=M()->query("SELECT * FROM yq_first_customer a LEFT JOIN
        (SELECT first_customer_id from yq_mall_sale_log WHERE mall_company_id=".$mall_company_id.") as tem
on a.id=tem.first_customer_id WHERE tem.first_customer_id is null ".$sql." LIMIT 2000;");
            foreach($return as $k=>$value){
                $return[$k]['to_channel']=$companyInfo['utm_source_config'][$value['channel']]?$companyInfo['utm_source_config'][$value['channel']]:$value['channel'];
            }
        }else{
            $return = array();
        }

        $this->assign('search',$this->data);
        $this->assign('list',$return);
        $this->assign('companyInfo',$companyInfo);
        $this->display();
    }

    /**
     * 推送日志
     */
    public function hasTable(){
        $perpage = 100;
        $get = I('get.');
        $p = $get['p'] ? $get['p'] : 1 ;
        $mall_company_id=I('get.id');
        if(!$mall_company_id){exit('非法操作');}

        $where = array();
        $where['a.mall_company_id'] = array('eq',$mall_company_id);
        if($get['name']){$where['b.name']=array('like','%'.$get['name'].'%');}
        if($get['mobile']){$where['b.mobile']=array('like','%'.$get['mobile'].'%');}
        if($get['city']){$where['b.city']=array('like','%'.$get['city'].'%');}
        if($get['channel']){$where['b.channel']=array('like','%'.$get['channel'].'%');}
        if($get['is_ok']){$where['a.is_ok']=array('eq',$get['is_ok']);}

        if($get['start_push_time']){$where['a.create_time'][] = array('gt',$get['start_push_time']." 00:00:00");}
        if($get['end_push_time']){$where['a.create_time'][] = array('lt',$get['end_push_time']." 23:59:59");}

        if($get['min_money']){ $where['b.money'][] = array('EGT',$get['min_money']);}
        if($get['max_money']){ $where['b.money'][] = array('ELT',$get['max_money']);}
        //$where['a.is_ok'] = 1;
        $count = M('mall_sale_log')->alias('a')
            ->where($where)
            ->join('LEFT JOIN __FIRST_CUSTOMER__ as b ON a.first_customer_id = b.id')
            ->count();
        $Page = new \Think\Page($count,$perpage);
        $list  = M('mall_sale_log')->alias('a')
            ->where($where)
            ->join('LEFT JOIN __FIRST_CUSTOMER__ as b ON a.first_customer_id = b.id')
            ->Page($p,$perpage)->order('a.id desc')
            ->field('a.first_customer_id,b.name,b.mobile,b.money,b.city,b.create_time,b.channel,a.create_time as push_time,
            a.is_ok as is_ok,a.error_msg as error_msg,a.api_data as api_data')
            ->select();

        $this->assign('search',$get);
        $this->assign('count',$count);
        $this->assign('list', $list);
        $this->assign('page',$Page->show());
        $this->display();
    }


    /**
     * 删除操作
     */
    public function delete(){
        $ids = array_filter(array_unique(explode(',', I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result = M('mall_company')->where(array('id'=>array('in',$ids)))->delete();
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
        $result = M('mall_company')->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('修改成功');
    }

}

