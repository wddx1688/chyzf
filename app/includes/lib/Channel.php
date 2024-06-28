<?php
namespace lib;

class Channel {

	static public function get($id, $channelinfo=null){
		global $DB;
		$value=$DB->getRow("SELECT * FROM pre_channel WHERE id='$id' LIMIT 1");
		if(!empty($channelinfo)){
			$arr = json_decode($channelinfo, true);
			if($value['appid'] && substr($value['appid'],0,1)=='['){
				$key = substr($value['appid'],1,-1);
				$value['appid'] = $arr[$key];
			}
			if($value['appkey'] && substr($value['appkey'],0,1)=='['){
				$key = substr($value['appkey'],1,-1);
				$value['appkey'] = $arr[$key];
			}
			if($value['appsecret'] && substr($value['appsecret'],0,1)=='['){
				$key = substr($value['appsecret'],1,-1);
				$value['appsecret'] = $arr[$key];
			}
			if($value['appurl'] && substr($value['appurl'],0,1)=='['){
				$key = substr($value['appurl'],1,-1);
				$value['appurl'] = $arr[$key];
			}
			if($value['appmchid'] && substr($value['appmchid'],0,1)=='['){
				$key = substr($value['appmchid'],1,-1);
				$value['appmchid'] = $arr[$key];
			}
		}
		return $value;
	}

	static public function getWeixin($id){
		global $DB;
		$value=$DB->getRow("SELECT * FROM pre_weixin WHERE id='$id' LIMIT 1");
		return $value;
	}

	// 支付提交处理（输入支付方式名称）
	static public function submit($type, $gid=0, $money=0, $device=null){
		global $DB;
		if($device == 'mobile' || $device == 'qq' || $device == 'wechat' || $device == 'alipay' || checkmobile()==true){
			$sqls = " AND (device=0 OR device=2)";
		}else{
			$sqls = " AND (device=0 OR device=1)";
		}
		$paytype=$DB->getRow("SELECT id,name,status FROM pre_type WHERE name='$type'{$sqls} LIMIT 1");
		if(!$paytype || $paytype['status']==0)sysmsg('支付方式(type)不存在');
		$typeid = $paytype['id'];
		$typename = $paytype['name'];

		return self::getSubmitInfo($typeid, $typename, $gid, $money);
	}

	// 支付提交处理2（输入支付方式ID）
	static public function submit2($typeid, $gid=0, $money=0){
		global $DB;
		$paytype=$DB->getRow("SELECT id,name,status FROM pre_type WHERE id='$typeid' LIMIT 1");
		if(!$paytype || $paytype['status']==0)sysmsg('支付方式(type)不存在');
		$typename = $paytype['name'];

		return self::getSubmitInfo($typeid, $typename, $gid, $money);
	}

	//获取通道、插件、费率信息
	static public function getSubmitInfo($typeid, $typename, $gid, $money){
		global $DB;
		if($gid>0)$groupinfo=$DB->getColumn("SELECT info FROM pre_group WHERE gid='$gid' LIMIT 1");
		if(!$groupinfo)$groupinfo=$DB->getColumn("SELECT info FROM pre_group WHERE gid=0 LIMIT 1");
		if($groupinfo){
			$info = json_decode($groupinfo,true);
			$groupinfo = $info[$typeid];
			if(is_array($groupinfo)){
				$channel = $groupinfo['channel'];
				$money_rate = $groupinfo['rate'];
			}
			else{
				$channel = -1;
				$money_rate = null;
			}
			if($channel==0){ //当前商户关闭该通道
				return false;
			}
			elseif($channel==-1){ //随机可用通道
				$rows=$DB->getAll("SELECT id,plugin,status,rate,apptype,mode,paymin,paymax FROM pre_channel WHERE type='$typeid' AND status=1 AND daystatus=0");
				if(count($rows)>0){
					$newrows = [];
					foreach($rows as $row){
						if($money>0 && !empty($row['paymin']) && $row['paymin']>0 && $money<$row['paymin'])continue;
						if($money>0 && !empty($row['paymax']) && $row['paymax']>0 && $money>$row['paymax'])continue;
						$newrows[] = $row;
					}
					if(count($newrows)>0){
						$row = $newrows[array_rand($newrows)];
					}else{
						$row = $rows[array_rand($rows)];
					}
					$channel = $row['id'];
					$plugin = $row['plugin'];
					$apptype = $row['apptype'];
					$mode = $row['mode'];
					$paymin = $row['paymin'];
					$paymax = $row['paymax'];
					if(empty($money_rate))$money_rate = $row['rate'];
				}
			}
			else{
				if($groupinfo['type']=='roll'){ //解析轮询组
					$channel = self::getChannelFromRoll($channel, $money);
					if(!$channel || $channel==0){ //当前轮询组未开启
						return false;
					}
				}
				//获取轮询组对应通道
				$row=$DB->getRow("SELECT plugin,status,rate,apptype,mode,paymin,paymax FROM pre_channel WHERE id='$channel' LIMIT 1");
				if($row['status']==1){
					$plugin = $row['plugin'];
					$apptype = $row['apptype'];
					$mode = $row['mode'];
					$paymin = $row['paymin'];
					$paymax = $row['paymax'];
					if(empty($money_rate))$money_rate = $row['rate'];
				}
			}
		}else{
			//未设置用户组
			$row=$DB->getRow("SELECT id,plugin,status,rate,apptype,mode,paymin,paymax FROM pre_channel WHERE type='$typeid' AND status=1 AND daystatus=0 ORDER BY rand() LIMIT 1");
			if($row){
				$channel = $row['id'];
				$plugin = $row['plugin'];
				$apptype = $row['apptype'];
				$mode = $row['mode'];
				$money_rate = $row['rate'];
				$paymin = $row['paymin'];
				$paymax = $row['paymax'];
			}
		}
		if(!$plugin || !$channel){ //通道已关闭
			return false;
		}
		return ['typeid'=>$typeid, 'typename'=>$typename, 'plugin'=>$plugin, 'channel'=>$channel, 'rate'=>$money_rate, 'apptype'=>$apptype, 'mode'=>$mode, 'paymin'=>$paymin, 'paymax'=>$paymax];
	}

