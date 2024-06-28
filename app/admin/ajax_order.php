<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

@header('Content-Type: application/json; charset=UTF-8');

switch($act){
case 'setStatus': //改变订单状态
	$trade_no=trim($_GET['trade_no']);
	$status=is_numeric($_GET['status'])?intval($_GET['status']):exit('{"code":200}');
	if($status==5){
		if($DB->exec("DELETE FROM pre_order WHERE trade_no='$trade_no'"))
			exit('{"code":200}');
		else
			exit('{"code":400,"msg":"删除订单失败！['.$DB->error().']"}');
	}else{
		if($DB->exec("update pre_order set status='$status' where trade_no='$trade_no'")!==false)
			exit('{"code":200}');
		else
			exit('{"code":400,"msg":"修改订单失败！['.$DB->error().']"}');
	}
break;
case 'order': //订单详情
	$trade_no=trim($_GET['trade_no']);
	$row=$DB->getRow("select A.*,B.showname typename,C.name channelname from pre_order A,pre_type B,pre_channel C where trade_no='$trade_no' and A.type=B.id and A.channel=C.id limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在或未成功选择支付通道！"}');
	$result=array("code"=>0,"msg"=>"succ","data"=>$row);
	exit(json_encode($result));
break;
case 'operation': //批量操作订单
	$status=is_numeric($_POST['status'])?intval($_POST['status']):exit('{"code":-1,"msg":"请选择操作"}');
	$checkbox=$_POST['checkbox'];
	$i=0;
	foreach($checkbox as $trade_no){
		if($status==4)$DB->exec("DELETE FROM pre_order WHERE trade_no='$trade_no'");
		elseif($status==3){
			$row=$DB->getRow("select uid,getmoney,status from pre_order where trade_no='$trade_no' limit 1");
			if($row && $row['status']==3 && $row['getmoney']>0){
				if(changeUserMoney($row['uid'], $row['getmoney'], true, '解冻订单', $trade_no))
					$DB->exec("update pre_order set status='1' where trade_no='$trade_no'");
			}
		}
		elseif($status==2){
			$row=$DB->getRow("select uid,getmoney,status from pre_order where trade_no='$trade_no' limit 1");
			if($row && $row['status']==1 && $row['getmoney']>0){
				if(changeUserMoney($row['uid'], $row['getmoney'], false, '冻结订单', $trade_no))
					$DB->exec("update pre_order set status='3' where trade_no='$trade_no'");
			}
		}
		else $DB->exec("update pre_order set status='$status' where trade_no='$trade_no' limit 1");
		$i++;
	}
	exit('{"code":0,"msg":"成功改变'.$i.'条订单状态"}');
break;
case 'getmoney': //退款查询
	if(!$conf['admin_paypwd'])exit('{"code":-1,"msg":"你还未设置支付密码"}');
	$trade_no=trim($_POST['trade_no']);
	$api=isset($_POST['api'])?intval($_POST['api']):0;
	$row=$DB->getRow("select * from pre_order where trade_no='$trade_no' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	if($row['status']!=1)
		exit('{"code":-1,"msg":"只支持退款已支付状态的订单"}');
	if($api==1){
		if(!$row['api_trade_no'])
			exit('{"code":-1,"msg":"接口订单号不存在"}');
		$channel = \lib\Channel::get($row['channel']);
		if(!$channel){
			exit('{"code":-1,"msg":"当前支付通道信息不存在"}');
		}
		if(\lib\Plugin::isrefund($channel['plugin'])==false){
			exit('{"code":-1,"msg":"当前支付通道不支持API退款"}');
		}
		$money = $row['money'];
	}else{
		$money = $row['money'];
	}
	exit('{"code":0,"money":"'.$money.'"}');
break;
case 'refund': //退款操作
	$trade_no=trim($_POST['trade_no']);
	$money = trim($_POST['money']);
	if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))exit('{"code":-1,"msg":"金额输入错误"}');
	$row=$DB->getRow("select uid,money,getmoney,status from pre_order where trade_no='$trade_no' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	if($row['status']!=1)
		exit('{"code":-1,"msg":"只支持退款已支付状态的订单"}');
	if($money>$row['money'])exit('{"code":-1,"msg":"退款金额不能大于订单金额"}');
	if($money==$row['money'] || $money>=$row['getmoney']){
		$refundmoney = $money;
		$reducemoney = $row['getmoney'];
	}else{
		$refundmoney = $money;
		$reducemoney = $money;
	}
	if($reducemoney>0){
		changeUserMoney($row['uid'], $reducemoney, false, '订单退款', $trade_no);
		$DB->exec("update pre_order set status='2' where trade_no='$trade_no'");
	}
	exit('{"code":0,"msg":"已成功从UID:'.$row['uid'].'扣除'.$reducemoney.'元余额"}');
break;
case 'apirefund': //API退款操作
	$trade_no=trim($_POST['trade_no']);
	$paypwd=trim($_POST['paypwd']);
	$money = trim($_POST['money']);
	if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))exit('{"code":-1,"msg":"金额输入错误"}');
	if($paypwd!=$conf['admin_paypwd'])
		exit('{"code":-1,"msg":"支付密码输入错误！"}');
	$row=$DB->getRow("select uid,money,getmoney,status from pre_order where trade_no='$trade_no' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	if($row['status']!=1)
		exit('{"code":-1,"msg":"只支持退款已支付状态的订单"}');
	if($money>$row['money'])exit('{"code":-1,"msg":"退款金额不能大于订单金额"}');
	if($money==$row['money'] || $money>=$row['getmoney']){
		$refundmoney = $money;
		$reducemoney = $row['getmoney'];
	}else{
		$refundmoney = $money;
		$reducemoney = $money;
	}
	$message = null;
	if(\lib\Plugin::refund($trade_no, $refundmoney, $message)){
		if($reducemoney>0){
			if(changeUserMoney($row['uid'], $reducemoney, false, '订单退款', $trade_no)){
				$addstr = '，并成功从UID:'.$row['uid'].'扣除'.$reducemoney.'元余额';
			}
			$DB->exec("update pre_order set status='2' where trade_no='$trade_no'");
		}
		exit('{"code":0,"msg":"API退款成功！退款金额￥'.$refundmoney.$addstr.'"}');
	}else{
		exit('{"code":-1,"msg":"API退款失败：'.$message.'"}');
	}
break;
case 'freeze': //冻结订单
	$trade_no=trim($_POST['trade_no']);
	$row=$DB->getRow("select uid,getmoney,status from pre_order where trade_no='$trade_no' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	if($row['status']!=1)
		exit('{"code":-1,"msg":"只支持冻结已支付状态的订单"}');
	if($row['getmoney']>0){
		changeUserMoney($row['uid'], $row['getmoney'], false, '订单冻结', $trade_no);
		$DB->exec("update pre_order set status='3' where trade_no='$trade_no'");
	}
	exit('{"code":0,"msg":"已成功从UID:'.$row['uid'].'冻结'.$row['getmoney'].'元余额"}');
break;
case 'unfreeze': //解冻订单
	$trade_no=trim($_POST['trade_no']);
	$row=$DB->getRow("select uid,getmoney,status from pre_order where trade_no='$trade_no' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	if($row['status']!=3)
		exit('{"code":-1,"msg":"只支持解冻已冻结状态的订单"}');
	if($row['getmoney']>0){
		changeUserMoney($row['uid'], $row['getmoney'], true, '订单解冻', $trade_no);
		$DB->exec("update pre_order set status='1' where trade_no='$trade_no'");
	}
	exit('{"code":0,"msg":"已成功为UID:'.$row['uid'].'恢复'.$row['getmoney'].'元余额"}');
break;
case 'notify': //获取回调地址
	$trade_no=trim($_POST['trade_no']);
	$row=$DB->getRow("select * from pre_order where trade_no='$trade_no' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	$url=creat_callback($row);
	if($row['notify']>0)
		$DB->exec("update pre_order set notify=0,notifytime=NULL where trade_no='$trade_no'");
	exit('{"code":0,"url":"'.($_POST['isreturn']==1?$url['return']:$url['notify']).'"}');
break;
case 'fillorder': //手动补单
	$trade_no=trim($_POST['trade_no']);
	$row=$DB->getRow("select * from pre_order where trade_no='$trade_no' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	if($row['status']>0)exit('{"code":-1,"msg":"当前订单不是未完成状态！"}');
	if($DB->exec("update `pre_order` set `status` ='1' where `trade_no`='$trade_no'")){
		$DB->exec("update `pre_order` set `endtime` ='$date',`date` =NOW() where `trade_no`='$trade_no'");
		$channel=\lib\Channel::get($row['channel']);
		processOrder($row);
	}
	exit('{"code":0,"msg":"补单成功"}');
break;
default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}