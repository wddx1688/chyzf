<?php

class payjs_plugin
{
	static public $info = [
		'name'        => 'payjs', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'PAYJS', //支付插件显示名称
		'author'      => 'PAYJS', //支付插件作者
		'link'        => 'https://payjs.cn/', //支付插件作者链接
		'types'       => ['alipay','wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户密钥',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '微信是否支持H5',
				'type' => 'select',
				'options' => [0=>'否',1=>'是'],
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
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
		global $siteurl, $channel, $order, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat'){
				return self::wxjspay();
			}elseif($device=='mobile'){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		global $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT.'inc/payjs.class.php');
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$pay = new Payjs($pay_config);
		$arr = [
			'body' => $ordername,
			'out_trade_no' => TRADE_NO,
			'total_fee' => strval($order['realmoney']*100),
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'type' => 'alipay',
		];
		$result = $pay->pay($arr);

		if($result['return_code'] == 1){
			\lib\Payment::updateOrder(TRADE_NO, $result['payjs_order_id']);
			$code_url = $result['code_url'];
		}else{
			return ['type'=>'error','msg'=>'支付宝支付下单失败 '.$result['return_msg']];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT.'inc/payjs.class.php');
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$pay = new Payjs($pay_config);
		$arr = [
			'body' => $ordername,
			'out_trade_no' => TRADE_NO,
			'total_fee' => strval($order['realmoney']*100),
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
		];
		$result = $pay->pay($arr);

		if($result['return_code'] == 1){
			\lib\Payment::updateOrder(TRADE_NO, $result['payjs_order_id']);
			$code_url = $result['code_url'];
		}else{
			return ['type'=>'error','msg'=>'微信支付下单失败 '.$result['return_msg']];
		}

		return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
	}

	//微信公众号支付（收银台）
	static public function wxjspay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT.'inc/payjs.class.php');
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$pay = new Payjs($pay_config);
		$arr = [
			'body' => $ordername,
			'out_trade_no' => TRADE_NO,
			'total_fee' => strval($order['realmoney']*100),
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'auto' => '1',
		];
		if($_GET['d']==1)$arr['callback_url'] = $siteurl.'pay/return/'.TRADE_NO.'/';
		
		$url = $pay->cashier($arr);
		return ['type'=>'jump','url'=>$url];
	}

	//微信手机支付
	static public function wxwappay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		if($channel['appswitch']==1){

		require(PAY_ROOT.'inc/payjs.class.php');
		$pay_config = require(PAY_ROOT.'inc/config.php');
		
		$pay = new Payjs($pay_config);
		$arr = [
			'body' => $ordername,
			'out_trade_no' => TRADE_NO,
			'total_fee' => strval($order['realmoney']*100),
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'callback_url' => $siteurl.'pay/return/'.TRADE_NO.'/',
		];
		$result = $pay->mwebpay($arr);
		
		if($result['return_code'] == 1){
			\lib\Payment::updateOrder(TRADE_NO, $result['payjs_order_id']);
			$h5_url = $result['h5_url'];
			return ['type'=>'jump','url'=>$h5_url];
		}else{
			return ['type'=>'error','msg'=>'微信支付下单失败 '.$result['return_msg']];
		}
		
		}else{
			$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		}
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT.'inc/payjs.class.php');
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$pay = new Payjs($pay_config);

		if($pay->checkSign($_POST)){
			
			if($_POST['return_code'] == 1){
				$out_trade_no = daddslashes($_POST['out_trade_no']);
				$payjs_order_id = daddslashes($_POST['payjs_order_id']);
				$openid = daddslashes($_POST['openid']);
				$total_fee = $_POST['total_fee'];
				if($out_trade_no == TRADE_NO && $total_fee==strval($order['realmoney']*100)){
					processNotify($order, $payjs_order_id, $openid);
				}

				return ['type'=>'html','data'=>'success'];
			}else{
				return ['type'=>'html','data'=>'fail'];
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

		require(PAY_ROOT.'inc/payjs.class.php');
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$pay = new Payjs($pay_config);

		$result = $pay->refund($order['api_trade_no']);

		if($result['return_code'] == 1){
			$result = ['code'=>0, 'trade_no'=>$result['payjs_order_id'], 'refund_fee'=>$order['realmoney']];
		}else{
			$result = ['code'=>-1, 'msg'=>$result["return_msg"]];
		}
		return $result;
	}
}