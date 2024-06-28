<?php

class qxapp_plugin
{
	static public $info = [
		'name'        => 'qxapp', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '千寻畅付', //支付插件显示名称
		'author'      => '千寻畅付', //支付插件作者
		'link'        => 'https://www.qxapp.net/', //支付插件作者链接
		'types'       => ['alipay','wxpay','qqpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => 'AppId',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'AppKey',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => null,
		'note' => '在商户后台配置异步通知地址：[siteurl]pay/notify/[channel]/', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		return ['type'=>'jump','url'=>'/pay/'.$order['typename'].'/'.TRADE_NO.'/?sitename='.$sitename];
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $device, $mdevice;

		$typename = $order['typename'];
		return self::$typename();
	}

	static private function make_sign($param, $key){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $v!=''){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr .= 'key='.$key;
		$sign = strtoupper(md5($signstr));
		return $sign;
	}

	//通用创建订单
	static private function addOrder($method){
		global $channel, $order, $ordername, $conf, $clientip, $siteurl;

		session_start();

		$apiurl = 'https://pay.qxapp.net/Gateway/api';
		$data = [
			'store_id' => '',
			'total' => intval($order['realmoney']*100),
			'nonce_str' => random(12),
			'body' => $ordername,
			'out_trade_no' => TRADE_NO,
		];
		if($method == 'wx_jsapi' || $method == 'ali_jsapi' || $method == 'qq_jsapi'){
			$data += [
				'openid' => '',
				'return_url' => $siteurl.'pay/return/'.TRADE_NO.'/'
			];
		}
		$param = array(
			'appid' => $channel['appid'],
			'method' => $method,
			'data' => $data,
			'sign' => self::make_sign($data,$channel['appkey'])
		);

		$post = json_encode($param);

		if($_SESSION[TRADE_NO.'_pay']){
			$data = $_SESSION[TRADE_NO.'_pay'];
		}else{
			$data = get_curl($apiurl, $post);
			$_SESSION[TRADE_NO.'_pay'] = $data;
		}

		$result = json_decode($data, true);

		if($result['code']==100 && $result['data']['result_code']=='0000'){
			\lib\Payment::updateOrder(TRADE_NO, $result['data']['out_trade_no']);
			$code_url = $result['data']['code_url'];
		}elseif($result['code']==100){
			throw new Exception($result['data']['result_msg']);
		}elseif(preg_match('/<p class="error">(.*?)<\/p>/',$data,$match)){
			throw new Exception($match[1]);
		}else{
			throw new Exception($result['msg']);
		}
		return $code_url;
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::addOrder('ali_jsapi');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		if(strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient')!==false){
			return ['type'=>'jump','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
		}
	}

	//微信扫码支付
	static public function wxpay(){
		try{
			$code_url = self::addOrder('wx_jsapi');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile()==true) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//QQ扫码支付
	static public function qqpay(){
		try{
			$code_url = self::addOrder('qq_native');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'QQ支付下单失败！'.$ex->getMessage()];
		}

		if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/')!==false){
			return ['type'=>'jump','url'=>$code_url];
		}elseif(checkmobile()==true && !isset($_GET['qrcode'])){
			return ['type'=>'qrcode','page'=>'qqpay_wap','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'qqpay_qrcode','url'=>$code_url];
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::addOrder('union_scan');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'银联云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order, $DB;

		$json = file_get_contents("php://input");
		$data = json_decode($json, true);

		$out_trade_no = daddslashes($data['u_out_trade_no']);
		$order = $DB->getRow("SELECT * FROM pre_order WHERE trade_no='$out_trade_no' limit 1");
		if(!$order)return ['type'=>'html','data'=>'FAIL'];

		$channel = \lib\Channel::get($order['channel']);
		if(!$channel)return ['type'=>'html','data'=>'FAIL'];

		//file_put_contents('log.txt',$json);

		$sign = self::make_sign($data,$channel['appkey']);

		if($sign===$data["sign"]){
			if($data['status'] == 1){
				$api_trade_no = daddslashes($data['out_trade_no']);
				processNotify($order, $api_trade_no);
				return ['type'=>'html','data'=>'SUCCESS'];
			}else{
				return ['type'=>'html','data'=>'status='.$data['status']];
			}
		}else{
			return ['type'=>'html','data'=>'FAIL'];
		}
	}

	//同步回调
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

}