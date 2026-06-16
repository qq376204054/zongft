<?php
namespace Admin\Controller;
use Admin\Model\CompanyModel;
use Think\Controller;

/**
 * 添加公司相关控制器
 */
class CompanyController extends BaseController
{
    /**
     * 获取用户列表
     * @return mixed
     */
    public function index(){
        $this->display();
    }

    /**
     * 公司添加界面
     */
    public function add(){
        if(I('get.id')){
            $info=M('company')->where('id='.input('get.id'))->find();
            if(empty($info)){exit('公司不存在');}
            $this->assign([
                'info' => $info
            ]);
        }
        $this->display();
    }

    /**
     * 添加修改公司
     * @return json数据
     */
    public function addAction(){
        $post=array(
            'id'=>I('post.id'),
            'name'=>I('post.name'),
            'status'=>I('post.status'),
            'logo'=>I('post.logo'),
        );
        $result=(new CompanyModel())->saveCompanyModel($post);
        $result?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
    }

    /**
     * 选择公司的界面
     * @return mixed
     */
    public function selectCompany(){
        $this->display();
    }

}

