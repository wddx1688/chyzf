<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

@header('Content-Type: application/json; charset=UTF-8');

switch($act){
case 'getGroup': //用户组
	$gid=intval($_GET['gid']);
	$row=$DB->getRow("select * from pre_group where gid='$gid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前用户组不存在！"}');
	$result = ['code'=>0,'msg'=>'succ','gid'=>$gid,'name'=>$row['name'],'info'=>json_decode($row['info'],true),'settle_open'=>$row['settle_open'],'settle_type'=>$row['settle_type'],'settings'=>$row['settings']];
	exit(json_encode($result));
break;
case 'delGroup':
	$gid=intval($_GET['gid']);
	$row=$DB->getRow("select * from pre_group where gid='$gid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前用户组不存在！"}');
	$sql = "DELETE FROM pre_group WHERE gid='$gid'";
	if($DB->exec($sql))exit('{"code":0,"msg":"删除用户组成功！"}');
	else exit('{"code":-1,"msg":"删除用户组失败['.$DB->error().']"}');
break;
case 'saveGroup':
	if($_POST['action'] == 'add'){
		$name=trim($_POST['name']);
		$row=$DB->getRow("select * from pre_group where name='$name' limit 1");
		if($row)
			exit('{"code":-1,"msg":"用户组名称重复"}');
		$info=$_POST['info'];
		$info=json_encode($info);
		$settle_open=intval($_POST['settle_open']);
		$settle_type=intval($_POST['settle_type']);
		$settings=trim($_POST['settings']);
		if($settings && !checkGroupSettings($settings))exit('{"code":-1,"msg":"用户变量格式不正确"}');
		$sql = "INSERT INTO pre_group (name, info, settle_open, settle_type, settings) VALUES ('{$name}', '{$info}', '{$settle_open}', '{$settle_type}', '{$settings}')";
		if($DB->exec($sql))exit('{"code":0,"msg":"新增用户组成功！"}');
		else exit('{"code":-1,"msg":"新增用户组失败['.$DB->error().']"}');
	}elseif($_POST['action'] == 'changebuy'){
		$gid=intval($_POST['gid']);
		$status=intval($_POST['status']);
		$sql = "UPDATE pre_group SET isbuy='{$status}' WHERE gid='$gid'";
		if($DB->exec($sql))exit('{"code":0,"msg":"修改上架状态成功！"}');
		else exit('{"code":-1,"msg":"修改上架状态失败['.$DB->error().']"}');
	}else{
		$gid=intval($_POST['gid']);
		$name=trim($_POST['name']);
		$row=$DB->getRow("select * from pre_group where name='$name' and gid<>$gid limit 1");
		if($row)
			exit('{"code":-1,"msg":"用户组名称重复"}');
		$info=$_POST['info'];
		$info=json_encode($info);
		$settle_open=intval($_POST['settle_open']);
		$settle_type=intval($_POST['settle_type']);
		$settings=trim($_POST['settings']);
		if($settings && !checkGroupSettings($settings))exit('{"code":-1,"msg":"用户变量格式不正确"}');
		$sql = "UPDATE pre_group SET name='{$name}',info='{$info}',settle_open='{$settle_open}',settle_type='{$settle_type}',settings='{$settings}' WHERE gid='$gid'";
		if($DB->exec($sql)!==false)exit('{"code":0,"msg":"修改用户组成功！"}');
		else exit('{"code":-1,"msg":"修改用户组失败['.$DB->error().']"}');
	}
break;
case 'saveGroupPrice':
	$prices = $_POST['price'];
	$sorts = $_POST['sort'];
	foreach($prices as $gid=>$item){
		$price = trim($item);
		$sort = trim($sorts[$gid]);
		if(empty($price)||!is_numeric($price))exit('{"code":-1,"msg":"GID:'.$gid.'的售价填写错误"}');
		$DB->exec("UPDATE pre_group SET price='{$price}',sort='{$sort}' WHERE gid='$gid'");
	}
	exit('{"code":0,"msg":"保存成功！"}');
break;
case 'setUser':
	$uid=intval($_GET['uid']);
	$type=trim($_GET['type']);
	$status=intval($_GET['status']);
	if($type=='pay')$sql = "UPDATE pre_user SET pay='$status' WHERE uid='$uid'";
	elseif($type=='settle')$sql = "UPDATE pre_user SET settle='$status' WHERE uid='$uid'";
	elseif($type=='group')$sql = "UPDATE pre_user SET gid='$status' WHERE uid='$uid'";
	else $sql = "UPDATE pre_user SET status='$status' WHERE uid='$uid'";
	if($DB->exec($sql)!==false)exit('{"code":0,"msg":"修改用户成功！"}');
	else exit('{"code":-1,"msg":"修改用户失败['.$DB->error().']"}');
break;
case 'resetUser':
	$uid=intval($_GET['uid']);
	$key = random(32);
	$sql = "UPDATE pre_user SET `key`='$key' WHERE uid='$uid'";
	if($DB->exec($sql)!==false)exit('{"code":0,"msg":"重置密钥成功","key":"'.$key.'"}');
	else exit('{"code":-1,"msg":"重置密钥失败['.$DB->error().']"}');
break;
case 'user_settle_info':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("select * from pre_user where uid='$uid' limit 1");
	if(!$rows)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	$data = '<div class="form-group"><div class="input-group"><div class="input-group-addon">结算方式</div><select class="form-control" id="pay_type" default="'.$rows['settle_id'].'">'.($conf['settle_alipay']?'<option value="1">支付宝</option>':null).''.($conf['settle_wxpay']?'<option value="2">微信</option>':null).''.($conf['settle_qqpay']?'<option value="3">QQ钱包</option>':null).''.($conf['settle_bank']?'<option value="4">银行卡</option>':null).'</select></div></div>';
	$data .= '<div class="form-group"><div class="input-group"><div class="input-group-addon">结算账号</div><input type="text" id="pay_account" value="'.$rows['account'].'" class="form-control" required/></div></div>';
	$data .= '<div class="form-group"><div class="input-group"><div class="input-group-addon">真实姓名</div><input type="text" id="pay_name" value="'.$rows['username'].'" class="form-control" required/></div></div>';
	$data .= '<input type="submit" id="save" onclick="saveInfo('.$uid.')" class="btn btn-primary btn-block" value="保存">';
	$result=array("code"=>0,"msg"=>"succ","data"=>$data,"pay_type"=>$rows['settle_id']);
	exit(json_encode($result));
break;
case 'user_settle_save':
	$uid=intval($_POST['uid']);
	$pay_type=trim(daddslashes($_POST['pay_type']));
	$pay_account=trim(daddslashes($_POST['pay_account']));
	$pay_name=trim(daddslashes($_POST['pay_name']));
	$sds=$DB->exec("update `pre_user` set `settle_id`='$pay_type',`account`='$pay_account',`username`='$pay_name' where `uid`='$uid'");
	if($sds!==false)
		exit('{"code":0,"msg":"修改记录成功！"}');
	else
		exit('{"code":-1,"msg":"修改记录失败！'.$DB->error().'"}');
break;
case 'user_cert':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("select cert,certtype,certmethod,certno,certname,certcorpno,certcorpname,certtime from pre_user where uid='$uid' limit 1");
	if(!$rows)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	$rows['certmethodname'] = show_cert_method($rows['certmethod']);
	$result = ['code'=>0,'msg'=>'succ','uid'=>$uid,'data'=>$rows];
	exit(json_encode($result));
break;
case 'recharge':
	$uid=intval($_POST['uid']);
	$do=$_POST['actdo'];
	$rmb=floatval($_POST['rmb']);
	$row=$DB->getRow("select uid,money from pre_user where uid='$uid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	if($do==1 && $rmb>$row['money'])$rmb=$row['money'];
	if($do==0){
		changeUserMoney($uid, $rmb, true, '后台加款');
	}else{
		changeUserMoney($uid, $rmb, false, '后台扣款');
	}
	exit('{"code":0,"msg":"succ"}');
break;
default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}