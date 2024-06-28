<?php
namespace lib;

use Exception;

class Plugin {

	static public function getList(){
		$dir = PLUGIN_ROOT;
		$dirArray[] = NULL;
		if (false != ($handle = opendir($dir))) {
			$i = 0;
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && strpos($file, ".")===false) {
					$dirArray[$i] = $file;
					$i++;
				}
			}
			closedir($handle);
		}
		return $dirArray;
	}

	static public function getConfig($name){
		$filename = PLUGIN_ROOT.$name.'/'.$name.'_plugin.php';
		$classname = '\\'.$name.'_plugin';
		if(file_exists($filename)){
			include $filename;
			if (class_exists($classname, false) && property_exists($classname, 'info')) {
				return $classname::$info;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	static public function loadForPay($s){
		global $DB,$conf,$order,$channel,$ordername;
		if(preg_match('/^(.[a-zA-Z0-9]+)\/([0-9]+)\/$/',$s, $matchs)){
			$func = $matchs[1];
			$trade_no = $matchs[2];
			
			$order = $DB->getRow("SELECT A.*,B.name typename FROM pre_order A left join pre_type B on A.type=B.id WHERE trade_no=:trade_no limit 1", [':trade_no'=>$trade_no]);
            if (!$order) {
				$channel = \lib\Channel::get($trade_no);
				if(!$channel) throw new Exception('该订单号不存在，请返回来源地重新发起请求！');
				$trade_no = null;
            }else{
				$channelinfo = $DB->getColumn("SELECT channelinfo FROM pre_user WHERE uid='{$order['uid']}' limit 1");
				$channel = \lib\Channel::get($order['channel'], $channelinfo);
				if(!$channel)throw new Exception('当前支付通道信息不存在');
				$channel['apptype'] = explode(',',$channel['apptype']);
	
				$userrow = $DB->getRow("SELECT `ordername` FROM `pre_user` WHERE `uid`='{$order['uid']}' LIMIT 1");
				if(!empty($userrow['ordername']))$conf['ordername']=$userrow['ordername'];
				$ordername = !empty($conf['ordername'])?ordername_replace($conf['ordername'],$order['name'],$order['uid'],$trade_no):$order['name'];
			}

			return self::loadClass($channel['plugin'], $func, $trade_no);
		}else{
			throw new Exception('URL参数不符合规范');
		}
	}

	static public function loadForSubmit($plugin, $trade_no, $ismapi=false){
		global $DB,$conf,$order,$channel,$ordername,$userrow;
		if(preg_match('/^(.[a-zA-Z0-9]+)$/',$plugin) && preg_match('/^(.[0-9]+)$/',$trade_no)){
			$func = 'submit';
			if($ismapi) $func = 'mapi';
			
			$channelinfo = $userrow?$userrow['channelinfo']:null;
			$channel = \lib\Channel::get($order['channel'], $channelinfo);
			if(!$channel)throw new Exception('当前支付通道信息不存在');
			$channel['apptype'] = explode(',',$channel['apptype']);
			if(!empty($userrow['ordername']))$conf['ordername']=$userrow['ordername'];
			$ordername = !empty($conf['ordername'])?ordername_replace($conf['ordername'],$order['name'],$order['uid'],$trade_no):$order['name'];

			return self::loadClass($plugin, $func, $trade_no);
		}else{
			throw new Exception('URL参数不符合规范');
		}
	}

	static private function loadClass($plugin, $func, $trade_no){
		$filename = PLUGIN_ROOT.$plugin.'/'.$plugin.'_plugin.php';
		$classname = '\\'.$plugin.'_plugin';
        if (file_exists($filename)) {
			define("IN_PLUGIN", true);
            define("PAY_PLUGIN", $plugin);
            define("PAY_ROOT", PLUGIN_ROOT.PAY_PLUGIN.'/');
            define("TRADE_NO", $trade_no);
            include $filename;
            if (class_exists($classname, false) && method_exists($classname, $func)) {
                return $classname::$func();
            } else {
				if($func == 'mapi' && class_exists($classname, false) && method_exists($classname, 'submit')){
					global $siteurl;
					return ['type'=>'jump','url'=>$siteurl.'pay/submit/'.TRADE_NO.'/'];
				}else{
					throw new Exception('插件方法不存在:'.$func);
				}
            }
        }else{
			throw new Exception('Pay file not found');
		}
	}

	
	static public function exists($name){
		$filename = PLUGIN_ROOT.$name.'/'.$name.'_plugin.php';
		if(file_exists($filename)){
			return true;
		}else{
			return false;
		}
	}

	static public function isrefund($name){
		$filename = PLUGIN_ROOT.$name.'/'.$name.'_plugin.php';
		$classname = '\\'.$name.'_plugin';
		if(file_exists($filename)){
			include $filename;
			if (class_exists($classname, false) && method_exists($classname, 'refund')) {
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	static public function refund($trade_no, $money, &$message){
		global $order,$channel,$DB;
		if(!preg_match('/^(.[0-9]+)$/',$trade_no))return false;
		$order = $DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no limit 1", [':trade_no'=>$trade_no]);
		if(!$order){
			$message = '该订单号不存在';
			return false;
		}
		$channel = \lib\Channel::get($order['channel']);
		if(!$channel){
			$message = '当前支付通道信息不存在';
			return false;
		}
		$order['refundmoney'] = $money;
		$filename = PLUGIN_ROOT.$channel['plugin'].'/'.$channel['plugin'].'_plugin.php';
		$classname = '\\'.$channel['plugin'].'_plugin';
		if(file_exists($filename)){
			include $filename;
			if (class_exists($classname, false) && method_exists($classname, 'refund')) {
				define("IN_REFUND", true);
				define("PAY_PLUGIN", $channel['plugin']);
				define("PAY_ROOT", PLUGIN_ROOT.PAY_PLUGIN.'/');
				define("TRADE_NO", $trade_no);
				$result = $classname::refund($order);
				if($result && $result['code']==0){
					return true;
				}else{
					$message = $result['msg'];
					return false;
				}
			}else{
				$message = '当前支付通道不支持API退款';
				return false;
			}
		}else{
			$message = '支付插件不存在';
			return false;
		}
	}

	static public function updateAll(){
		global $DB;
		$DB->exec("TRUNCATE TABLE pre_plugin");
		$list = self::getList();
		foreach($list as $name){
			if($config = self::getConfig($name)){
				if($config['name']!=$name)continue;
				$DB->insert('plugin',['name'=>$config['name'], 'showname'=>$config['showname'], 'author'=>$config['author'], 'link'=>$config['link'], 'types'=>implode(',',$config['types'])]);
			}
		}
		return true;
	}

	static public function get($name){
		global $DB;
		$result = $DB->getRow("SELECT * FROM pre_plugin WHERE name='$name'");
		return $result;
	}

	static public function getAll(){
		global $DB;
		$result = $DB->getAll("SELECT * FROM pre_plugin ORDER BY name ASC");
		return $result;
	}
}
