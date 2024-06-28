<?php

class ttpays_plugin
{
	static public $info = [
		'name'        => 'ttpays', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '天天畅付', //支付插件显示名称
		'author'      => '天天畅付', //支付插件作者
		'link'        => 'http://www.ttpays.cn/', //支付插件作者链接
		'types'       => ['alipay','wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '应用ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '应用密钥',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '微信是否开启小程序支付',
				'type' => 'select',
				'options' => [0=>'否',1=>'是'],
			],
		],
		'select' => null,
		'note' => '在商户后台配置异步通知地址：[siteurl]pay/notify/[channel]/', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/?sitename='.$sitename];
		}elseif($order['typename']=='wxpay'){
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif(checkmobile()==true){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/?sitename='.$sitename];
			}
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat'){
				return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif($device=='mobile'){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}
	}

	//下单通用
	static private function addOrder($service, $extra = null){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT.'inc/Utils.php');
		require(PAY_ROOT.'inc/Payment.php');

		session_start();

		$pay = new TTPayment($channel['appid'], $channel['appkey']);
		$param = [];
		$param['service'] = $service;
		$param['out_trade_no'] = TRADE_NO;
		$param['amount'] = $order['realmoney'];
		$param['subject'] = $ordername;
		$param['return_url'] = $siteurl.'pay/return/'.TRADE_NO.'/';
		if($extra){
			$param = array_merge($param, $extra);
		}

		if($_SESSION[TRADE_NO.'_pay']){
			$result = $_SESSION[TRADE_NO.'_pay'];
		}else{
			$result = $pay->commit($param);
			$_SESSION[TRADE_NO.'_pay'] = $result;
		}

		if(isset($result['code']) && $result['code']==0){
			if(isset($result['data']['status_code']) && $result['data']['status_code']==0){
				return $result['data']['result'];
			}elseif(isset($result['data']['desc'])){
				throw new Exception('['.$result['data']['status_code'].']'.$result['data']['desc']);
			}else{
				throw new Exception('未知错误');
			}
		}else{
			if($result['message'] == 'error' && is_array($result['error'])){
				$first = array_shift($result['error']);
				$result['message'] = is_array($first) ? $first[0] : $first;
			}
			throw new Exception($result['message']?$result['message']:'返回数据解析失败');
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::addOrder('alipay.native');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		try{
			$code_url = self::addOrder('wx.h5');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
	}

	//微信公众号支付
	static public function wxjspay(){
		try{
			$code_url = self::addOrder('wx.h5');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}
		return ['type'=>'jump','url'=>$code_url];
	}

	//微信手机支付
	static public function wxwappay(){
		global $channel;

		if($channel['appswitch'] == 1){
			try{
				$code_url = self::addOrder('wx.mini');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
			return ['type'=>'jump','url'=>$code_url];
		}else{
			try{
				$code_url = self::addOrder('wx.h5');
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
			}
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		}
	}

	//异步回调
	static public function notify(){
		global $channel, $order, $DB;

		//file_put_contents('logs.txt' , http_build_query($_POST));

		$out_trade_no = daddslashes($_POST['out_trade_no']);
		$order = $DB->getRow("SELECT * FROM pre_order WHERE trade_no='$out_trade_no' limit 1");
		if(!$order)return ['type'=>'html','data'=>'fail'];

		$channel = \lib\Channel::get($order['channel']);
		if(!$channel)return ['type'=>'html','data'=>'fail'];

		require(PAY_ROOT.'inc/Utils.php');

		if(TTUtils::verifySign($channel['appkey'], $_POST)){
			if($_POST['status'] == 'FINISHED'){
				$api_trade_no = daddslashes($_POST['trade_no']);
				$money = $_POST['amount'];

				if (round($money,2)==round($order['realmoney'],2)) {
					processNotify($order, $api_trade_no);
				}
				return ['type'=>'html','data'=>'success'];
			}else{
				return ['type'=>'html','data'=>'status='.$_POST['status']];
			}
		}else{
			return ['type'=>'html','data'=>'fail'];
		}
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT.'inc/Utils.php');
		require(PAY_ROOT.'inc/Payment.php');

		$pay = new TTPayment($channel['appid'], $channel['appkey']);
		$result = $pay->refund($order['trade_no']);

		if (isset($result['code']) && $result['code'] == 0) {
			return ['code'=>0, 'trade_no'=>$result['data']['out_trade_no'], 'refund_fee'=>$result['data']['amount']];
		} else {
			return ['code'=>-1, 'msg'=>$result['message']?$result['message']:'返回数据解析失败'];
		}
	}

}