<?php
// +----------------------------------------------------------------------
// | 星益云 [ Remain true to our original aspiration ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018-至今 http://www.96xy.cn/ All rights reserved.
// +----------------------------------------------------------------------
// | 星益云聚合收银台系统 - 配置文件
// +----------------------------------------------------------------------
define('CASHIER_VERSION', '1.0');  //SDK版本号（勿动）
define('CASHIER_BUILD', 1000);  //SDK更新批号（勿动）
//星益云聚合收银台系统 - 开发文档：https://www.kancloud.cn/xingyi/kfwd/2396471
$config = array(
    'url'=>$channel['appurl'],  //聚合收银台地址

    'uid'=>$channel['appid'],  //商户UID

    'key'=>$channel['appkey'],  //商户KEY

    'sign_type'=>'md5',  //不懂不要修改！

    'eliminate'=>['key']  //商户身份验证时，请求参数剔除指定字段，不懂不要修改！
);