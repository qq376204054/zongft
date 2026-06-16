<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Admin\Logic\TreeLogic;
use Admin\Model\CooperationModel;
use Admin\Model\OrderModel;
use Think\Controller;

/**
 * 渠道合作相关控制器
 */
class CooperationController extends BaseController
{

    //获取列表
    public function index(){
        $tree = new TreeLogic();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $result = M('cooperation')->where(array('is_delete'=>0))->order('id asc')->select();
        $array = array();
        foreach($result as $r) {
            if($r['pid']==0){
                $r['is_default'] = '';
                $r['str_manage'] = '<a href="javascript:;" class="J_showdialog" data-uri="'.U('Cooperation/add_child',array('pid'=>$r['id'])).'" data-title="添加 - '.$r['name'].' - 的渠道" data-id="add_child" data-width="500" data-height="150">添加渠道</a> |
                                <a href="javascript:;" class="J_showdialog" data-uri="'.U('Cooperation/add',array('id'=>$r['id'])).'" data-title="修改 - '.$r['name'].'" data-id="add" data-width="500" data-height="150">修改</a> |
                                <a href="javascript:;" data-acttype="ajax" class="J_confirmurl" data-uri="'.U('Cooperation/delete',array('id'=>$r['id'])).'" data-msg="确认要删除 - '.$r['name'].' - 吗？">删除</a>';
                $r['sale'] = "";
                $r['bg'] = "background: #f4d6ba";
                $r['status'] = '';
            }else{
                if ($r['status'] == 1) {
                    $r['status'] = '<img data-tdtype="toggle" data-id="'.$r['id'].'" data-field="status" data-value="'.$r['status'].'" src="/static/images/admin/toggle_enabled.gif" />';
                } else {
                    $r['status'] = '<img data-tdtype="toggle" data-id="'.$r['id'].'" data-field="status" data-value="'.$r['status'].'" src="/static/images/admin/toggle_disabled.gif" />';
                }
                $r['is_default'] = $r['is_default']==1?'默认':'';
                $r['str_manage'] = '<a href="javascript:;" data-acttype="ajax" class="J_confirmurl" data-uri="'.U('Cooperation/defaultOne',array('id'=>$r['id'])).'" data-msg="将 - '.$r['name'].' - 设为默认渠道吗？">设为默认</a> |
                                <a href="javascript:;" class="J_showdialog" data-uri="'.U('Cooperation/add_child',array('id'=>$r['id'])).'" data-title="修改渠道 - '.$r['name'].'" data-id="add_child" data-width="500" data-height="150">修改渠道</a> |
                                <a href="javascript:;" data-acttype="ajax" class="J_confirmurl" data-uri="'.U('Cooperation/delete',array('id'=>$r['id'])).'" data-msg="确认要删除 - '.$r['name'].' - 吗？">删除</a>';
                $r['key']='（'.$r['remark'].'）';
                $r['sale'] = "<div style='border-bottom: 1px solid #666666;'>".$r['cost']."</div><div>".$r['sale_price']."</div>";
                $r['bg'] = "";
            }
            $array[] = $r;
        }
        $str  = "<tr id='node-\$id' style='\$bg'>
                <td align='center'><input type='checkbox' value='\$id' class='J_checkitem'></td>
                <td>\$spacer \$name</td>
                <td>\$key</td>
                <td align='center'>\$is_default</td>
                <td align='center'>\$type</td>
                <td align='center'>\$status</td>
                <td align='center'>\$str_manage</td>
                </tr>";
        $tree->init($array);
        $list = $tree->get_tree(0, $str);
        $this->assign('list', $list);
        $this->assign('list_table', true);
        $this->display();
    }


    /**
     * 设置一个默认的
     */
    public function defaultOne(){
        $id=I('get.id');
        if(!$id){$this->errorjsonReturn('请选择渠道');}
        $info=M('cooperation')->where(array('id'=>$id,'is_delete'=>0,'pid'=>array('neq',0)))->find();
        if(!$info){$this->errorjsonReturn('非法操作');}
        (new CacheLogic())->clear_channel_key_manger();
        $return1=M('cooperation')->where(array('pid'=>$info['pid'],'is_delete'=>0))->save(array('is_default'=>0));
        if($return1===false){$this->errorjsonReturn('操作失败');}
        $return2=M('cooperation')->where(array('id'=>$id))->save(array('is_default'=>1));
        if($return2===false){$this->errorjsonReturn('操作失败');}
        $this->setjsonReturn('操作成功');
    }

