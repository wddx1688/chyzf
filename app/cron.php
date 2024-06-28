<?php
if(preg_match('/Baiduspider/', $_SERVER['HTTP_USER_AGENT']))exit;
$nosession = true;
require './includes/common.php';

if (function_exists("set_time_limit"))
{
	@set_time_limit(0);
}
if (function_exists("ignore_user_abort"))
{
	@ignore_user_abort(true);
}

@header('Content-Type: text/html; charset=UTF-8');

if(empty($conf['cronkey']))exit("请先设置好监控密钥");
if($conf['cronkey']!=$_GET['key'])exit("监控密钥不正确");

if($_GET['do']=='settle'){
	if($conf['settle_open']==1 || $conf['settle_open']==3){
		$settle_time=getSetting('settle_time', true);
		if(strtotime($settle_time)>=strtotime(date("Y-m-d").' 00:00:00'))exit('自动生成结算列表今日已完成');
		$rs=$DB->query("SELECT * from pre_user where money>={$conf['settle_money']} and account is not null and username is not null and settle=1 and status=1");
		$i=0;
		$allmoney=0;
		while($row = $rs->fetch())
		{
			$i++;
			if($conf['settle_rate']>0){
				$fee=round($row['money']*$conf['settle_rate']/100,2);
				if($fee<$conf['settle_fee_min'])$fee=$conf['settle_fee_min'];
				if($fee>$conf['settle_fee_max'])$fee=$conf['settle_fee_max'];
				$realmoney=$row['money']-$fee;
			}else{
				$realmoney=$row['money'];
			}
			if($DB->exec("INSERT INTO `pre_settle` (`uid`, `type`, `username`, `account`, `money`, `realmoney`, `addtime`, `status`) VALUES ('{$row['uid']}', '{$row['settle_id']}', '{$row['username']}', '{$row['account']}', '{$row['money']}', '{$realmoney}', '{$date}', '0')")){
				changeUserMoney($row['uid'], $row['money'], false, '自动结算');
				$allmoney+=$realmoney;
			}
		}
		saveSetting('settle_time', $date);
		exit('自动生成结算列表成功 allmony='.$allmoney.' num='.$i);
	}else{
		exit('自动生成结算列表未开启');
	}
}
elseif($_GET['do']=='order'){
	$order_time=getSetting('order_time', true);
	if(strtotime($order_time)>=strtotime(date("Y-m-d").' 00:00:00'))exit('订单统计与清理任务今日已完成');

	$thtime=date("Y-m-d H:i:s",time()-3600*24);

	$CACHE->clean();
	$DB->exec("delete from pre_order where status=0 and addtime<'{$thtime}'");
	$DB->exec("delete from pre_regcode where `time`<'".(time()-3600*24)."'");

	$day = date("Ymd", strtotime("-1 day"));

	$paytype = [];
	$rs = $DB->getAll("SELECT id,name,showname FROM pre_type WHERE status=1");
	foreach($rs as $row){
		$paytype[$row['id']] = $row['showname'];
	}
	unset($rs);

	$channel = [];
	$rs = $DB->getAll("SELECT id,name FROM pre_channel WHERE status=1");
	foreach($rs as $row){
		$channel[$row['id']] = $row['name'];
	}
	unset($rs);

	$lastday=date("Y-m-d",strtotime("-1 day"));
	$today=date("Y-m-d");

	$rs=$DB->query("SELECT type,channel,money from pre_order where status=1 and date>='$lastday' and date<'$today'");
	foreach($paytype as $id=>$type){
		$order_paytype[$id]=0;
	}
	foreach($channel as $id=>$type){
		$order_channel[$id]=0;
	}
	while($row = $rs->fetch())
	{
		$order_paytype[$row['type']]+=$row['money'];
		$order_channel[$row['channel']]+=$row['money'];
	}
	foreach($order_paytype as $k=>$v){
		$order_paytype[$k] = round($v,2);
	}
	foreach($order_channel as $k=>$v){
		$order_channel[$k] = round($v,2);
	}
	$allmoney=0;
	foreach($order_paytype as $money){
		$allmoney+=$money;
	}

	$order_lastday['all']=round($allmoney,2);
	$order_lastday['paytype']=$order_paytype;
	$order_lastday['channel']=$order_channel;

	$CACHE->save('order_'.$day, serialize($order_lastday));

	saveSetting('order_time', $date);

	$DB->exec("update pre_channel set daystatus=0");
	exit($day.'订单统计与清理任务执行成功');
}
elseif($_GET['do']=='notify'){
	$limit = 20; //每次重试的订单数量
	for($i=0;$i<$limit;$i++){
		$srow=$DB->getRow("SELECT * FROM pre_order WHERE (TO_DAYS(NOW()) - TO_DAYS(endtime) <= 1) AND notify>0 AND notifytime<NOW() LIMIT 1");
		if(!$srow)break;

		//通知时间：1分钟，3分钟，20分钟，1小时，2小时
		$notify = $srow['notify'] + 1;
		if($notify == 2){
			$interval = '2 minute';
		}elseif($notify == 3){
			$interval = '16 minute';
		}elseif($notify == 4){
			$interval = '36 minute';
		}elseif($notify == 5){
			$interval = '1 hour';
		}else{
			$DB->exec("UPDATE pre_order SET notify=-1,notifytime=NULL WHERE trade_no='{$srow['trade_no']}'");
			continue;
		}
		$DB->exec("UPDATE pre_order SET notify={$notify},notifytime=date_add(now(), interval {$interval}) WHERE trade_no='{$srow['trade_no']}'");

		$url=creat_callback($srow);
		if(do_notify($url['notify'])){
			$DB->exec("UPDATE pre_order SET notify=0,notifytime=NULL WHERE trade_no='{$srow['trade_no']}'");
			echo $srow['trade_no'].' 重新通知成功<br/>';
		}else{
			echo $srow['trade_no'].' 重新通知失败（第'.$notify.'次）<br/>';
		}
	}
	echo 'ok!';
}
elseif($_GET['do']=='notify2'){
	$limit = 20; //每次重试的订单数量
	for($i=0;$i<$limit;$i++){
		$srow=$DB->getRow("SELECT * FROM pre_order WHERE (TO_DAYS(NOW()) - TO_DAYS(endtime) <= 1) AND notify=-1 LIMIT 1");
		if(!$srow)break;

		$url=creat_callback($srow);
		if(do_notify($url['notify'])){
			$DB->exec("UPDATE pre_order SET notify=0,notifytime=NULL WHERE trade_no='{$srow['trade_no']}'");
			echo $srow['trade_no'].' 重新通知成功<br/>';
		}else{
			echo $srow['trade_no'].' 重新通知失败<br/>';
		}
	}
	echo 'ok!';
}