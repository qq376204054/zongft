<?php
namespace Admin\Controller;
use Admin\Logic\AuthLogic;
use Admin\Logic\CacheLogic;
use Admin\Logic\TreeLogic;
use Think\Controller;

/**
 * 基础权限函数控制
 * Class BaseController
 * @package Home\Controller
 */

class BaseController extends Controller {
    public $no_login_action = array();
    protected $ret = array('errNum'=>0, 'errMsg'=>'success', 'retData'=>array());
    protected $data = array();
    protected $userInfo = array();
    protected $p = 1;
    protected $perpage = 10;
    protected $desc = 'id desc';

    public function _initialize()
    {
        if(!in_array(ACTION_NAME,$this->no_login_action)){
            //下面使用方法权限验证
            $validAuth=(new AuthLogic())->check(mb_strtolower(MODULE_NAME.'_'.CONTROLLER_NAME.'_'.ACTION_NAME),session('user_info_new.id'),'diff');
//            if (session('user_info_new.mobile') == '13761626615' || session('user_info_new.mobile') == '13477660725') {
//                $this->redirect('/Admin/login/loginout');
//                return false;
//            }
            if($validAuth!==true){
                if($_POST||I('get.is_ajax')){
                    return $this->errorjsonReturn($validAuth==-1?'您未登录':'权限不足');
                }else{
                    $validAuth==-1?$this->redirect('/admin/login/login'):exit('权限不足');
                }
            }
        }

        $post = I('post.')?I('post.'):array();
        $get  = I('get.')?I('get.'):array();
        $this->data = array_merge($post,$get);
        foreach($this->data as $key=>$value){if(!is_array($value)){$this->data[$key] = trim($value);}}

        $this->userInfo = session('user_info_new');
        $this->p = $this->data['p'] ? $this->data['p']:1;
        $this->perpage = $this->data['perpage'] ? $this->data['perpage']:10;
        $this->desc = $this->data['desc'] ? $this->data['desc']:'id desc';
        if($this->data['sort']&&$this->data['order']){$this->desc = $this->data['sort'].' '.$this->data['order'];}
    }

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

    /**
     * 过滤特殊的标签
     * @param $str
     * @return mixed
     */
    function clearhtml($str){
        $str = preg_replace( "@<script(.*?)</script>@is", "", $str );
        $str = preg_replace( "@<iframe(.*?)</iframe>@is", "", $str );
        $str = preg_replace( "@<style(.*?)</style>@is", "", $str );
        return $str;
    }

    /**
     * 提取第一张图片
     * @param $content
     * @return string
     */
    public  function getpic($content){
        $soContent = $content;
        $soImages = '~<img [^>]* />~';
        preg_match_all( $soImages, $soContent, $thePics);
        $allPics = count($thePics[0]);
        preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png|jpeg))\"?.+>/i',$thePics[0][0],$match);
        if($allPics>0 && $match[1]!="null"){
            return $match[1];//获取的图片名称
        }else{
            return "/static/images/nopic.jpg";
        }
    }

    /**
     * 创建公共的组织架构下拉选择
     */
    public function makeBranchSelect($branch_id=0){
        //开始组装组织架构选择
        $tree = new TreeLogic();
        $result = (new CacheLogic())->get_all_branch();
        if($this->userInfo['data_auth'] != 1){
            $result = (new CacheLogic())->getCompanyBranch($this->userInfo['company_id']);
        }
        $array = array();
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $branch_id ? 'selected' : '';
            $array[] = $r;
        }
        $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $select_menus = $tree->get_tree(0, $str);
        $this->assign('select_menus', $select_menus);

        $all_config = (new CacheLogic())->get_all_config();
        $this->assign('customer_list_remark', $all_config['customer_list_remark']);
    }
}
