<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$submit2=true;
?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>正在为您跳转到支付页面，请稍候...</title>
	<style type="text/css">
body{margin:0;padding:0}
p{position:absolute;left:50%;top:50%;height:35px;margin:-35px 0 0 -160px;padding:20px;font:bold 16px/30px "宋体",Arial;background:#f9fafc url(/assets/img/loading.gif) no-repeat 20px 20px;text-indent:40px;border:1px solid #c5d0dc}
#waiting{font-family:Arial}
	</style>
</head>
<?php
$trade_no=daddslashes($_GET['trade_no']);
$order=$DB->getRow("SELECT * FROM pre_order WHERE trade_no='{$trade_no}' LIMIT 1");
if(!$order)sysmsg('该订单号不存在，请返回来源地重新发起请求！');

$paytype=$DB->getRow("SELECT id,name,status FROM pre_type WHERE id='{$order['type']}' LIMIT 1");
if(!$paytype)sysmsg('支付方式不存在');

$channelrow=$DB->getRow("SELECT id,plugin,apptype FROM pre_channel WHERE id='{$order['channel']}' LIMIT 1");
if(!$channelrow)sysmsg('支付通道不存在');

$order['typename'] = $paytype['name'];

try{
	$result = \lib\Plugin::loadForSubmit($channelrow['plugin'], $trade_no);
	\lib\Payment::echoDefault($result);
}catch(Exception $e){
	sysmsg($e->getMessage());
}
?>
<p>正在为您跳转到支付页面，请稍候...</p>
</body>
</html>