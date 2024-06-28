<?php
if(isset($_GET['pid'])){
	$queryArr=$_GET;
}elseif(isset($_POST['pid'])){
	$queryArr=$_POST;
}else{
	exit('{"code":-4}');
}
$nosession = true;
require './includes/common.php';

@header('Content-Type: application/json; charset=UTF-8');

function echojson($msg){
	exit(json_encode(['code'=>-1, 'msg'=>$msg]));
}

use \lib\PayUtils;
$prestr=PayUtils::createLinkstring(PayUtils::argSort(PayUtils::paraFilter($queryArr)));
$pid=intval($queryArr['pid']);
if(empty($pid))echojson('PID不存在');
$userrow=$DB->query("SELECT `uid`,`gid`,`key`,`money`,`mode`,`pay`,`cert`,`status`,`channelinfo`,`qq`,`ordername` FROM `pre_user` WHERE `uid`='{$pid}' LIMIT 1")->fetch();
if(!$userrow)echojson('商户不存在！');
if(!PayUtils::md5Verify($prestr, $queryArr['sign'], $userrow['key']))echojson('签名校验失败，请返回重试！');

if($userrow['status']==0 || $userrow['pay']==0)echojson('商户已封禁，无法支付！');

if($userrow['pay']==2 && $conf['user_review']==1)echojson('商户没通过审核，请联系官方客服进行审核');

$type=daddslashes($queryArr['type']);
$out_trade_no=daddslashes($queryArr['out_trade_no']);
$notify_url=htmlspecialchars(daddslashes($queryArr['notify_url']));
$return_url=htmlspecialchars(daddslashes($queryArr['return_url']));
$name=htmlspecialchars(daddslashes($queryArr['name']));
$money=daddslashes($queryArr['money']);
$clientip=daddslashes($queryArr['clientip']);
$device=daddslashes($queryArr['device']);
if(empty($device))$device = 'pc';
$sitename=urlencode(base64_encode(htmlspecialchars($queryArr['sitename'])));
$param=isset($queryArr['param'])?htmlspecialchars(daddslashes($queryArr['param'])):null;
$mdevice='';
if ($device=='qq'||$device=='wechat'||$device=='alipay') {
	$mdevice=$device;
    $device='mobile';
}

if(empty($out_trade_no))echojson('订单号(out_trade_no)不能为空');
if(empty($notify_url))echojson('通知地址(notify_url)不能为空');
if(empty($name))echojson('商品名称(name)不能为空');
if(empty($money))echojson('金额(money)不能为空');
if(empty($type))echojson('支付方式(type)不能为空');
if(empty($clientip))echojson('用户IP地址(clientip)不能为空');
if($money<=0 || !is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))echojson('金额不合法');
if($conf['pay_maxmoney']>0 && $money>$conf['pay_maxmoney'])echojson('最大支付金额是'.$conf['pay_maxmoney'].'元');
if($conf['pay_minmoney']>0 && $money<$conf['pay_minmoney'])echojson('最小支付金额是'.$conf['pay_minmoney'].'元');
if(!preg_match('/^[a-zA-Z0-9.\_\-|]+$/',$out_trade_no))echojson('订单号(out_trade_no)格式不正确');

$domain=getdomain($notify_url);

if(!empty($conf['blockname'])){
	$block_name = explode('|',$conf['blockname']);
	foreach($block_name as $rows){
		if(!empty($rows) && strpos($name,$rows)!==false){
			$DB->exec("INSERT INTO `pre_risk` (`uid`, `url`, `content`, `date`) VALUES (:uid, :domain, :rows, NOW())", [':uid'=>$pid,':domain'=>$domain,':rows'=>$rows]);
			echojson($conf['blockalert']?$conf['blockalert']:'该商品禁止出售');
		}
	}
}
if($conf['cert_force']==1 && $userrow['cert']==0){
	echojson('当前商户未完成实名认证，无法收款');
}
if($conf['forceqq']==1 && empty($userrow['qq'])){
	echojson('当前商户未填写联系QQ，无法收款');
}

