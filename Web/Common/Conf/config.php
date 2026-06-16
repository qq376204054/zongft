<?php
return array(
    //下面是数据库连接配置
    'DB_TYPE' => 'mysql',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'yq_system',
    'DB_USER' => 'root',
    'DB_PWD' => 'root',//QWEqwe679506
    'DB_PREFIX' => 'yq_',

    //下面是用文件缓存
    'DATA_CACHE_TYPE' => 'file',//缓存类型
    'DATA_CACHE_TIME' => 0,//缓存时间  0标识永久缓存

    //开启页面调试显示页面Trace信息
    'SHOW_PAGE_TRACE' => false,

    //URL访问模式,可选参数0、1、2、3,代表四种模式，为2时隐藏URL中index.php,需配置服务器
    'URL_MODEL' =>  1,

    //SESSION相关配置信息
    'SESSION_OPTIONS' => array(
        'name'              =>  'BJYSESSION', //设置session名
        'expire'            =>  60*60*24, //SESSION过期时间，单位秒
        'use_trans_sid'     =>  1, //跨页传递
        'use_only_cookies'  =>  0, //是否只开启基于cookies的session的会话方式
    )
);