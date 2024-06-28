<?php
function curl_get($url)
{
	global $conf;
	$ch=curl_init($url);
	if($conf['proxy'] == 1){
		$proxy_server = $conf['proxy_server'];
		$proxy_port = intval($conf['proxy_port']);
		$proxy_userpwd = $conf['proxy_user'].':'.$conf['proxy_pwd'];
		if($conf['proxy_type'] == 'https'){
			$proxy_type = CURLPROXY_HTTPS;
		}elseif($conf['proxy_type'] == 'sock4'){
			$proxy_type = CURLPROXY_SOCKS4;
		}elseif($conf['proxy_type'] == 'sock5'){
			$proxy_type = CURLPROXY_SOCKS5;
		}else{
			$proxy_type = CURLPROXY_HTTP;
		}
		curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_PROXY, $proxy_server);
		curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_userpwd);
		curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type);
	}
	$httpheader[] = "Accept: */*";
	$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
	$httpheader[] = "Connection: close";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36');
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	$content=curl_exec($ch);
	curl_close($ch);
	return $content;
}
function get_curl($url, $post=0, $referer=0, $cookie=0, $header=0, $ua=0, $nobaody=0, $addheader=0)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$httpheader[] = "Accept: */*";
	$httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
	$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
	$httpheader[] = "Connection: close";
	if($addheader){
		$httpheader = array_merge($httpheader, $addheader);
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	if ($header) {
		curl_setopt($ch, CURLOPT_HEADER, true);
	}
	if ($cookie) {
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}
	if($referer){
		if($referer==1){
			curl_setopt($ch, CURLOPT_REFERER, 'http://m.qzone.com/infocenter?g_f=');
		}else{
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
	}
	if ($ua) {
		curl_setopt($ch, CURLOPT_USERAGENT, $ua);
	}
	else {
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.0.4; es-mx; HTC_One_X Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0");
	}
	if ($nobaody) {
		curl_setopt($ch, CURLOPT_NOBODY, 1);
	}
	curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$ret = curl_exec($ch);
	curl_close($ch);
	return $ret;
}
function real_ip($type=0){
$ip = $_SERVER['REMOTE_ADDR'];
if($type<=0 && isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
	foreach ($matches[0] AS $xip) {
		if (filter_var($xip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			$ip = $xip;
			break;
		}
	}
} elseif ($type<=0 && isset($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
	$ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif ($type<=1 && isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
	$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
} elseif ($type<=1 && isset($_SERVER['HTTP_X_REAL_IP']) && filter_var($_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
	$ip = $_SERVER['HTTP_X_REAL_IP'];
}
return $ip;
}
function get_ip_city($ip)
{
    $url = 'http://whois.pconline.com.cn/ipJson.jsp?json=true&ip=';
    $city = get_curl($url . $ip);
	$city = mb_convert_encoding($city, "UTF-8", "GB2312");
    $city = json_decode($city, true);
    if ($city['city']) {
        $location = $city['pro'].$city['city'];
    } else {
        $location = $city['pro'];
    }
	if($location){
		return $location;
	}else{
		return false;
	}
}
function send_mail($to, $sub, $msg) {
	global $conf;
	if($conf['mail_cloud']==1){
		$mail = new \lib\mail\Sendcloud($conf['mail_apiuser'], $conf['mail_apikey']);
		return $mail->send($to, $sub, $msg, $conf['mail_name2'], $conf['sitename']);
	}elseif($conf['mail_cloud']==2){
		$mail = new \lib\mail\Aliyun($conf['mail_apiuser'], $conf['mail_apikey']);
		return $mail->send($to, $sub, $msg, $conf['mail_name2'], $conf['sitename']);
	}else{
		if(!$conf['mail_name'] || !$conf['mail_port'] || !$conf['mail_smtp'] || !$conf['mail_pwd'])return false;
		$port = intval($conf['mail_port']);
		$mail = new \lib\mail\PHPMailer\PHPMailer(true);
		try{
			$mail->SMTPDebug = 0;
			$mail->CharSet = 'UTF-8';
			$mail->Timeout = 5;
			$mail->isSMTP();
			$mail->Host = $conf['mail_smtp'];
			$mail->SMTPAuth = true;
			$mail->Username = $conf['mail_name'];
			$mail->Password = $conf['mail_pwd'];
			if($port == 587) $mail->SMTPSecure = 'tls';
			else if($port >= 465) $mail->SMTPSecure = 'ssl';
			else $mail->SMTPAutoTLS = false;
			$mail->Port = intval($conf['mail_port']);
			$mail->setFrom($conf['mail_name'], $conf['sitename']);
			$mail->addAddress($to);
			$mail->addReplyTo($conf['mail_name'], $conf['sitename']);
			$mail->isHTML(true);
			$mail->Subject = $sub;
			$mail->Body = $msg;
			$mail->send();
			return true;
		} catch (Exception $e) {
			return $mail->ErrorInfo;
		}
	}
}
function send_sms($phone, $code, $scope='reg'){
	global $conf;
	if($scope == 'reg'){
		$moban = $conf['sms_tpl_reg'];
	}elseif($scope == 'login'){
		$moban = $conf['sms_tpl_login'];
	}elseif($scope == 'find'){
		$moban = $conf['sms_tpl_find'];
	}elseif($scope == 'edit'){
		$moban = $conf['sms_tpl_edit'];
	}
	if($conf['sms_api']==1){
		$ssender = new \lib\sms\TencentSms($conf['sms_appid'], $conf['sms_appkey']);
		$params = array($code);
		$smsSign = $conf['sms_sign'];
		$result = $ssender->sendWithParam("86", $phone, $moban, $params, $smsSign, "", "");
		$arr = json_decode($result,true);
		if(isset($arr['result']) && $arr['result']==0){
			return true;
		}else{
			return $arr['errmsg'];
		}
	}elseif($conf['sms_api']==2){
		$sms = new \lib\sms\Aliyun($conf['sms_appid'], $conf['sms_appkey']);
		$arr = $sms->send($phone, $code, $moban, $conf['sms_sign'], $conf['sitename']);
		if(isset($arr['Code']) && $arr['Code']=='OK'){
			return true;
		}else{
			return $arr['Message'];
		}
	}elseif($conf['sms_api']==3){
		$app=$conf['sitename'];
		$url = 'https://api.topthink.com/sms/send';
		$param = ['appCode'=>$conf['sms_appkey'], 'signId'=>$conf['sms_sign'], 'templateId'=>$moban, 'phone'=>$phone, 'params'=>json_encode(['code'=>$code])];
		$data=get_curl($url, http_build_query($param));
		$arr=json_decode($data,true);
		if(isset($arr['code']) && $arr['code']==0){
			return true;
		}else{
			return $arr['message'];
		}
	}else{
		$app=$conf['sitename'];
		$url = 'http://sms.php.gs/sms/send/yzm';
		$param = ['appkey'=>$conf['sms_appkey'], 'phone'=>$phone, 'moban'=>$moban, 'code'=>$code, 'app'=>$app];
		$data=get_curl($url, http_build_query($param));
		$arr=json_decode($data,true);
		if($arr['status']=='200'){
			return true;
		}else{
			return $arr['error_msg_zh'];
		}
	}
}
function daddslashes($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = daddslashes($val);
		}
	} else {
		$string = addslashes($string);
	}
	return $string;
}

function strexists($string, $find) {
	return !(strpos($string, $find) === FALSE);
}

function dstrpos($string, $arr) {
	if(empty($string)) return false;
	foreach((array)$arr as $v) {
		if(strpos($string, $v) !== false) {
			return true;
		}
	}
	return false;
}

function checkmobile() {
	$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
	$ualist = array('android', 'midp', 'nokia', 'mobile', 'iphone', 'ipod', 'blackberry', 'windows phone');
	if((dstrpos($useragent, $ualist) || strexists($_SERVER['HTTP_ACCEPT'], "VND.WAP") || strexists($_SERVER['HTTP_VIA'],"wap")))
		return true;
	else
		return false;
}
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5($key);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);
	$result = '';
	$box = range(0, 255);
	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	if($operation == 'DECODE') {
		if(((int)substr($result, 0, 10) == 0 || (int)substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}

function random($length) {
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	$hash = '';
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed[mt_rand(0, $max)];
	}
	return $hash;
}
function showmsg($content = '未知的异常',$type = 4,$back = false)
{
switch($type)
{
case 1:
	$panel="success";
break;
case 2:
	$panel="info";
break;
case 3:
	$panel="warning";
break;
case 4:
	$panel="danger";
break;
}

echo '<div class="panel panel-'.$panel.'">
      <div class="panel-heading">
        <h3 class="panel-title">提示信息</h3>
        </div>
        <div class="panel-body">';
echo $content;

if ($back) {
	echo '<hr/><a href="'.$back.'"><< 返回上一页</a>';
}
else
    echo '<hr/><a href="javascript:history.back(-1)"><< 返回上一页</a>';

echo '</div>
    </div>';
	exit;
}
function sysmsg($msg = '未知的异常',$title = '站点提示信息') {
    ?>  
    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml" lang="zh-CN">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title?></title>
        <style type="text/css">
html{background:#eee}body{background:#fff;color:#333;font-family:"微软雅黑","Microsoft YaHei",sans-serif;margin:2em auto;padding:1em 2em;max-width:700px;-webkit-box-shadow:10px 10px 10px rgba(0,0,0,.13);box-shadow:10px 10px 10px rgba(0,0,0,.13);opacity:.8}h1{border-bottom:1px solid #dadada;clear:both;color:#666;font:24px "微软雅黑","Microsoft YaHei",sans-serif;margin:30px 0 0 0;padding:0;padding-bottom:7px}#error-page{margin-top:50px}h3{text-align:center}#error-page p{font-size:9px;line-height:1.5;margin:25px 0 20px}#error-page code{font-family:Consolas,Monaco,monospace}ul li{margin-bottom:10px;font-size:9px}a{color:#21759B;text-decoration:none;margin-top:-10px}a:hover{color:#D54E21}.button{background:#f7f7f7;border:1px solid #ccc;color:#555;display:inline-block;text-decoration:none;font-size:9px;line-height:26px;height:28px;margin:0;padding:0 10px 1px;cursor:pointer;-webkit-border-radius:3px;-webkit-appearance:none;border-radius:3px;white-space:nowrap;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;-webkit-box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);vertical-align:top}.button.button-large{height:29px;line-height:28px;padding:0 12px}.button:focus,.button:hover{background:#fafafa;border-color:#999;color:#222}.button:focus{-webkit-box-shadow:1px 1px 1px rgba(0,0,0,.2);box-shadow:1px 1px 1px rgba(0,0,0,.2)}.button:active{background:#eee;border-color:#999;color:#333;-webkit-box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5);box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5)}table{table-layout:auto;border:1px solid #333;empty-cells:show;border-collapse:collapse}th{padding:4px;border:1px solid #333;overflow:hidden;color:#333;background:#eee}td{padding:4px;border:1px solid #333;overflow:hidden;color:#333}
        </style>
    </head>
    <body id="error-page">
        <?php echo '<h3>'.$title.'</h3>';
        echo $msg; ?>
    </body>
    </html>
    <?php
    exit;
}
function returnTemplate($return_url) {
	$url = base64_encode($return_url);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>支付成功跳转页面</title>
        <style type="text/css">
body{margin:0;padding:0}
p{position:absolute;left:50%;top:50%;height:35px;margin:-35px 0 0 -160px;padding:20px;font:bold 16px/30px "宋体",Arial;background:#f9fafc url(/assets/img/loading.gif) no-repeat 20px 20px;text-indent:40px;border:1px solid #c5d0dc}
#waiting{font-family:Arial}
        </style>
    </head>
    <body id="return-page">
        <p>支付成功，正在跳转请稍候...</p>
    </body>
	<script>window.location.href=window.atob("<?php echo $url?>");</script>
    </html>
    <?php
    exit;
}
function getSid() {
    return md5(uniqid(mt_rand(), true) . microtime());
}
function getMd5Pwd($pwd, $salt=null) {
    return md5(md5($pwd) . md5('1277180438'.$salt));
}

/**
 * 取中间文本
 * @param string $str
 * @param string $leftStr
 * @param string $rightStr
 */
function getSubstr($str, $leftStr, $rightStr)
{
	$left = strpos($str, $leftStr);
	$start = $left+strlen($leftStr);
	$right = strpos($str, $rightStr, $start);
	if($left < 0) return '';
	if($right>0){
		return substr($str, $start, $right-$start);
	}else{
		return substr($str, $start);
	}
}

function getSetting($k, $force = false){
	global $DB,$CACHE;
	if($force) return $DB->getColumn("SELECT v FROM pre_config WHERE k=:k LIMIT 1", [':k'=>$k]);
	$cache = $CACHE->get($k);
	return $cache[$k];
}
function saveSetting($k, $v){
	global $DB;
	return $DB->exec("REPLACE INTO pre_config SET v=:v,k=:k", [':v'=>$v, ':k'=>$k]);
}
function checkGroupSettings($str){
	foreach(explode(',',$str) as $row){
		if(!strpos($row,':'))return false;
	}
	return true;
}

function creat_callback($data){
	global $DB,$conf;
	$key=$DB->getColumn("SELECT `key` FROM pre_user WHERE uid='{$data['uid']}' LIMIT 1");
	$type=$DB->getColumn("SELECT name FROM pre_type WHERE id='{$data['type']}' LIMIT 1");
	$array=array('pid'=>$data['uid'],'trade_no'=>$data['trade_no'],'out_trade_no'=>$data['out_trade_no'],'type'=>$type,'name'=>$data['name'],'money'=>(float)$data['money'],'trade_status'=>'TRADE_SUCCESS');
	if(!empty($data['param']))$array['param']=$data['param'];
	if($conf['notifyordername']==1)$array['name']='product';
	$arg=\lib\PayUtils::argSort(\lib\PayUtils::paraFilter($array));
	$prestr=\lib\PayUtils::createLinkstring($arg);
	$urlstr=\lib\PayUtils::createLinkstringUrlencode($arg);
	$sign=\lib\PayUtils::md5Sign($prestr, $key);
	if(strpos($data['notify_url'],'?'))
		$url['notify']=$data['notify_url'].'&'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
	else
		$url['notify']=$data['notify_url'].'?'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
	if(strpos($data['return_url'],'?'))
		$url['return']=$data['return_url'].'&'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
	else
		$url['return']=$data['return_url'].'?'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
	if($data['tid']>0){
		$url['return']=$data['return_url'];
	}
	return $url;
}

function creat_callback_user($data, $key=null){
	global $DB,$conf;
	if(!$key)$key=$DB->getColumn("SELECT `key` FROM pre_user WHERE uid='{$data['uid']}' LIMIT 1");
	$type=$DB->getColumn("SELECT name FROM pre_type WHERE id='{$data['type']}' LIMIT 1");
	$array=array('pid'=>$data['uid'],'trade_no'=>$data['trade_no'],'out_trade_no'=>$data['out_trade_no'],'type'=>$type,'name'=>$data['name'],'money'=>(float)$data['money'],'trade_status'=>$data['type']>0?'TRADE_SUCCESS':'TRADE_CLOSED');
	if(!empty($data['param']))$array['param']=$data['param'];
	if($conf['notifyordername']==1)$array['name']='product';
	$arg=\lib\PayUtils::argSort(\lib\PayUtils::paraFilter($array));
	$prestr=\lib\PayUtils::createLinkstring($arg);
	$urlstr=\lib\PayUtils::createLinkstringUrlencode($arg);
	$sign=\lib\PayUtils::md5Sign($prestr, $key);
	if(strpos($data['notify_url'],'?'))
		$url['notify']=$data['notify_url'].'&'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
	else
		$url['notify']=$data['notify_url'].'?'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
	if(strpos($data['return_url'],'?'))
		$url['return']=$data['return_url'].'&'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
	else
		$url['return']=$data['return_url'].'?'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
	if($data['tid']>0){
		$url['return']=$data['return_url'];
	}
	return $url;
}

function getdomain($url){
	$arr=parse_url($url);
	$host = $arr['host'];
	if(isset($arr['port']) && $arr['port']!=80 && $arr['port']!=443)$host .= ':'.$arr['port'];
	return $host;
}

function do_notify($url){
	$return = curl_get($url);
	if(strpos($return,'success')!==false || strpos($return,'SUCCESS')!==false || strpos($return,'Success')!==false){
		return true;
	}else{
		return false;
	}
}

function checkBlockUser($openid, $trade_no){
	global $DB,$conf;
	$DB->update('order', ['buyer'=>$openid], ['trade_no'=>$trade_no]);
	if($conf['blockusers']){
		$blockusers = explode('|',$conf['blockusers']);
		if(in_array($openid, $blockusers))return ['type'=>'error','msg'=>'系统异常无法完成付款'];
	}
	return false;
}

function processReturn($order, $api_trade_no=null, $buyer=null){
	\lib\Payment::processOrder(false, $order, $api_trade_no, $buyer);
}

function processNotify($order, $api_trade_no=null, $buyer=null){
	\lib\Payment::processOrder(true, $order, $api_trade_no, $buyer);
}

function processOrder($srow,$notify=true){
	global $DB,$CACHE,$conf,$channel;
	$addmoney = $srow['getmoney'];
	$reducemoney = round($srow['realmoney']-$srow['getmoney'], 2);
	if($reducemoney<0)$reducemoney=0;
	if($srow['tid']==1){ //商户注册
		changeUserMoney($srow['uid'], $addmoney, true, '订单收入', $srow['trade_no']);
		$info = unserialize($CACHE->read('reg_'.$srow['trade_no']));
		if($info){
			$DB->exec("UPDATE `pre_regcode` SET `status` ='1' WHERE `id`=:codeid", [':codeid'=>$info['codeid']]);
			$key = random(32);
			$paystatus = $conf['user_review']==1?2:1;
			$sds=$DB->exec("INSERT INTO `pre_user` (`key`, `money`, `email`, `phone`, `addtime`, `pay`, `settle`, `keylogin`, `apply`, `status`) VALUES (:key, '0.00', :email, :phone, :addtime, :paystatus, 1, 0, 0, 1)", [':key'=>$key, ':email'=>$info['email'], ':phone'=>$info['phone'], ':addtime'=>$info['addtime'], ':paystatus'=>$paystatus]);
			$uid=$DB->lastInsertId();
			$pwd = getMd5Pwd($info['pwd'], $uid);
			$DB->exec("UPDATE `pre_user` SET `pwd` ='{$pwd}' WHERE `uid`='$uid'");
			if($sds && !empty($info['email'])){
				$sub = $conf['sitename'].' - 注册成功通知';
				$msg = '<h2>商户注册成功通知</h2>感谢您注册'.$conf['sitename'].'！<br/>您的登录账号：'.$info['email'].'<br/>您的商户ID：'.$uid.'<br/>您的商户秘钥：'.$key.'<br/>'.$conf['sitename'].'官网：<a href="http://'.$_SERVER['HTTP_HOST'].'/" target="_blank">'.$_SERVER['HTTP_HOST'].'</a><br/>【<a href="http://'.$_SERVER['HTTP_HOST'].'/user/" target="_blank">商户管理后台</a>】';
				$result = send_mail($info['email'], $sub, $msg);
			}
		}
	}else if($srow['tid']==2){ //充值余额
		changeUserMoney($srow['uid'], $addmoney, true, '余额充值', $srow['trade_no']);
	}else if($srow['tid']==3){ //一码支付
		if($channel['mode']==1){
			if($reducemoney>0)
				changeUserMoney($srow['uid'], $reducemoney, false, '在线收款服务费', $srow['trade_no']);
		}else{
			changeUserMoney($srow['uid'], $addmoney, true, '在线收款', $srow['trade_no']);
		}
	}else if($srow['tid']==4){ //购买用户组
		$start = strpos($srow['name'],'#')+1;
		$end = strrpos($srow['name'],'#');
		$gid=intval(substr($srow['name'],$start,$end-$start));
		changeUserGroup($srow['uid'],$gid);
	}else{
		if($channel['mode']==1){
			if($reducemoney>0)
				changeUserMoney($srow['uid'], $reducemoney, false, '订单服务费', $srow['trade_no']);
		}else{
			changeUserMoney($srow['uid'], $addmoney, true, '订单收入', $srow['trade_no']);
		}
		/*if(preg_match('/X(\d+)\!(\d+)X/',$srow['name'],$match)){
			if($match[1] >= 1000 && $match[2] > 0 && $match[2] < 100){
				$addmoney = round($srow['money']*$match[2]/100,2);
				changeUserMoney($match[1], $addmoney, true, '订单分成', $srow['trade_no']);
			}
		}*/
		$url=creat_callback($srow);
		if(do_notify($url['notify'])){
			$DB->exec("UPDATE pre_order SET notify=0 WHERE trade_no='{$srow['trade_no']}'");
		}elseif($notify==true){
			//通知时间：1分钟，3分钟，20分钟，1小时，2小时
			$DB->exec("UPDATE pre_order SET notify=1,notifytime=date_add(now(), interval 1 minute) WHERE trade_no='{$srow['trade_no']}'");
		}
	}
	if($channel['daytop']>0){
		$cachekey = 'daytop'.$channel['id'].date("Ymd");
		$nowmoney = $CACHE->read($cachekey);
		if(!$nowmoney)$nowmoney=0;
		$nowmoney=$nowmoney+$srow['money'];
		$CACHE->save($cachekey, $nowmoney);
		if($nowmoney>=$channel['daytop']){
			$DB->exec("UPDATE pre_channel SET daystatus=1 WHERE id='{$channel['id']}'");
		}
	}
}

function changeUserMoney($uid, $money, $add=true, $type=null, $orderid=null){
	global $DB;
	if($money<=0)return;
	if($type=='订单退款'){
		$isrefund = $DB->getColumn("SELECT id FROM pre_record WHERE type='订单退款' AND trade_no='{$orderid}' LIMIT 1");
		if($isrefund)return;
	}
	$DB->beginTransaction();
	$oldmoney = $DB->getColumn("SELECT money FROM pre_user WHERE uid='{$uid}' LIMIT 1 FOR UPDATE");
	if($add == true){
		$action = 1;
		$newmoney = round($oldmoney+$money, 2);
	}else{
		$action = 2;
		$newmoney = round($oldmoney-$money, 2);
	}
	$res = $DB->exec("UPDATE pre_user SET money='{$newmoney}' WHERE uid='{$uid}'");
	$DB->exec("INSERT INTO `pre_record` (`uid`, `action`, `money`, `oldmoney`, `newmoney`, `type`, `trade_no`, `date`) VALUES (:uid, :action, :money, :oldmoney, :newmoney, :type, :orderid, NOW())", [':uid'=>$uid, ':action'=>$action, ':money'=>$money, ':oldmoney'=>$oldmoney, ':newmoney'=>$newmoney, ':type'=>$type, ':orderid'=>$orderid]);
	$DB->commit();
	return $res;
}

function changeUserGroup($uid, $gid){
	global $DB;
	return $DB->exec("UPDATE pre_user SET gid='{$gid}' WHERE uid='{$uid}'");
}

function checkIfActive($string) {
	$array=explode(',',$string);
	$php_self=substr($_SERVER['REQUEST_URI'],strrpos($_SERVER['REQUEST_URI'],'/')+1,strrpos($_SERVER['REQUEST_URI'],'.')-strrpos($_SERVER['REQUEST_URI'],'/')-1);
	if (in_array($php_self,$array)){
		return 'active';
	}else
		return null;
}

//通用转账
//type alipay:支付宝,wxpay:微信,qqpay:QQ钱包,bank:银行卡
function transfer_do($type, $channel, $out_trade_no, $payee_account, $payee_real_name, $money){
	global $conf;
	if($type == 'alipay'){
		return transferToAlipay($channel, $out_trade_no, $payee_account, $payee_real_name, $money);
	}elseif($type == 'wxpay'){
		return transferToWeixin($channel, $out_trade_no, $payee_account, $payee_real_name, $money);
	}elseif($type == 'qqpay'){
		return transferToQQ($channel, $out_trade_no, $payee_account, $payee_real_name, $money);
	}elseif($type == 'bank'){
		return transferToBank($channel, $out_trade_no, $payee_account, $payee_real_name, $money);
	}
	return false;
}

//单笔转账到支付宝
function transferToAlipay($channel, $out_trade_no, $payee_account, $payee_real_name, $money){
	global $conf;
	define("IN_PLUGIN", true);
	define("PAY_ROOT", PLUGIN_ROOT.'alipay/');

	if(is_numeric($payee_account) && substr($payee_account,0,4)=='2088' && strlen($payee_account)==16)$is_userid = true;
	else $is_userid = false;

	require_once PAY_ROOT."inc/AlipayTransferService.php";
	$transfer = new AlipayTransferService($config);
	$result = $transfer->transferToAccount($out_trade_no, $money, $is_userid, $payee_account, $payee_real_name, $conf['transfer_name']);

	$data = array();
	if(!empty($result['code'])&&$result['code'] == 10000){
		$data['code']=0;
		$data['ret']=1;
		$data['msg']='success';
		$data['orderid']=$result['order_id'];
		$data['paydate']=$result['pay_date']?$result['pay_date']:$result['trans_date'];
	} elseif($result['code'] == 40004) {
		$data['code']=0;
		$data['ret']=0;
		$data['msg']='['.$result['sub_code'].']'.$result['sub_msg'];
		$data['sub_code']=$result['sub_code'];
		$data['sub_msg']=$result['sub_msg'];
	} elseif(!empty($result['code'])){
		$data['code']=-1;
		$data['msg']='['.$result['sub_code'].']'.$result['sub_msg'];
		$data['sub_code']=$result['sub_code'];
		$data['sub_msg']=$result['sub_msg'];
	} else {
		$data['code']=-1;
		$data['msg']='未知错误';
	}
	return $data;
}

//微信企业付款
function transferToWeixin($channel, $out_trade_no, $payee_account, $payee_real_name, $money){
	global $conf;
	define("IN_PLUGIN", true);
	define("PAY_ROOT", PLUGIN_ROOT.'wxpay/');
	require_once PAY_ROOT."inc/WxPay.Api.php";
	$input = new WxPayTransfer();
	$input->SetPartner_trade_no($out_trade_no);
	$input->SetOpenid($payee_account);
	if(!empty($payee_real_name)){
		$input->SetCheck_name('FORCE_CHECK');
		$input->SetRe_user_name($payee_real_name);
	}else{
		$input->SetCheck_name('NO_CHECK');
	}
	$input->SetAmount($money*100);
	$input->SetDesc($conf['transfer_desc']);
	$input->SetSpbill_create_ip($_SERVER['SERVER_ADDR']);
	$result = WxPayApi::transfer($input);

	$data = array();
	if($result["result_code"]=='SUCCESS'){
		$data['code']=0;
		$data['ret']=1;
		$data['msg']='success';
		$data['orderid']=$result["payment_no"];
		$data['paydate']=$result["payment_time"];
	} elseif($result["result_code"]=='FAIL' && ($result["err_code"]=='OPENID_ERROR'||$result["err_code"]=='NAME_MISMATCH'||$result["err_code"]=='MONEY_LIMIT'||$result["err_code"]=='V2_ACCOUNT_SIMPLE_BAN')) {
		$data['code']=0;
		$data['ret']=0;
		$data['msg']='['.$result["err_code"].']'.$result["err_code_des"];
		$data['sub_code']=$result["err_code"];
		$data['sub_msg']=$result["err_code_des"];
	} elseif(!empty($result["result_code"])){
		$data['code']=-1;
		$data['msg']='['.$result["err_code"].']'.$result["err_code_des"];
		$data['sub_code']=$result["err_code"];
		$data['sub_msg']=$result["err_code_des"];
	} else {
		$data['code']=-1;
		$data['msg']='未知错误 '.$result["return_msg"];
	}
	return $data;
}

//QQ钱包企业付款
function transferToQQ($channel, $out_trade_no, $payee_account, $payee_real_name, $money){
	global $conf;
	define("IN_PLUGIN", true);
	define("PAY_ROOT", PLUGIN_ROOT.'qqpay/');
	require_once (PAY_ROOT.'inc/qpayMchAPI.class.php');
	//入参
	$params = array();
	$params["input_charset"] = 'UTF-8';
	$params["uin"] = $payee_account;
	$params["out_trade_no"] = $out_trade_no;
	$params["fee_type"] = "CNY";
	$params["total_fee"] = $money*100;
	$params["memo"] = $conf['transfer_desc']; //付款备注
	if(!empty($payee_real_name)){
		$params["check_name"] = 'FORCE_CHECK'; //校验用户姓名，"FORCE_CHECK"校验实名	
	}else{
		$params["check_name"] = 'false'; //校验用户姓名，"FORCE_CHECK"校验实名
	}
	$params["re_user_name"] = $payee_real_name; //收款用户真实姓名
	$params["check_real_name"] = "0"; //校验用户是否实名
	$params["op_user_id"] = QpayMchConf::OP_USERID;
	$params["op_user_passwd"] = md5(QpayMchConf::OP_USERPWD);
	$params["spbill_create_ip"] = $_SERVER['SERVER_ADDR'];

	//api调用
	$qpayApi = new QpayMchAPI('https://api.qpay.qq.com/cgi-bin/epay/qpay_epay_b2c.cgi', true, 10);
	$ret = $qpayApi->reqQpay($params);
	$result = QpayMchUtil::xmlToArray($ret);

	$data = array();
	if ($result['return_code']=='SUCCESS' && $result['result_code']=='SUCCESS') {
		$data['code']=0;
		$data['ret']=1;
		$data['msg']='success';
		$data['orderid']=$result["transaction_id"];
		$data['paydate']=date('Y-m-d H:i:s',time());
	}elseif ($result['err_code']=='TRANSFER_FEE_LIMIT_ERROR' || $result['err_code']=='TRANSFER_FAIL' || $result['err_code']=='NOTENOUGH' || $result['err_code']=='APPID_OR_OPENID_ERR' || $result['err_code']=='TOTAL_FEE_OUT_OF_LIMIT' || $result['err_code']=='REALNAME_CHECK_ERROR' || $result['err_code']=='RE_USER_NAME_CHECK_ERROR') {
		$data['code']=0;
		$data['ret']=0;
		$data['msg']='['.$result["err_code"].']'.$result["err_code_des"];
		$data['sub_code']=$result["err_code"];
		$data['sub_msg']=$result["err_code_des"];
	}elseif(isset($result['result_code'])){
		$data['code']=-1;
		$data['msg']='['.$result["err_code"].']'.$result["err_code_des"];
		$data['sub_code']=$result["err_code"];
		$data['sub_msg']=$result["err_code_des"];
	}else{
		$data['code']=-1;
		$data['msg']='未知错误 '.$result["return_msg"];
	}
	return $data;
}

//单笔转账到银行卡
function transferToBank($channel, $out_trade_no, $payee_account, $payee_real_name, $money){
	global $conf;
	define("IN_PLUGIN", true);
	define("PAY_ROOT", PLUGIN_ROOT.'alipay/');

	require_once PAY_ROOT."inc/AlipayTransferService.php";
	$transfer = new AlipayTransferService($config);
	$result = $transfer->transferToBankCard($out_trade_no, $money, $payee_account, $payee_real_name, $conf['transfer_name']);

	$data = array();
	if(!empty($result['code'])&&$result['code'] == 10000){
		$data['code']=0;
		$data['ret']=1;
		$data['msg']='success';
		$data['orderid']=$result['order_id'];
		$data['paydate']=$result['pay_date']?$result['pay_date']:$result['trans_date'];
	} elseif($result['code'] == 40004) {
		$data['code']=0;
		$data['ret']=0;
		$data['msg']='['.$result['sub_code'].']'.$result['sub_msg'];
		$data['sub_code']=$result['sub_code'];
		$data['sub_msg']=$result['sub_msg'];
	} elseif(!empty($result['code'])){
		$data['code']=-1;
		$data['msg']='['.$result['sub_code'].']'.$result['sub_msg'];
		$data['sub_code']=$result['sub_code'];
		$data['sub_msg']=$result['sub_msg'];
	} else {
		$data['code']=-1;
		$data['msg']='未知错误';
	}
	return $data;
}

function ordername_replace($name,$oldname,$uid,$order){
	global $DB;
	if(strpos($name,'[name]')!==false){
		$name = str_replace('[name]', $oldname, $name);
	}
	if(strpos($name,'[order]')!==false){
		$name = str_replace('[order]', $order, $name);
	}
	if(strpos($name,'[qq]')!==false){
		$qq = $DB->getColumn("SELECT qq FROM pre_user WHERE uid='{$uid}' limit 1");
		$name = str_replace('[qq]', $qq, $name);
	}
	if(strpos($name,'[time]')!==false){
		$name = str_replace('[time]', time(), $name);
	}
	return $name;
}

function is_idcard( $id )
{
	$id = strtoupper($id);
	$regx = "/(^\d{17}([0-9]|X)$)/";
	$arr_split = array();
	if(strlen($id)!=18 || !preg_match($regx, $id))
	{
		return false;
	}
	$regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
	@preg_match($regx, $id, $arr_split);
	$dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
	if(!strtotime($dtm_birth)) //检查生日日期是否正确
	{
		return false;
	}
	else
	{
		//检验18位身份证的校验码是否正确。
		//校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
		$arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
		$arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
		$sign = 0;
		for ( $i = 0; $i < 17; $i++ )
		{
			$b = (int) $id[$i];
			$w = $arr_int[$i];
			$sign += $b * $w;
		}
		$n = $sign % 11;
		$val_num = $arr_ch[$n];
		if ($val_num != substr($id,17, 1))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
}

function checkRefererHost(){
	if(!$_SERVER['HTTP_REFERER'])return false;
	$url_arr = parse_url($_SERVER['HTTP_REFERER']);
	$http_host = $_SERVER['HTTP_HOST'];
	if(strpos($http_host,':'))$http_host = substr($http_host, 0, strpos($http_host, ':'));
	return $url_arr['host'] === $http_host;
}
function randFloat($min=0, $max=1){
	return $min + mt_rand()/mt_getrandmax() * ($max-$min);
}

function check_cert($idcard, $name, $phone){
	global $conf;
	$appcode = $conf['cert_appcode'];
	$url = 'http://phone3.market.alicloudapi.com/phonethree';
	$post = ['idcard'=>$idcard, 'phone'=>$phone, 'realname'=>$name];
	$data = get_curl($url.'?'.http_build_query($post), 0,0,0,0,0,0, ['Authorization: APPCODE '.$appcode, 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);
	$arr=json_decode($data,true);
	if(array_key_exists('code',$arr) && $arr['code']==200){
		return ['code'=>0, 'msg'=>$arr['msg']];
	}elseif(array_key_exists('msg',$arr)){
		return ['code'=>-1, 'msg'=>$arr['msg']];
	}else{
		return ['code'=>-2, 'msg'=>'返回结果解析失败'];
	}
}
function check_corp_cert($companyName, $creditNo, $legalPerson){
	global $conf;
	$appcode = $conf['cert_appcode2'];
	$url = 'http://companythree.shumaidata.com/companythree/check';
	$post = ['companyName'=>$companyName, 'creditNo'=>$creditNo, 'legalPerson'=>$legalPerson];
	$data = get_curl($url.'?'.http_build_query($post), 0, 0,0,0,0,0, ['Authorization: APPCODE '.$appcode, 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);
	$arr=json_decode($data,true);
	if(array_key_exists('code',$arr) && $arr['code']==200){
		if($arr['data']['result']==0){
			return ['code'=>0, 'msg'=>$arr['data']['desc']];
		}else{
			return ['code'=>-1, 'msg'=>$arr['data']['desc']=='不一致'?'公司与法人信息不一致':$arr['data']['desc']];
		}
	}elseif(array_key_exists('msg',$arr)){
		return ['code'=>-1, 'msg'=>$arr['msg']];
	}else{
		return ['code'=>-2, 'msg'=>'返回结果解析失败'];
	}
}
function show_cert_type($certtype){
	if($certtype == 1){
		return '企业实名认证';
	}else{
		return '个人实名认证';
	}
}
function show_cert_method($certmethod){
	if($certmethod == 1){
		return '微信快捷认证';
	}elseif($certmethod == 2){
		return '手机号三要素认证';
	}elseif($certmethod == 3){
		return '人工审核认证';
	}else{
		return '支付宝快捷认证';
	}
}
/**
 * 获取指定用户支付成功率
 *
 * @param $uid
 *
 * @return float|int
 */
function getSuccRateByUser($uid)
{
    global $DB, $conf;

    $minute   = intval($conf['pay_succ_range_minute']);
    $add_time = date('Y-m-d H:i:s', strtotime("-{$minute} minute"));
    $states   = $DB->getAll("SELECT status, COUNT(*) as num FROM `pay_order` WHERE `uid` = '{$uid}' and addtime >= '{$add_time}' GROUP by status");
    $succ     = 0;
    $fail     = 0;
    $total    = 0;
    foreach ($states as $state) {
        $total += $state['num'];
        if ($state['status'] == 1) {
            $succ += $state['num'];

            continue;
        }

        $fail += $state['num'];
    }

    // 如果请求频率低于两秒一个，则认为成功率及格
    if ($total <= $minute * 60 * 0.5) {

        return 100;
    }

    return round($succ / $total, 4) * 100;
}

function randomFloat($min = 0, $max = 1) {
	$num = $min + mt_rand() / mt_getrandmax() * ($max - $min);
	return sprintf("%.2f",$num);
}

function wx_get_access_token($appid, $secret) {
	global $DB;
	$cachekey = 'wxtoken_'.$appid;
	$DB->beginTransaction();
	$data = $DB->getColumn("SELECT v FROM pre_cache WHERE k=:key LIMIT 1 FOR UPDATE", [':key'=>$cachekey]);
	$arr = json_decode($data, true);
	if (!$arr || $arr['expire_time'] < time()) {
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
		$output = get_curl($url);
		$res = json_decode($output, true);
		if ($res['errcode'] == 0 && $res['access_token']) {
			$arr['expire_time'] = time() + $res['expires_in'];
			$arr['access_token'] = $res['access_token'];
			$data = json_encode($arr);
			$DB->exec("REPLACE INTO pre_cache VALUES (:key, :value, :expire)", [':key'=>$cachekey, ':value'=>$data, ':expire'=>0]);
			$access_token = $arr['access_token'];
		}else{
			$DB->commit();
			throw new Exception('AccessToken获取失败：'.$res['errmsg']);
		}
	} else {
		$access_token = $arr['access_token'];
	}
	$DB->commit();
	return $access_token;
}

function wxa_generate_scheme($access_token, $path, $query, $expire = 600){
	$url = "https://api.weixin.qq.com/wxa/generatescheme?access_token=".$access_token;
	$data = ['jump_wxa'=>['path'=>$path, 'query'=>$query]];
	if($expire>0){
		$data['is_expire'] = true;
		$data['expire_time'] = time()+$expire;
	}
	$output = get_curl($url, json_encode($data));
	$res = json_decode($output, true);
	if ($res && $res['errcode'] == 0) {
		return $res['openlink'];
	}else{
		throw new Exception('urlscheme生成失败：'.$res['errmsg']);
	}
}
