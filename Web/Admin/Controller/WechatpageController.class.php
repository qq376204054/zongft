<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 微信管理相关控制器
 * Class User
 * @package app\admin\controller
 */
class WechatpageController extends BaseController
{
    public function menu(){
        if(IS_POST){
            $post=I('post.daohang');
            $menu_array=array();
            foreach($post as $key=>$value){
                if($value['name']){
                    $sub_array=array();
                    //下面遍历子菜单
                    foreach($value['sub']['name'] as $k1=>$v1){
                        if($v1){
                            if($value['sub']['action'][$k1]=='view'){
                                $sub_array[]=array("type"=>"view", "name"=>$v1, "url"=>$value['sub']['url'][$k1]);
                            }else{
                                $sub_array[]=array("type"=>"click", "name"=>$v1, "key"=>$value['sub']['url'][$k1]);
                            }
                        }
                    }
                    if($sub_array){
                        $menu_array[]=array("name"=>$value['name'], "sub_button"=>$sub_array);
                    }else{
                        if($value['action']=='view'){
                            $menu_array[]=array("type"=>"view", "name"=>$value['name'], "url"=>$value['url']);
                        }else{
                            $menu_array[]=array("type"=>"click", "name"=>$value['name'], "key"=>$value['url']);
                        }
                    }
                }
            }
            $want_daohang=json_encode(array('button'=>$menu_array),JSON_UNESCAPED_UNICODE);
            $token=self::Gettoken();
            //添加菜单
            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-type:application/x-www-form-urlencoded',
                    'content' => $want_daohang,
                    'timeout' => 15 * 60 // 超时时间（单位:s）
                )
            );
            $context = stream_context_create($options);
            $result = file_get_contents("https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$token", false, $context);
            $result=json_decode($result,true);
            if($result['errcode']===0){
                $this->setjsonReturn('设置成功');
            }else{
                $this->errorjsonReturn('设置失败，状态码'.$result['errcode'].'，错误信息：'.$result['errmsg']);
            }
        }ELSE{
            $token=self::Gettoken();
            $url2 = "https://api.weixin.qq.com/cgi-bin/menu/get";
            $return=http($url2, $params = array("access_token"=>$token), $method = 'GET');
            $return_data = json_decode($return,true);
            $this->assign('menu',$return_data['menu']['button']);
            $this->display();
        }
    }

    /**
     * 获取接口的token
     * @return mixed
     */
    static function Gettoken(){
        $configinfo=M('setting')->where(array('type'=>'system','name'=>array('in',array('weixin_appkey','weixin_appsecret','weixin_token'))))
            ->getField('name,data',true);
        $url = "https://api.weixin.qq.com/cgi-bin/token";
        $return_string=http($url, $params = array("grant_type"=>"client_credential","appid"=>$configinfo['weixin_appkey'],"secret"=>$configinfo['weixin_appsecret']), $method = 'GET');
        $return_data = json_decode($return_string,true);
        return $return_data['access_token'];
    }

    /**
     * 微信的自定义回复
     */
    public function system_answer(){
        $get=I('get.');
        $p = $get['p'] ? $get['p'] : 1 ;
        $order='id desc';
        if($get['sort']&&$get['order']){$order=$get['sort'].' '.$get['order'];}
        $perpage=10;
        $where['is_delete']=0;
        $count = M('wechat_setting')->where($where)->count();
        $Page = new \Think\Page($count,$perpage);
        $list =  M('wechat_setting')->where($where)->Page($p,$perpage)->order($order)->select();
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $big_menu = array('title' => '添加自动回复规则', 'iframe' => U('Wechatpage/system_answer_add'), 'id' => 'system_answer_add', 'width' => '500', 'height' => '350',);
        $this->assign('big_menu', $big_menu);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 添加自动回复
     */
    public function system_answer_add(){
        if (IS_POST) {
            $post=I('post.');
            if(!$post['action']){$this->errorjsonReturn('请选择触发动作');}
            if(!$post['number']){$this->errorjsonReturn('请填写回复编号');}
            if(($post['action']=='关键字回复')&&!$post['keyword']){$this->errorjsonReturn('请填写关键字');}
            if(!$post['type']){$this->errorjsonReturn('请选择回复类型');}
            if($post['action']!='关键字回复'){$post['keyword']='';}
            if($post['type']=='text'){
                if(!$post['text']){$this->errorjsonReturn('请填写回复文本');}
            }elseif($post['type']=='news'){
                $array=array();
                foreach($post['news_title'] as $k=>$v){
                    if($v){
                        $array[]=array(
                            'title'=>$v,
                            'text'=>$post['news_text'][$k],
                            'url'=>$post['news_url'][$k],
                            'pic'=>$post['news_pic'][$k],
                        );
                    }
                }
                if(!$array){$this->errorjsonReturn('请配置图文内容');}
                $post['text']=json_encode($array);
            }else{
                $this->errorjsonReturn('非法操作');
            }
            if($post['id']){
                //修改的时候不能覆盖了他人的手机号码
                if(M('wechat_setting')->where(array('number'=>$post['number'],'is_delete'=>0,'id'=>array('neq',$post['id'])))->count()>0){$this->errorjsonReturn('编号已经存在');}
                M('wechat_setting')->where(array('id'=>$post['id']))->save($post)!==false?$this->setjsonReturn('修改成功'):$this->errorjsonReturn('修改失败');
            }else{
                //添加的情况下手机号码不能重复
                if(M('wechat_setting')->where(array('number'=>$post['number'],'is_delete'=>0))->count()>0){$this->errorjsonReturn('编号已经存在');}
                M('wechat_setting')->add($post)?$this->setjsonReturn('添加成功'):$this->errorjsonReturn('添加失败');
            }
        }else{
            if(I('get.id')){
                $info=M('wechat_setting')->where(array('id'=>I('get.id')))->find();
                if($info['type']=='news'){$info['text']=json_decode($info['text'],true);}
                if(!$info){exit('非法操作');}
                $this->assign('info',$info);
            }
            $this->display();
        }
    }

    /**
     * 删除分类
     */
    public function delete(){
        $ids = array_filter(array_unique(explode(',',I('get.id'))));
        if(!$ids){$this->errorjsonReturn('请选择需删除项');}
        $result=M('wechat_setting')->where(array('id'=>array('in',$ids)))->save(array('is_delete'=>1));
        $result===false?$this->errorjsonReturn('删除失败'):$this->setjsonReturn('删除成功');
    }
}

