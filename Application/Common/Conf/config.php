<?php
return array(
    'DB_TYPE'      => 'mysql', // 数据库类型
    // 'DB_HOST'      => '115.159.90.233', // 服务器地址
    'DB_HOST'      => '192.168.1.135', // 服务器地址
    'DB_NAME'      => 'oa', // 数据库名
    'DB_USER'      => 'root', // 用户名
    'DB_PWD'       => 'cui19950422', // 密码
    'DB_PORT'      => '3306', // 端口
    'DB_PREFIX'    => 'oa_', // 数据库表前缀
    

    'URL_MODEL'    => 2, // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
    // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式
    //丁冬云的apikey
    'DDY_apikey'   => '9af458b2e9eb96490266d0ad0c9dc6e7',
    //默认头像
    'DefaultPhoto' => 'http://ww2.sinaimg.cn/large/80b3680bgw1f7lyq2racej204k04kt8k.jpg',
/*    //七牛的配置信息
'Qiniu_accessKey' => 'iPh9APMr_ujzrqgsX9pvwDJXG6T1TcH3Kssfi9ss',
'Qiniu_secretKey' => 'ICZdSZKG3zrRQ3QsKv0LVP0Wvs5fv8c9fxBXwzS8',
'Qiniu_bucket'    => 'gaojibang',*/
);
