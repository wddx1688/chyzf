<?php
include("../includes/common.php");

if($conf['cert_open'] == 1){ //支付宝身份验证
	$uid = authcode($_GET['state'], 'DECODE', SYS_KEY);
	if(!$uid || $uid<=0)sysmsg('state校验失败');
	$uid = intval($uid);

	$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid='{$uid}' limit 1");
	if(!$userrow)sysmsg('uid不存在');
	$certtoken = $userrow['certtoken'];

	$channel = \lib\Channel::get($conf['cert_channel']);
	if(!$channel)sysmsg('当前实名认证通道信息不存在');
	define("IN_PLUGIN", true);
	define("PAY_ROOT", PLUGIN_ROOT.'alipay/');
	require_once PAY_ROOT."inc/AlipayCertifyService.php";
	$certify = new AlipayCertifyService($config);
	$certifyResult = $certify->query($certtoken);
	if(isset($certifyResult['passed'])){
		if($certifyResult['passed'] == 'T'){
			if($DB->exec("update `pre_user` set `cert`=1 where `uid`='$uid'")){
				$DB->exec("update `pre_user` set `certtime`='$date' where `uid`='$uid'");
				if($conf['cert_money']>0){
					changeUserMoney($uid, $conf['cert_money'], false, '实名认证');
				}
			}
		}else{
			sysmsg('<center>实名认证未通过（'.$certifyResult['fail_reason'] .'）</center>');
		}
	}else{
		sysmsg('支付宝接口返回异常['.$certifyResult['sub_code'].']'.$certifyResult['sub_msg']);
	}
}elseif($conf['cert_open'] == 4){ //微信扫码实名认证
	$uid = intval($_GET['state']);
	$AuthToken = isset($_GET['AuthToken'])?$_GET['AuthToken']:exit('param is error');
	$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid='{$uid}' limit 1");
	if(!$userrow)sysmsg('uid不存在');
	if($AuthToken!=$userrow['certtoken'])sysmsg('AuthToken不正确');

	$qcloud = new \lib\QcloudFaceid($conf['cert_qcloudid'], $conf['cert_qcloudkey']);
	$result = $qcloud->GetRealNameAuthResult($AuthToken);
	if(isset($result['ResultType'])){
		if($result['ResultType'] == '0'){
			if($DB->exec("update `pre_user` set `cert`=1 where `uid`='$uid'")){
				$DB->exec("update `pre_user` set `certtime`='$date' where `uid`='$uid'");
				if($conf['cert_money']>0){
					changeUserMoney($uid, $conf['cert_money'], false, '实名认证');
				}
			}
		}else{
			$msg = '实名认证未通过';
			if($result['ResultType'] == '-1'){
				$msg .= '：姓名和身份证号不一致';
			}elseif($result['ResultType'] == '-2'){
				$msg .= '：姓名和微信实名姓名不一致';
			}elseif($result['ResultType'] == '-3'){
				$msg .= '：微信号未实名';
			}else{
				$msg .= '（ResultType='.$result['ResultType'].'）';
			}
			sysmsg('<center>'.$msg.'</center>');
		}
	}else{
		sysmsg('接口返回异常['.$result['Error']['Code'].']'.$result['Error']['Message']);
	}
}elseif($conf['cert_open'] == 5){ //阿里云金融级实人认证
	$uid = authcode($_GET['state'], 'DECODE', SYS_KEY);
	if(!$uid || $uid<=0)sysmsg('state校验失败');
	$uid = intval($uid);

	$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid='{$uid}' limit 1");
	if(!$userrow)sysmsg('uid不存在');
	$certtoken = $userrow['certtoken'];

	$aliyun = new \lib\AliyunCertify($conf['cert_aliyunid'], $conf['cert_aliyunkey'], $conf['cert_aliyunsceneid']);
	$result = $aliyun->query($certtoken);
    if (isset($result['Code']) && $result['Code']==200) {
		if($result['Data']['passed'] == 'T'){
			if($DB->exec("update `pre_user` set `cert`=1 where `uid`='$uid'")){
				$DB->exec("update `pre_user` set `certtime`='$date' where `uid`='$uid'");
				if($conf['cert_money']>0){
					changeUserMoney($uid, $conf['cert_money'], false, '实名认证');
				}
			}
		}else{
			sysmsg('<center>实名认证未通过（'.$result['Data']['fail_reason'].'）</center>');
		}
    }else{
		sysmsg('接口返回异常['.$result['Code'].']'.$result['Message']);
	}
}
if($islogin2==1){
	exit("<script language='javascript'>window.location.href='./certificate.php';</script>");
}
@header('Content-Type: text/html; charset=UTF-8');
include PAYPAGE_ROOT.'certok.php';
