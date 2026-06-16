<?php
namespace Admin\Controller;
use Admin\Logic\CacheLogic;
use Admin\Logic\TreeLogic;
use Admin\Model\ConfigModel;
use Think\Controller;

/**
 * 常量配置相关控制器
 */
class ConfigController extends BaseController
{
    /**
     * 首页
     */
    public function index(){
        $result = M('config')->where(array('is_delete'=>1))->order('ordid asc,id desc')->select();
        $this->assign('list', $result);
        $big_menu = array('title' => '添加配置类型', 'iframe' => U('Config/add'), 'id' => 'add', 'width' => '500', 'height' => '150',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 添加配置
     */
    public function add(){
        if(IS_POST){
            $post=I('post.');
            foreach($post as &$value){$value=trim($value);}
            if(!$post['name']||!$post['title']||!$post['remark']||!in_array($post['type'],array(1,2,3))){$this->errorjsonReturn('请完善信息');}
            $post['create_time']=time();
            (new CacheLogic())->clear_all_config();
            M('config')->add($post)?$this->setjsonReturn('成功'):$this->errorjsonReturn('添加失败');
        }else{
            $this->display();
        }
    }

    /**
     * 删除分类
     */
    public function delete(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        (new CacheLogic())->clear_all_config();
        $result=M('config')->where(array('id'=>array('in',$ids)))->save(array('is_delete'=>2));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }

    /**
     * ajax修改单个字段值
     */
    public function ajax_edit()
    {
        $get=I('get.');
        if(!$get['id']||!$get['field']){$this->errorjsonReturn('修改失败');}
        $result = M('config')->where(array('id'=>$get['id']))->save(array($get['field']=>$get['val']));
        $result===false?$this->errorjsonReturn('修改失败'):$this->setjsonReturn('成功');
    }

    /**
     * 添加值
     */
    public function add_value(){
        if(IS_POST){
            $return=(new ConfigModel())->editOneConfigById(I('post.id'),I('post.value'),I('post.key'));
            (new CacheLogic())->clear_all_config();
            $return===true?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
        }else{
            $type=M('config')->where(array('id'=>I('get.id'),'is_delete'=>1))->getfield('type');
            if(!$type){exit('非法操作');}
            $value=(new ConfigModel())->getConfigByIdModel(I('get.id'));
            if($value===false){exit('非法操作');}
            if($type==1){$page='add_one';}
            elseif($type==2){$page='add_two';}
            elseif($type==3){$page='add_three';}
            else{exit('数据错误');}
            $this->assign('value',$value);
            $this->assign('id',I('get.id'));
            $this->display($page);
        }
    }
}

