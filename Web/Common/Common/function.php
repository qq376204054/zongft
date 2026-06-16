<?php

/**
 * 对象转数组
 * @param $object
 * @return mixed
 */
function object2array($object) {
    $array=array();
    if (is_object($object)) {
        foreach ($object as $key => $value) {
            $array[$key] = $value;
        }
    }
    else {
        $array = $object;
    }
    return $array;
}


/**------------------------------------无限级导航函数-------------------------------**/
/**
 * 无极分类排序公共函数---------生成一级数组
 * @param $data  传入的数组
 * @param int $pid  父导航
 * @param int $level  当前是第几级
 * @param string $fatheridmark 跟踪父id的标签
 * @param string $levelmark 定义输出等级的标签
 * @param string $idmark 跟踪主键id的标签
 * @return array
 */
function listDataToLevel($data,$pid=0,$level=0,$fatheridmark='fatherid',$levelmark='level',$idmark='id'){
    $array=array();
    foreach ($data AS $k=>$value) {
        if ($value[$fatheridmark] == $pid) {
            $value[$levelmark]=$level;
            $array[$value[$idmark]] = $value;
            unset($data[$k]);//移除已经用过的数据，减少循环次数
            $array=$array+listDataToLevel($data,$value[$idmark],$level+1,$fatheridmark,$levelmark);//用+号合并数组可以保存键值
        }
    }
    return $array;
}
/**
 * 创建等级数组里面的无限循环函数------生成无限级数组
 * @param $array
 * @param $fatherid
 * @param $fatherids
 * @return array
 */
function createGradeMenu($array,$fatheridmark='pid',$fatherid=0,$fatherids=array()){
    $fatherids=array_column($array,$fatheridmark);
    $menu=array();
    foreach($array as $k=>$v){
        if($v[$fatheridmark]==$fatherid){
            if(in_array($v['id'],$fatherids)){
                $v['hasChild']=1;
                $v['child']=createGradeMenu($array,$fatheridmark,$v['id'],$fatherids);
            }else{
                $v['hasChild']=0;
            }
            $menu[$v['id']]=$v;
        }
    }
    return $menu;
}
/**
 * 获取这个主键的所有子集
 * @param $id
 * @param $array
 * @return mixed
 */
function getAllChildInArray($id,$array,$fatherid_remark='fatherid'){
    $allChildId=array();
    foreach($array as $key=>$value){
        if($value[$fatherid_remark]==$id){
            $allChildId[]=$value['id'];
            $childChildId=getAllChildInArray($value['id'],$array,$fatherid_remark);
            $allChildId=array_merge($allChildId,$childChildId);
        }
    }
    return $allChildId;
}
/**------------------------------------无限级导航函数-------------------------------**/



/**-------------------------------------获取我的上级所有部门-----------------------**/
function getLeaderbranch($branch,$all_branch,$array=array()){
    $pid=$all_branch[$branch]['pid'];
    if($pid){
        $array[]=$pid;
        return getLeaderbranch($pid,$all_branch,$array);
    }
    return $array;
}
/**-------------------------------------获取我的部门我我的上级所有部门-----------------------**/





/**--------------------------生成后台导航分类--------------------------------**/
/**生成后台导航一级类
 * @param $data
 * @param int $pid
 * @param int $level
 * @return string
 */
function adminMenu($data,$pid=0,$level=0){
    $str="";
    $templete1 = <<<NCTX
       <li><a href="#"><i class="fa fa-home"></i><span class="nav-label">%s</span><span class="fa arrow"></span></a><ul class="nav nav-second-level">%s</ul></li>
NCTX;
    foreach ($data as $k=>$v){
        if($v['fatherid']==0){//如果是顶级分类，生成大类列表
            //下面是将子内容写到一级模板里面去
            $str =$str.sprintf($templete1, $v['name'], adminChildMenu($data,$v['id'],$level+1));
        }
    }
    return $str;
}
/**无限循环生成二级导航以下
 * @param $data
 * @param int $pid
 * @param int $level
 * @return string
 */
