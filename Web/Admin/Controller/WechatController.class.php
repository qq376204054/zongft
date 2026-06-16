<?php
namespace Admin\Controller;
use Think\Controller;
/**
 * 微信相关控制器
 * Class User
 * @package app\admin\controller
 */
class WechatController extends Controller
{
    public function index(){
        if($this->checkSignature()){
            $return=$this->responseMsg();
            if($return===false){
                echo $_GET["echostr"];
            }else{
                echo($return);
                echo $_GET["echostr"];
            }
        }else{
            echo $_GET["echostr"];
        }
    }

    //首先定义一个获取微信公众号配置的方法(内置的)
    static function GetWeixininfo(){
        $configinfo=M('setting')->where(array('type'=>'system','name'=>array('in',array('weixin_appkey','weixin_appsecret','weixin_token'))))
                    ->getField('name,data',true);
        return $configinfo;
    }
    /**
     * 下面是检测验证
     * @return bool
     */
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        //微信服务器要验证客户服务器上的token
        $strarr=self::GetWeixininfo();
        $tmpArr = array($strarr['weixin_token'], $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    //客户服务器回复的动作及数据处理
    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $RX_TYPE = trim($postObj->MsgType);
        //下面记录微信的消息类型
        M('wechat_log')->add(array('msg_type'=>$RX_TYPE, 'create_time'=>time(), 'data'=>json_encode($postObj)));
        if($RX_TYPE=="event"){//用户关注事件
            $event = trim($postObj->Event);
            if($event=="subscribe"){//用户关注
                //如果不存在，就去找通用的回复配置
                $data=M('wechat_setting')->where(array('action'=>'关注回复','is_delete'=>0))->field('type,text')->find();
                if(!$data){return false;}
            }elseif($event=='CLICK'){
                $EventKey=trim($postObj->EventKey);
                //点击菜单触发的事件,查询存在不存在回复配置
                $data=M('wechat_setting')->where(array('number'=>$EventKey,'is_delete'=>0))->field('type,text')->find();
                if(!$data){return false;}
            }else{
                return false;
            }
            $array=json_decode($data['text'],true);
            return $this->create_xml($postObj->FromUserName,$postObj->ToUserName,$array);
        } elseif($RX_TYPE=="text") {//用户发送信息事件回复
            return $this->reply_user_action($postObj);
        }
    }

    /**
     * 根据关键词回复
     * @param $postObj
     * @return bool|string
     */
    private function reply_user_action($postObj){
        $content=$postObj->Content;
        if(!$content){return false;}
        //下面去数据库中进行智能匹配
        $data=M('wechat_setting')->where("INSTR('".$content."',keyword)>0 AND is_delete<>1 AND keyword<>''")->field('type,text')->find();
        if(!$data){
            //如果不存在，就去找通用的回复配置
            $data=M('wechat_setting')->where(array('action'=>'统一回复','is_delete'=>0))->field('type,text')->find();
            if(!$data){return false;}
        }
        $array=json_decode($data['text'],true);
        return $this->create_xml($postObj->FromUserName,$postObj->ToUserName,$array);
    }

    /**
     * 构建xml语句
     * @param $FromUserName
     * @param $ToUserName
     * @param $array
     * @return string
     */
    private function create_xml($FromUserName,$ToUserName,$array){
        $textTpl="<xml>
                        <ToUserName><![CDATA[".$FromUserName."]]></ToUserName>
                        <FromUserName><![CDATA[".$ToUserName."]]></FromUserName>
                        <CreateTime>".time()."</CreateTime>
                        <MsgType><![CDATA[news]]></MsgType>
                        <ArticleCount>".count($array)."</ArticleCount>
                        <Articles>";
        foreach($array as $value){
            $textTpl=$textTpl."<item>";
            if($value['title']){$textTpl=$textTpl."<Title><![CDATA[".$value['title']."]]></Title>";}
            if($value['text']){$textTpl=$textTpl."<Description><![CDATA[".$value['text']."]]></Description>";}
            if($value['pic']){$textTpl=$textTpl."<PicUrl><![CDATA[http://".$_SERVER['HTTP_HOST'].$value['pic']."]]></PicUrl>";}
            if($value['url']){$textTpl=$textTpl."<Url><![CDATA[".$value['url']."]]></Url>";}
            $textTpl=$textTpl."</item>";
        }
        $textTpl=$textTpl."</Articles>
                        </xml>";
        return $textTpl;
    }
}