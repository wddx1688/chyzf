<?php
$config = array (
	//签名方式,默认为RSA2(RSA2048)
	'sign_type' => "RSA2",

	//支付宝公钥
	'alipay_public_key' => $channel['appkey'],

	//商户私钥
	'merchant_private_key' => $channel['appsecret'],

	//编码格式
	'charset' => "UTF-8",

	//支付宝网关
	'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

	//应用ID
	'app_id' => $channel['appid'],

	//异步通知地址
	'notify_url' => $conf['localurl'].'pay/notify/'.(defined("TRADE_NO")?TRADE_NO:$order['trade_no']).'/',

	//同步通知地址
	'return_url' => $conf['localurl'].'pay/return/'.(defined("TRADE_NO")?TRADE_NO:$order['trade_no']).'/',

	//商户授权token
	'app_auth_token' => $channel['appmchid'],
);

if(file_exists(PAY_ROOT.'cert/'.$channel['appid'].'/appCertPublicKey_'.$channel['appid'].'.crt')){
	$config['appCertPath'] = PAY_ROOT.'cert/'.$channel['appid'].'/appCertPublicKey_'.$channel['appid'].'.crt';
	$config['alipayCertPath'] = PAY_ROOT.'cert/'.$channel['appid'].'/alipayCertPublicKey_RSA2.crt';
	$config['rootCertPath'] = PAY_ROOT.'cert/'.$channel['appid'].'/alipayRootCert.crt';
}
elseif(file_exists(PAY_ROOT.'cert/appCertPublicKey_'.$channel['appid'].'.crt')){
	$config['appCertPath'] = PAY_ROOT.'cert/appCertPublicKey_'.$channel['appid'].'.crt';
	$config['alipayCertPath'] = PAY_ROOT.'cert/alipayCertPublicKey_RSA2.crt';
	$config['rootCertPath'] = PAY_ROOT.'cert/alipayRootCert.crt';
}