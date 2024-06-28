<?php
$nosession = true;
require './includes/common.php';
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;
$url=daddslashes($_GET['url']);
$authcode=daddslashes($_GET['authcode']);


if($act=='query')
{
	$pid=intval($_GET['pid']);
	$key=daddslashes($_GET['key']);
	$row=$DB->getRow("SELECT * FROM pre_user WHERE uid='{$pid}' limit 1");
	if($row){
		if($key==$row['key']){
			$orders=$DB->getColumn("SELECT count(*) from pre_order WHERE uid={$pid}");

			$lastday=date("Y-m-d",strtotime("-1 day")).' 00:00:00';
			$today=date("Y-m-d").' 00:00:00';
			$order_today=$DB->query("SELECT sum(money) from pre_order where uid={$pid} and status=1 and endtime>='$today'")->fetchColumn();

			$order_lastday=$DB->query("SELECT sum(money) from pre_order where uid={$pid} and status=1 and endtime>='$lastday' and endtime<'$today'")->fetchColumn();

			//$settle_money=$DB->query("SELECT sum(money) from pre_settle where uid={$pid} and status=1")->fetchColumn();

			$result=array("code"=>1,"pid"=>$pid,"key"=>$key,"active"=>$row['status'],"money"=>$row['money'],"type"=>$row['settle_id'],"account"=>$row['account'],"username"=>$row['username'],"orders"=>$orders,"order_today"=>$order_today,"order_lastday"=>$order_lastday);
		}else{
			$result=array("code"=>-2,"msg"=>"KEY校验失败");
		}
	}else{
		$result=array("code"=>-3,"msg"=>"PID不存在");
	}
}
elseif($act=='settle')
{
	$pid=intval($_GET['pid']);
	$key=daddslashes($_GET['key']);
	$limit=$_GET['limit']?intval($_GET['limit']):10;
	if($limit>50)$limit=50;
	$row=$DB->query("SELECT * FROM pre_user WHERE uid='{$pid}' limit 1")->fetch();
	if($row){
		if($key==$row['key']){
			$rs=$DB->query("SELECT * FROM pre_settle WHERE uid='{$pid}' order by id desc limit {$limit}");
			while($row=$rs->fetch()){
				$data[]=$row;
			}
			if($rs){
				$result=array("code"=>1,"msg"=>"查询结算记录成功！","pid"=>$pid,"key"=>$key,"type"=>$type,"data"=>$data);
			}else{
				$result=array("code"=>-1,"msg"=>"查询结算记录失败！");
			}
		}else{
			$result=array("code"=>-2,"msg"=>"KEY校验失败");
		}
	}else{
		$result=array("code"=>-3,"msg"=>"PID不存在");
	}
}
elseif($act=='order')
{
	$pid=intval($_GET['pid']);
	$key=daddslashes($_GET['key']);
	$row=$DB->query("SELECT * FROM pre_user WHERE uid='{$pid}' limit 1")->fetch();
	if($row){
		if($key==$row['key']){
			if(isset($_GET['trade_no'])){
				$trade_no=daddslashes($_GET['trade_no']);
				$row=$DB->query("SELECT * FROM pre_order WHERE uid='{$pid}' and trade_no='{$trade_no}' limit 1")->fetch();
			}elseif(isset($_GET['out_trade_no'])){
				$out_trade_no=daddslashes($_GET['out_trade_no']);
				$row=$DB->query("SELECT * FROM pre_order WHERE uid='{$pid}' and out_trade_no='{$out_trade_no}' limit 1")->fetch();
			}else{
				exit('{"code":-4,"msg":"参数不完整"}');
			}
			if($row){
				$type=$DB->getColumn("SELECT name FROM pre_type WHERE id='{$row['type']}' LIMIT 1");
				$result=array("code"=>1,"msg"=>"查询订单号成功！","trade_no"=>$row['trade_no'],"out_trade_no"=>$row['out_trade_no'],"type"=>$type,"pid"=>$row['uid'],"addtime"=>$row['addtime'],"endtime"=>$row['endtime'],"name"=>$row['name'],"money"=>$row['money'],"param"=>$row['param'],"buyer"=>$row['buyer'],"status"=>$row['status']);
			}else{
				$result=array("code"=>-1,"msg"=>"订单号不存在");
			}
		}else{
			$result=array("code"=>-2,"msg"=>"KEY校验失败");
		}
	}else{
		$result=array("code"=>-3,"msg"=>"PID不存在");
	}
}
elseif($act=='orders')
{
	$pid=intval($_GET['pid']);
	$key=daddslashes($_GET['key']);
	$limit=$_GET['limit']?intval($_GET['limit']):10;
	if($limit>50)$limit=50;
	$row=$DB->query("SELECT * FROM pre_user WHERE uid='{$pid}' limit 1")->fetch();
	if($row){
		if($key==$row['key']){
			$rs=$DB->query("SELECT A.*,B.name typename FROM pre_order A LEFT JOIN pre_type B ON A.type=B.id WHERE uid='{$pid}' ORDER BY trade_no DESC LIMIT {$limit}");
			while($row=$rs->fetch()){
				$data[]=["trade_no"=>$row['trade_no'],"out_trade_no"=>$row['out_trade_no'],"type"=>$row['typename'],"pid"=>$row['uid'],"addtime"=>$row['addtime'],"endtime"=>$row['endtime'],"name"=>$row['name'],"money"=>$row['money'],"param"=>$row['param'],"buyer"=>$row['buyer'],"status"=>$row['status']];
			}
			if($rs){
				$result=array("code"=>1,"msg"=>"查询订单记录成功！","count"=>count($data),"data"=>$data);
			}else{
				$result=array("code"=>-1,"msg"=>"查询订单记录失败！");
			}
		}else{
			$result=array("code"=>-2,"msg"=>"KEY校验失败");
		}
	}else{
		$result=array("code"=>-3,"msg"=>"PID不存在");
	}
}
else
{
	$result=array("code"=>-5,"msg"=>"No Act!");
}

echo json_encode($result);

?>