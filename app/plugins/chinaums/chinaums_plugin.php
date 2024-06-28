<?php

class chinaums_plugin
{
	static public $info = [
		'name'        => 'chinaums', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '银联商务', //支付插件显示名称
		'author'      => '银联商务', //支付插件作者
		'link'        => 'https://open.chinaums.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
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
			'appmchid' => [
				'name' => '商户号mid',
				'type' => 'input',
				'note' => '',
			],
			'appurl' => [
				'name' => '终端号tid',
				'type' => 'input',
				'note' => '',
			],
			'appsecret' => [
				'name' => 'MD5密钥',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	const instMid = 'QRPAYDEFAULT'; //机构商户号
	const msgSrcId = '31VT'; //来源编号
	const isTest = false; //是否测试环境

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/?sitename='.$sitename];
		}elseif($order['typename']=='wxpay'){
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif(checkmobile()==true && $channel['appwxa']>0){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/?sitename='.$sitename];
			}
		}elseif($order['typename']=='bank'){
			return ['type'=>'jump','url'=>'/pay/bank/'.TRADE_NO.'/?sitename='.$sitename];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat' && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>$siteurl.'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif($device=='mobile' && $channel['appwxa']>0){
				return ['type'=>'jump','url'=>$siteurl.'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	//扫码下单
	static private function qrcode(){
		global $channel, $order, $ordername, $conf, $clientip, $siteurl;

		session_start();

		require(PAY_ROOT."inc/Build.class.php");

		$client = new ChinaumsBuild($channel['appid'], $channel['appkey'], self::isTest);
		
		$path = '/v1/netpay/bills/get-qrcode';
		$time = time();
		//$qrCodeId = self::msgSrcId.date('YmdHis', $time).rand(111,999).rand(1111111,9999999);
		$param = [
			'msgId' => md5(uniqid(mt_rand(), true)),
			'requestTimestamp' => date('Y-m-d H:i:s', $time),
			'mid' => $channel['appmchid'],
			'tid' => $channel['appurl'],
			'instMid' => self::instMid,
			'billNo' => self::msgSrcId.TRADE_NO,
			'billDate' => date('Y-m-d', $time),
			'billDesc' => $ordername,
			'totalAmount' => $order['realmoney']*100,
			'notifyUrl' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'returnUrl' => $siteurl.'pay/return/'.TRADE_NO.'/',
			//'qrCodeId' => $qrCodeId,
		];

		if($_SESSION[TRADE_NO.'_pay']){
			$result = $_SESSION[TRADE_NO.'_pay'];
		}else{
			$result = $client->request($path, $param, $time);
			$_SESSION[TRADE_NO.'_pay'] = $result;
		}

		if(isset($result['errCode']) && $result['errCode']=='SUCCESS'){
			return $result['billQRCode'];
		}else{
			throw new Exception($result['errMsg']?$result['errMsg']:'返回数据解析失败');
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::qrcode();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		try{
			$code_url = self::qrcode();
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

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::qrcode();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/Build.class.php");

		$client = new ChinaumsBuild($channel['appid'], $channel['appkey']);

		$verifyResult = $client->verify($_POST, $channel['appsecret']);

		if($verifyResult){
			if($_POST['billStatus'] == 'PAID'){
				$out_trade_no = substr($_POST['billNo'],4);
				$billPayment = json_decode($_POST['billPayment'], true);
				$trade_no = $billPayment['qrCodeId'];
				$money = $_POST['totalAmount'];
				$buyer = $billPayment['buyerId'];
				if($out_trade_no == TRADE_NO && $money==strval($order['realmoney']*100)){
					processNotify($order, $trade_no, $buyer);
				}
				return ['type'=>'html','data'=>'SUCCESS'];
			}else{
				return ['type'=>'html','data'=>'status fail'];
			}
		}
		return ['type'=>'html','data'=>'sign fail'];
	}

	//同步回调
	static public function return(){
		global $channel, $order;

		require(PAY_ROOT."inc/Build.class.php");

		$client = new ChinaumsBuild($channel['appid'], $channel['appkey']);

		$verifyResult = $client->verify($_GET, $channel['appsecret']);

		if($verifyResult){
			if($_GET['billStatus'] == 'PAID'){
				$out_trade_no = $_GET['billNo'];
				$billPayment = json_decode($_GET['billPayment'], true);
				$trade_no = $billPayment['qrCodeId'];
				$money = $_GET['totalAmount'];
				$buyer = $billPayment['buyerId'];
				if($out_trade_no == TRADE_NO && $money==strval($order['realmoney']*100)){
					processReturn($order, $trade_no, $buyer);
				}else{
					return ['type'=>'error','msg'=>'订单信息校验失败'];
				}
			}else{
				return ['type'=>'error','msg'=>'billStatus='.$_GET['billStatus']];
			}
		}else{
			return ['type'=>'error','msg'=>'签名校验失败'];
		}
	}

	//退款
	static public function refund($order){
		global $channel, $conf;
		if(empty($order))exit();

		require(PAY_ROOT."inc/Build.class.php");

		$client = new ChinaumsBuild($channel['appid'], $channel['appkey'], self::isTest);
		
		$path = '/v1/netpay/bills/refund';
		$time = time();
		$param = [
			'msgId' => md5(uniqid(mt_rand(), true)),
			'requestTimestamp' => date('Y-m-d H:i:s', $time),
			'mid' => $channel['appmchid'],
			'tid' => $channel['appurl'],
			'instMid' => self::instMid,
			'billNo' => $order['trade_no'],
			'billDate' => date('Y-m-d', strtotime($order['addtime'])),
			'refundAmount' => $order['refundmoney']*100,
		];

		$result = $client->request($path, $param, $time);
		if(isset($result['errCode']) && $result['errCode']=='SUCCESS'){
			return ['code'=>0, 'trade_no'=>$result['billNo'], 'refund_fee'=>round($result['refundAmount']/100, 2)];
		}else{
			return ['code'=>-1, 'msg'=>$result['errMsg']?$result['errMsg']:'返回数据解析失败'];
		}
	}
}