if($conf['blockips']){
	$blockips = explode('|',$conf['blockips']);
	if(in_array($clientip, $blockips))echojson('系统异常无法完成付款');
}

if(strlen($name)>127)$name=mb_strcut($name, 0, 127, 'utf-8');

$oldorder = $DB->getRow("SELECT * FROM `pre_order` WHERE `uid`=:uid AND `out_trade_no`=:out_trade_no", [':uid'=>$pid, ':out_trade_no'=>$out_trade_no]);
if($oldorder && time() - strtotime($oldorder['addtime']) < 864000){
	if($oldorder['status']>0){
		echojson('该订单('.$out_trade_no.')已完成支付，请勿重复发起支付');
	}
	if(round($oldorder['money'],2) != round($money,2) || $oldorder['name'] != $name || $oldorder['notify_url'] != $notify_url || $oldorder['return_url'] != $return_url || $oldorder['param'] != $param){
		echojson('该订单('.$out_trade_no.')支付参数有变化，请更换订单号重新发起支付');
	}
	$trade_no=$oldorder['trade_no'];
}else{
	$trade_no=date("YmdHis").rand(11111,99999);
	if(!$DB->exec("INSERT INTO `pre_order` (`trade_no`,`out_trade_no`,`uid`,`addtime`,`name`,`money`,`notify_url`,`return_url`,`param`,`domain`,`ip`,`status`) VALUES (:trade_no, :out_trade_no, :uid, NOW(), :name, :money, :notify_url, :return_url, :param, :domain, :clientip, 0)", [':trade_no'=>$trade_no, ':out_trade_no'=>$out_trade_no, ':uid'=>$pid, ':name'=>$name, ':money'=>$money, ':notify_url'=>$notify_url, ':return_url'=>$return_url, ':domain'=>$domain, ':clientip'=>$clientip, ':param'=>$param]))echojson('创建订单失败，请返回重试！');
}

// 获取订单支付方式ID、支付插件、支付通道、支付费率
$submitData = \lib\Channel::submit($type, $userrow['gid'], $money, $device);

// 判断通道单笔支付限额
if(!empty($submitData['paymin']) && $submitData['paymin']>0 && $money<$submitData['paymin']){
	echojson('当前支付方式单笔最小限额为'.$submitData['paymin'].'元，请选择其他支付方式！');
}
if(!empty($submitData['paymax']) && $submitData['paymax']>0 && $money>$submitData['paymax']){
	echojson('当前支付方式单笔最大限额为'.$submitData['paymax'].'元，请选择其他支付方式！');
}

if($submitData){
	if($userrow['mode']==1){
		$realmoney = round($money*(100+100-$submitData['rate'])/100,2);
		$getmoney = $money;
	}else{
		$realmoney = $money;
		$getmoney = round($money*$submitData['rate']/100,2);
	}
	if($submitData['mode']==1 && $realmoney-$getmoney>$userrow['money']){
		echojson('当前商户余额不足，无法完成支付，请商户登录用户中心充值余额');
	}
	$DB->exec("UPDATE pre_order SET type='{$submitData['typeid']}',channel='{$submitData['channel']}',realmoney='$realmoney',getmoney='$getmoney' WHERE trade_no='$trade_no'");
}else{ //选择其他支付方式
	exit(json_encode(['code'=>1, 'payurl'=>'./cashier.php?trade_no='.$trade_no.'&sitename='.$sitename]));
}

$order['trade_no'] = $trade_no;
$order['out_trade_no'] = $out_trade_no;
$order['uid'] = $pid;
$order['addtime'] = $date;
$order['name'] = $name;
$order['realmoney'] = $realmoney;
$order['type'] = $submitData['typeid'];
$order['channel'] = $submitData['channel'];
$order['typename'] = $submitData['typename'];

try{
	$result = \lib\Plugin::loadForSubmit($submitData['plugin'], $trade_no, true);
	\lib\Payment::echoJson($result);
}catch(Exception $e){
	echojson($e->getMessage());
}
