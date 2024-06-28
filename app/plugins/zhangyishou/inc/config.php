<?php
$pay_config = array (
	//登录账号
	'MerchantId' => $channel['appid'],

	//商户编号
	'MerchantNo' => $channel['appurl'],

	//商户密钥
	'key' => $channel['appkey'],

	//通道ID
	'PayChannelId' => $channel['appmchid'],
);

function zz_get_curl($url, $post=0)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	/*curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_PROXY, '');
	curl_setopt($ch, CURLOPT_PROXYPORT, 808);
	curl_setopt($ch, CURLOPT_PROXYUSERPWD, '');
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);*/
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$httpheader[] = "Accept: */*";
	$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
	$httpheader[] = "Content-Type: application/json; charset=utf-8";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.0.4; es-mx; HTC_One_X Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$ret = curl_exec($ch);
	curl_close($ch);
	return $ret;
}