function adminChildMenu($data,$pid=0,$level=0){
    $str="";
    $templete2 = <<<NCTX
        <li><a class="J_menuItem" href="%s">%s</a></li>
NCTX;
    $templete3 = <<<NCTX
        <li><a href="#"><i class="fa fa-home"></i><span class="nav-label">%s</span><span class="fa arrow"></span></a><ul class="nav nav-second-level">%s</ul></li>
NCTX;
    foreach ($data as $k=>$v) {
        if ($v['fatherid'] == $pid) {//如果是这个id下的子分类
            if ($v['isdir'] == 1) {//如果是文件夹形式
                $str = $str . sprintf($templete3, $v['name'],adminChildMenu($data,$v['id'],$level+1));
            } else {//是链接形式
                $str = $str . sprintf($templete2, $v['url'],$v['name']);
            }
        }
    }
    return $str;
}
/**--------------------------生成后台导航分类--------------------------------**/

/**-----------------------------经纬度到米--------------------------**/
/**
 * 获取两个经纬度之间的距离 方法一
 * @param $locx1
 * @param $locy1
 * @param $locx2
 * @param $locy2
 * @return float
 */
function pos2m($locx1, $locy1, $locx2, $locy2) {
    $jl_jd = 102834.74258026089786013677476285; //mpr
    $jl_wd = 111712.69150641055729984301412873;
    $b = abs(($locx1 - $locx2 ) * $jl_jd);
    $a = abs(($locy1 - $locy2) * $jl_wd);
    return sqrt(($a * $a+$b * $b));
}

/**
 * 获取两个经纬度之间的距离 方法二
 * @param  string $lat1 纬一
 * @param  String $lng1 经一
 * @param  String $lat2 纬二
 * @param  String $lng2 经二
 * @return float  返回两点之间的距离
 */
function calcDistance($lat1, $lng1, $lat2, $lng2) {
    /** 转换数据类型为 double */
    $lat1 = doubleval($lat1);
    $lng1 = doubleval($lng1);
    $lat2 = doubleval($lat2);
    $lng2 = doubleval($lng2);
    /** 以下算法是 Google 出来的，与大多数经纬度计算工具结果一致 */
    $theta = $lng1 - $lng2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    return ($miles * 1.609344*1000);
}

/**
 * 根据经纬度和半径计算出查询范围
 * @param string $lat 纬度
 * @param String $lng 经度
 * @param float $radius 半径
 * @return Array 范围数组
 */
function calcScope($lat, $lng, $radius) {
    $degree = (24901*1609)/360.0;
    $dpmLat = 1/$degree;

    $radiusLat = $dpmLat*$radius;
    $minLat = $lat - $radiusLat;       // 最小纬度
    $maxLat = $lat + $radiusLat;       // 最大纬度

    $mpdLng = $degree*cos($lat * (PI/180));
    $dpmLng = 1 / $mpdLng;
    $radiusLng = $dpmLng*$radius;
    $minLng = $lng - $radiusLng;      // 最小经度
    $maxLng = $lng + $radiusLng;      // 最大经度

    /** 返回范围数组 */
    $scope = array(
        'minLat'    =>  $minLat,
        'maxLat'    =>  $maxLat,
        'minLng'    =>  $minLng,
        'maxLng'    =>  $maxLng
    );
    return $scope;
}
/**-----------------------------经纬度到米--------------------------**/




/**----------------------------下面是请求--------------------------**/
/**
 * 获取页面内容
 * @param string $url : 页面地址
 * @param int $timeout : 超时
 * @return mixed: 页面内容
 */
function get_url($url, $timeout = 20)
{
    if (function_exists('curl_init')) {     // 服务器支持curl
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curlHandle, CURLOPT_HEADER, FALSE);    // 显示header
        curl_setopt($curlHandle, CURLOPT_NOBODY, FALSE);    // 不显示body
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);    // 超时
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, TRUE); // 重定向
        curl_setopt($curlHandle, CURLOPT_MAXREDIRS, 10);    // 最大跳转次数
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, TRUE); // 获取的信息以文件流的形式返回
        curl_setopt($curlHandle, CURLOPT_USERAGENT, "Chrome/49.0.2623.87");  // 模拟浏览器
        $result = curl_exec($curlHandle);
        curl_close($curlHandle);
    } else {                                // 服务器不支持curl
        $ctx = stream_context_create(array(
            'http' => array(
                'method' => "GET",
                'header' => "Content-Type: text/html; charset=utf-8",
                'timeout' => $timeout
            )
        ));
        $result = file_get_contents($url, 0, $ctx);
    }
    return $result;
}

