<?php
namespace Admin\Controller;
use Think\Controller;

class JsonController extends Controller
{
    protected $ret = array('errNum'=>0, 'errMsg'=>'success', 'retData'=>array());
    /**
     * 返回正确的格式的json数据
     * @param $value 要json转换的数据
     * @param null $key 数据对应的关键词(可选)
     * @return json数据
     */
    function setjsonReturn($value,$key=null){
        if (isset($key))
            $this->ret['retData'][$key]=$value;
        else
            $this->ret['retData']=$value;
        $this->ajaxReturn ($this->ret,'JSON');
    }
    /**
     * 返回错误格式的json数据
     * @param $value
     * @return json数据
     */
    function errorjsonReturn($value){
        $this->ret['errNum']=-1;
        $this->ret['errMsg']=$value;
        $this->ajaxReturn ($this->ret,'JSON');
    }
}