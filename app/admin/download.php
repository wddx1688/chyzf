<?php
include("../includes/common.php");

if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$type = isset($_GET['type'])?trim($_GET['type']):null;
$batch=$_GET['batch'];

function display_type($type){
	if($type==1)
		return '支付宝';
	elseif($type==2)
		return '微信';
	elseif($type==3)
		return 'QQ钱包';
	elseif($type==4)
		return '银行卡';
	else
		return 1;
}

$remark = mb_convert_encoding($conf['transfer_desc'], "GB2312", "UTF-8");

if($type == 'alipay'){
	$data='';
	$rs=$DB->query("SELECT * from pre_settle where batch='$batch' and type=1 order by id asc");
	$i=0;
	while($row = $rs->fetch())
	{
		$i++;
		$data.=$i.','.$row['account'].','.mb_convert_encoding($row['username'], "GB2312", "UTF-8").','.$row['realmoney'].','.$remark."\r\n";
	}
	
	$date=date("Ymd");
	$file="支付宝批量付款文件模板\r\n";
	$file.="序号（必填）,收款方支付宝账号（必填）,收款方姓名（必填）,金额（必填，单位：元）,备注（选填）\r\n";
	$file.=$data;
}else{
	$data='';
	$rs=$DB->query("SELECT * from pre_settle where batch='$batch' order by type asc,id asc");
	$i=0;
	while($row = $rs->fetch())
	{
		$i++;
		$data.=$i.','.display_type($row['type']).','.$row['account'].','.mb_convert_encoding($row['username'], "GB2312", "UTF-8").','.$row['realmoney'].','.$remark."\r\n";
	}
	
	$date=date("Ymd");
	$file="商户流水号,收款方式,收款账号,收款人姓名,付款金额（元）,付款理由\r\n";
	$file.=$data;
}

$file_name='pay_'.$batch.'.csv';
$file_size=strlen($file);
header("Content-Description: File Transfer");
header("Content-Type:application/force-download");
header("Content-Length: {$file_size}");
header("Content-Disposition:attachment; filename={$file_name}");
echo $file;
?>