function tocurl($url, $header, $content){
    $ch = curl_init();
    if(substr($url,0,5)=='https'){
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($content));
    $response = curl_exec($ch);
    if($error=curl_error($ch)){
        die($error);
    }
    curl_close($ch);
    return $response;
}
/**
 * post提交
 * @param string $url : 提交地址
 * @param array $data : 提交的数据，$data = array('A' => '1', 'B' => '2');
 * @param [] $header :
 * @param int $timeout : 超时
 * @return mixed: 返回信息
 */
function post_header_url($url, $data, $header=[],$timeout = 20)
{
    $curlHandle = curl_init(); // 启动一个CURL会话
    curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curlHandle, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curlHandle, CURLOPT_HEADER, FALSE);    // 显示header
    curl_setopt($curlHandle, CURLOPT_NOBODY, FALSE);    // 不显示body
    curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);    // 超时
    curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, TRUE); // 重定向
    curl_setopt($curlHandle, CURLOPT_MAXREDIRS, 20);    // 最大跳转次数
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, TRUE); // 获取的信息以文件流的形式返回
    curl_setopt($curlHandle, CURLOPT_USERAGENT, "Chrome/49.0.2623.87");  // 模拟浏览器
    curl_setopt($curlHandle, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query($data)); // Post提交的数据包
    $result = curl_exec($curlHandle); // 执行操作
    curl_close($curlHandle);
    return $result;
}
function get_header_url($url,$header=[], $timeout = 20)
{
    if (function_exists('curl_init')) {     // 服务器支持curl
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curlHandle, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curlHandle, CURLOPT_HEADER, FALSE);    // 显示header
        curl_setopt($curlHandle, CURLOPT_NOBODY, FALSE);    // 不显示body
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);    // 超时
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, TRUE); // 重定向
        curl_setopt($curlHandle, CURLOPT_MAXREDIRS, 10);    // 最大跳转次数
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, TRUE); // 获取的信息以文件流的形式返回
        curl_setopt($curlHandle, CURLOPT_USERAGENT, "Chrome/49.0.2623.87");  // 模拟浏览器
        $result = curl_exec($curlHandle);
        curl_close($curlHandle);
    } else {                                // 服务器不支持curl
        $ctx = stream_context_create(array(
            'http' => array(
                'method' => "GET",
                'header' => "Content-Type: text/html; charset=utf-8",
                'timeout' => $timeout
            )
        ));
        $result = file_get_contents($url, 0, $ctx);
    }
    return $result;
}
/**
 * post提交
 * @param string $url : 提交地址
 * @param array $data : 提交的数据，$data = array('A' => '1', 'B' => '2');
 * @param int $timeout : 超时
 * @return mixed: 返回信息
 */
function post_url($url, $data, $timeout = 20)
{
    $curlHandle = curl_init(); // 启动一个CURL会话
    curl_setopt($curlHandle, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curlHandle, CURLOPT_HEADER, FALSE);    // 显示header
    curl_setopt($curlHandle, CURLOPT_NOBODY, FALSE);    // 不显示body
    curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);    // 超时
    curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, TRUE); // 重定向
    curl_setopt($curlHandle, CURLOPT_MAXREDIRS, 20);    // 最大跳转次数
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, TRUE); // 获取的信息以文件流的形式返回
    curl_setopt($curlHandle, CURLOPT_USERAGENT, "Chrome/49.0.2623.87");  // 模拟浏览器
    curl_setopt($curlHandle, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query($data)); // Post提交的数据包
    $result = curl_exec($curlHandle); // 执行操作
    curl_close($curlHandle);
    return $result;
}

/**
 * post提交
 * @param string $url : 提交地址
 * @param array $data : 提交的数据，$data = array('A' => '1', 'B' => '2');
 * @param int $timeout : 超时
 * @return mixed: 返回信息
 */
function post_https_url($url, $data, $timeout = 20)
{
    $curlHandle = curl_init(); // 启动一个CURL会话
    curl_setopt($curlHandle, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
    curl_setopt($curlHandle, CURLOPT_HEADER, FALSE);    // 显示header
    curl_setopt($curlHandle, CURLOPT_NOBODY, FALSE);    // 不显示body
    curl_setopt($curlHandle, CURLOPT_TIMEOUT, $timeout);    // 超时
    curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, TRUE); // 重定向
    curl_setopt($curlHandle, CURLOPT_MAXREDIRS, 20);    // 最大跳转次数
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, TRUE); // 获取的信息以文件流的形式返回
    curl_setopt($curlHandle, CURLOPT_USERAGENT, "Chrome/49.0.2623.87");  // 模拟浏览器
    curl_setopt($curlHandle, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curlHandle, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query($data)); // Post提交的数据包
    $result = curl_exec($curlHandle); // 执行操作
    curl_close($curlHandle);
    return $result;
}