	// 获取当前商户可用支付方式
	static public function getTypes($gid=0){
		global $DB;
		if(checkmobile()==true){
			$sqls = " AND (device=0 OR device=2)";
		}else{
			$sqls = " AND (device=0 OR device=1)";
		}
		$rows = $DB->getAll("SELECT * FROM pre_type WHERE status=1{$sqls} ORDER BY id ASC");
		$paytype = [];
		foreach($rows as $row){
			$paytype[$row['id']] = $row;
		}
		if($gid>0)$groupinfo=$DB->getColumn("SELECT info FROM pre_group WHERE gid='$gid' LIMIT 1");
		if(!$groupinfo)$groupinfo=$DB->getColumn("SELECT info FROM pre_group WHERE gid=0 LIMIT 1");
		if($groupinfo){
			$info = json_decode($groupinfo,true);
			foreach($info as $id=>$row){
				if(!isset($paytype[$id]))continue;
				if($row['channel']==0){
					unset($paytype[$id]);
				}elseif($row['channel']==-1){
					$status=$DB->getColumn("SELECT status FROM pre_channel WHERE type='$id' AND status=1 LIMIT 1");
					if(!$status || $status==0){
						unset($paytype[$id]);
					}elseif(empty($row['rate'])){
						$paytype[$id]['rate']=$DB->getColumn("SELECT rate FROM pre_channel WHERE type='$id' AND status=1 LIMIT 1");
					}else{
						$paytype[$id]['rate']=$row['rate'];
					}
				}else{
					if($row['type']=='roll'){
						$status=$DB->getColumn("SELECT status FROM pre_roll WHERE id='{$row['channel']}' LIMIT 1");
					}else{
						$status=$DB->getColumn("SELECT status FROM pre_channel WHERE id='{$row['channel']}' LIMIT 1");
					}
					if(!$status || $status==0)unset($paytype[$id]);
					else $paytype[$id]['rate']=$row['rate'];
				}
			}
		}else{
			foreach($paytype as $id=>$row){
				$status=$DB->getColumn("SELECT status FROM pre_channel WHERE type='$id' AND status=1 limit 1");
				if(!$status || $status==0)unset($paytype[$id]);
				else{
					$paytype[$id]['rate']=$DB->getColumn("SELECT rate FROM pre_channel WHERE type='$id' AND status=1 limit 1");
				}
			}
		}
		return $paytype;
	}

	//根据轮询组ID获取支付通道ID
	static private function getChannelFromRoll($channel, $money){
		global $DB;
		$row=$DB->getRow("SELECT * FROM pre_roll WHERE id='$channel' LIMIT 1");
		if($row['status']==1){
			$info = self::rollinfo_decode($row['info'],true);

			//先根据支付金额与限额过滤可用支付通道
			$channelids = [];
			foreach($info as $inforow){
				$channelids[] = $inforow['name'];
			}
			$channelids = implode(',',$channelids);
			$rows=$DB->getAll("SELECT id,paymin,paymax FROM pre_channel WHERE id IN ($channelids) AND status=1 AND daystatus=0");
			$newids = [];
			foreach($rows as $channelrow){
				if($money>0 && !empty($channelrow['paymin']) && $channelrow['paymin']>0 && $money<$channelrow['paymin'])continue;
				if($money>0 && !empty($channelrow['paymax']) && $channelrow['paymax']>0 && $money>$channelrow['paymax'])continue;
				$newids[] = $channelrow['id'];
			}
			if(count($newids)==0)return false;
			
			$newinfo = [];
			foreach($info as $inforow){
				if(in_array($inforow['name'], $newids))$newinfo[]=$inforow;
			}

			if($row['kind']==1){
				$channel = self::random_weight($newinfo);
			}else{
				$channel = $newinfo[$row['index']]['name'];
				$index = ($row['index'] + 1) % count($newinfo);
				$DB->exec("UPDATE pre_roll SET `index`='$index' WHERE id='{$row['id']}'");
			}
			return $channel;
		}
		return false;
	}

	//解析轮询组info
	static private function rollinfo_decode($content){
		$result = [];
		$arr = explode(',',$content);
		foreach($arr as $row){
			$a = explode(':',$row);
			$result[] = ['name'=>$a[0], 'weight'=>$a[1]];
		}
		return $result;
	}

	//加权随机
	static private function random_weight($arr){
		$weightSum = 0;
		foreach ($arr as $value) {
			$weightSum += $value['weight'];
		}
		if($weightSum<=0)return false;
		$randNum = rand(1, $weightSum);
		foreach ($arr as $k => $v) {
			if ($randNum <= $v['weight']) {
				return $v['name'];
			}
			$randNum -=$v['weight'];
		}
	}
}
