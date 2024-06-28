<?php
$nosession = true;
require './includes/common.php';

$trade_no=isset($_GET['trade_no'])?daddslashes($_GET['trade_no']):exit('No trade_no!');

@header('Content-Type: text/html; charset=UTF-8');

$row=$DB->getRow("SELECT * FROM pre_order WHERE trade_no='{$trade_no}' limit 1");
if($row['status']>=1){
	// 支付完成5分钟后禁止跳转回网站
	if(!empty($row['endtime']) && time() - strtotime($row['endtime']) > 300){
		$jumpurl = '/payok.html';
	}else{
		$url=creat_callback($row);
		$jumpurl = $url['return'];
	}
	echo json_encode(['code'=>1, 'msg'=>'付款成功', 'backurl'=>$jumpurl]);
}else{
	echo json_encode(['code'=>-1, 'msg'=>'未付款']);
}

?>