/**
 * 通过post传输Json化数据
 * @param $url
 * @param $jsonStr
 * @return array
 */
function http_post_json($url, $jsonStr, $token='')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($jsonStr),
            'Authorization: Bearer '.$token
        )
    );
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array($httpCode, $response);
}

/**
 * 通过post传输Json化数据
 * @param $url
 * @param $jsonStr
 * @return array
 */
function https_post_json($url, $jsonStr, $token='')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 设置超时限制防止死循环
    curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($jsonStr),
            'Authorization: Bearer '.$token
        )
    );
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array($httpCode, $response);
}

/**
 * 通过post传输Json化数据
 * @param $url
 * @param $jsonStr
 * @return array
 */
function http_post_header_json($url, $jsonStr,$token)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($jsonStr),
            'x-ol-authtoken-ssl : '.$token
        )
    );
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array($httpCode, $response);
}

/**
 * 判断是不是手机访问
 * @return bool
 */
function isMobile(){
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){return true;}
    //此条摘自TPM智能切换模板引擎，适合TPM开发
    if(isset ($_SERVER['HTTP_CLIENT']) &&'PhoneClient'==$_SERVER['HTTP_CLIENT']){return true;}
    //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息  //找不到为flase,否则为true
    if (isset ($_SERVER['HTTP_VIA']) &&stristr($_SERVER['HTTP_VIA'], 'wap')){return true;}
    //判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel',
            'lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm',
            'operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile','motorola','softbank','foma','docomo',
            'kddi','dopod','blazer','helio','hosin','huawei','novarra','CoolPad','webos','techfaith','palmsource',
            'amoi','ktouch','nexian','sagem','wellcom','bunjalloo','maui','smartphone','phone','iemobile','longcos',
            'pantech','gionee','portalmmm','hiptop','mzbrowser'
        );
        //从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {return true;}
    }
    //协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

/**
 * 发送HTTP请求方法，目前只支持CURL发送请求
 * @param  string  $url    请求URL
 * @param  array   $params 请求参数
 * @param  string  $method 请求方法GET/POST
 * @param  boolean $ssl    是否进行SSL双向认证
 * @return array   $data   响应数据
 */
function http($url, $params = array(), $method = 'GET'){
    $opts = array(
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    );
    /* 根据请求类型设置特定参数 */
    switch(strtoupper($method)){
        case 'GET':
            if(empty($params)){
                $opts[CURLOPT_URL] = $url;
            }else{
                $opts[CURLOPT_URL] = $url .'?'. http_build_query($params);
            }
            break;
        case 'POST':
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
    }
    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $err = curl_errno($ch);
    $errmsg = curl_error($ch);
    curl_close($ch);
    if ($err > 0) {
        return false;
    }else {
        return $data;
    }
}

/**
 * 判断是不是手机号
 */
function is_phone($phonenumber){
    return preg_match("/^1[123456789]{1}\d{9}$/",$phonenumber)?true:false;
}

/**
 * 判断是不是是不是邮箱
 * @param $email
 * @return bool
 */
function is_email($email)
{
    return preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $email)?true:false;
}

/**
 * 过滤城市中的尾部字符
 * @param $city
 * @param $filterStr  要过滤的数组
 * @return mixed|string
 */
function filterCity($city,$filterStr){
    $city=trim($city);//先过虑空格
    //获取尾部的最后一个字
    $str=mb_substr($city,-1,1,'utf-8');
    if(in_array($str,$filterStr)){
        $city=str_replace($str,'',$city);
    }
    return $city;
}

/**
 * 删除目录及目录下所有文件或删除指定文件
 */
function delDirAndFile($path, $delDir = true) {
    $handle = opendir($path);
    if ($handle) {
        while (false !== ( $item = readdir($handle) )) {
            if ($item != "." && $item != "..")
                is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
        }
        closedir($handle);
        if ($delDir){
            return rmdir($path);
        }
    }else {
        if (file_exists($path)) {
            return unlink($path);
        } else {
            return false;
        }
    }
}