    /**
     * 添加合作方
     */
    public function add(){
        if (IS_POST) {
            $post=I('post.');
            foreach($post as $k=>$v){$post[$k]=trim($v);}
            if(!$post['name']){$this->errorjsonReturn('请填写推广工具');}
            if(!$post['key']){$this->errorjsonReturn('请填写密匙');}
            //下面清除接口缓存
            (new CacheLogic())->clear_channel_key_manger();
            if($post['id']){
                if(M('cooperation')->where(array('key'=>$post['key'],'id'=>array('neq',$post['id']),'is_delete'=>0))->count()>0){$this->errorjsonReturn('密匙已经存在');}
                M('cooperation')->where(array('id'=>$post['id']))->save($post)!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
            }else{
                $post['create_time']=time();
                //添加的时候要判断key是不是存在
                if(M('cooperation')->where(array('key'=>$post['key'],'is_delete'=>0))->count()>0){$this->errorjsonReturn('密匙已经存在');}
                M('cooperation')->add($post)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
            }
        }else{
            if(I('get.id')){
                $info=M('cooperation')->where(array('id'=>I('get.id')))->find();
                $this->assign('info',$info);
            }
            $this->display();
        }
    }

    /**
     * 删除合作方获取渠道
     */
    public function delete(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        $return=(new CooperationModel())->deleteModel($ids);
        $return===true?$this->setjsonReturn('操作成功'):$this->errorjsonReturn($return);
    }

    /**
     * 随机创建密匙
     * @param int $length
     * @return json数据
     */
    public function make_key($length = 50)
    {
        $secret=(new OrderModel())->makeStr('abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',$length);
        $this->setjsonReturn($secret);
    }

    /**
     * 添加子分类
     */
    public function add_child(){
        if (IS_POST) {
            $post=I('post.');
            foreach($post as $k=>$v){$post[$k]=trim($v);}
            $return=(new CooperationModel())->addChannelModel($post);
            $return===true?$this->setjsonReturn('操作成功'):$this->errorjsonReturn($return);
        }else{
            if(!I('get.pid')&&!I('get.id')){exit('非法操作');}
            $info['pid']=I('get.pid');
            if(I('get.id')){
                $info=M('cooperation')->where(array('id'=>I('get.id')))->find();
            }
            $this->assign('info',$info);
            $this->display();
        }
    }

    /**
     * 渠道的公司定价
     */
    public function companyPrice(){
        $allCompany = M('company_branch')->where(array('is_company'=>1,'status'=>1,'is_delete'=>0))->getField('id,name,score',true);
        $allChannel = M('cooperation')->where(array('pid'=>array('neq',0),'status'=>1,'is_delete'=>0))->getField('name',true);
        $new_allCompanyChannel = (new CacheLogic())->get_all_company_channel_price();
        $this->assign('allCompany',$allCompany);
        $this->assign('allChannel',$allChannel);

        $this->assign('allCompanyChannel',$new_allCompanyChannel);
        $this->display();
    }

    /**
     * 修改公司渠道的售价
     */
    public function editCompanyPrice(){
        if (IS_POST) {
            if(!$this->data['company_id'] || !$this->data['channel']){$this->errorjsonReturn("非法操作");}
            if($this->data['price']==""){$this->errorjsonReturn("请填写金额");}
            $where = array('company_id'=>$this->data['company_id'],'channel'=>$this->data['channel']);
            $info = M('company_channel_price')->where($where)->find();
            (new CacheLogic())->clear_all_company_channel_price();
            if($info){
                $return =  M('company_channel_price')->where($where)->save(array('price'=>$this->data['price']));
                $return===false?$this->errorjsonReturn('操作失败'):$this->setjsonReturn('操作成功');
            }else{
                $return =  M('company_channel_price')->where($where)->add($this->data);
                $return?$this->setjsonReturn('操作成功'):$this->errorjsonReturn('操作失败');
            }
        }else{
            if(!$this->data['company_id'] || !$this->data['channel']){exit("非法操作");}
            $price = M('company_channel_price')->where($this->data)->getField('price');
            $price = $price?$price:"";
            $this->assign('company_id',$this->data['company_id']);
            $this->assign('channel',$this->data['channel']);
            $this->assign('price',$price);
            $this->display();
        }
    }

    /**
     * ajax修改状态
     */
    public function ajax_edit()
    {
        $get=I('get.');
        if(!$get['id']||!$get['field']){$this->errorjsonReturn('修改失败');}
        $result = M('cooperation')->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('成功');
    }
}