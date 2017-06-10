<?php
return array(
    // '配置项'=>'配置值'
    'URL_MODEL' => 0,
    'DB_TYPE' => 'mysql', // 数据库类型
    'DB_HOST' => '127.0.0.1', // 服务器地址
    'DB_NAME' => 'KWV2', // 数据库名
    'DB_USER' => 'root', // 用户名
    'DB_PWD' => 'lcz19860109', // 密码
    'DB_PORT' => 3306, // 端口
    'DB_PREFIX' => 'tk_', // 数据库表前缀
    'DB_CHARSET' => 'utf8', // 字符集
    'URL_CASE_INSENSITIVE' => false,

    'LOG_RECORD' => false, 
    
    // 模板
    'LAYOUT_ON' => true,
    'LAYOUT_NAME' => 'Layout/index',
    'LOAD_EXT_CONFIG' => 'menu',


    // 设置session
    'SESSION_PREFIX' => 'crm_',
    'SESSION_OPTIONS' => ['expire'=>'43200'],

    'DATA_CACHE_TYPE' => 'Memcache',
    'MEMCACHE_HOST' => '127.0.0.1',
    'MEMCACHE_PORT'	=> '11211',

  /*  //替换语言包
    'LANG_AUTO_DETECT' => true,
    'LANG_SWITCH_ON' => TRUE,
    'DEFAULT_LANG' => '/Lang/zh-cn.php',
    //防重复提交
    'TOKEN_ON' => true,
    'TOKEN_NAME' => '__hash__',
    'TOKEN_TYPE' => 'md5',
    'TOKEN_RESET' => true*/
);
