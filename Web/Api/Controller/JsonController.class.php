<?php
namespace Api\Controller;
use Think\Controller;

class JsonController extends Controller
{
    /**
     * 默认返回信息
     */
    protected $ret = array('errNum'=>0, 'errMsg'=>'success', 'retData'=>array());

    /**
     * 自动执行函数
     */
    public function _initialize()
    {
        $this->addApiLog();
    }

    /**
     * 添加接口日志
     */
    private function addApiLog()
    {
        $test['url'] = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
        $test['key'] = I('request.key','');
        $test['type'] = IS_POST ? 'post':'get';
        $post = I('post.')?I('post.'):array();
        $get = I('get.')?I('get.'):array();
        $data = array_merge($post,$get);
        $test['data'] = json_encode($data);
        M('api_log')->add($test);
    }

    /**
     * 成功时返回的json数据
     */
    function setjsonReturn($value, $key=null)
    {
        if (isset($key)) {
            $this->ret['retData'][$key]=$value;
        } else {
            $this->ret['retData']=$value;
        }
        $this->ajaxReturn($this->ret,'JSON');
    }

    /**
     * 失败时返回的json数据
     */
    function errorjsonReturn($value)
    {
        $this->ret['errNum']=-1;
        $this->ret['errMsg']=$value;
        $this->ajaxReturn ($this->ret,'JSON');
    }
}