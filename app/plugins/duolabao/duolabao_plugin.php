<?php

class duolabao_plugin
{
	static public $info = [
		'name'        => 'duolabao', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '哆啦宝支付', //支付插件显示名称
		'author'      => '哆啦宝', //支付插件作者
		'link'        => 'http://www.duolabao.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','qqpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户编号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '店铺编号',
				'type' => 'input',
				'note' => '',
			],
			'appmchid' => [
				'name' => '公钥',
				'type' => 'input',
				'note' => '',
			],
			'appsecret' => [
				'name' => '私钥',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
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

	//通用创建订单
	static public function addOrder(){
		global $channel, $order, $ordername, $conf, $clientip;

		require PAY_ROOT.'inc/App.php';
		$sub = App::config(include PAY_ROOT.'inc/config.php')->submit($order['trade_no'], $order['realmoney']);
		if ($sub === false) {
			throw new Exception('支付下单失败');
		} else if (strtolower($sub['result']) === 'success' && array_key_exists('data' , $sub)) {
			$code_url = $sub['data']['url'];
		} else {
			throw new Exception('支付下单失败 ['.$sub['error']['errorCode'].']'.$sub['error']['errorMsg']);
		}
		return $code_url;
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::addOrder();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		try{
			$code_url = self::addOrder();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if (checkmobile()==true) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//QQ扫码支付
	static public function qqpay(){
		try{
			$code_url = self::addOrder();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if (checkmobile()==true) {
			return ['type'=>'qrcode','page'=>'qqpay_wap','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'qqpay_qrcode','url'=>$code_url];
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::addOrder();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require PAY_ROOT.'inc/App.php';
		//@file_put_contents('./query.txt' , json_encode($_REQUEST));
		if (App::config(include PAY_ROOT.'inc/config.php')->verifyNotify()) {
			$trade_no = daddslashes($_REQUEST['requestNum']); //流水号
			$orderAmount = $_REQUEST['orderAmount']; //订单金额
			$orderStatus = strtolower($_REQUEST['status']);
			$completeTime = $_REQUEST['completeTime']; //订单完成时间
			if ($orderStatus === 'success') {
				if (round($order['realmoney'],2) == round($orderAmount,2)) {
					processNotify($order, $trade_no);
				}
				return ['type'=>'html','data'=>'success'];
			}
		} else {
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

		require PAY_ROOT.'inc/App.php';
		$sub = App::config(include PAY_ROOT.'inc/config.php')->refund($order['trade_no']);
		if ($sub === false) {
			return ['code'=>-1, 'msg'=>'接口请求失败'];
		} else if (strtolower($sub['result']) === 'success' && array_key_exists('data' , $sub)) {
			$result = ['code'=>0, 'trade_no'=>$sub['data']['orderNum'], 'refund_fee'=>$sub['data']['refundAmount']];
		} else {
			return ['code'=>-1,'msg'=>'支付下单失败 ['.$sub['error']['errorCode'].']'.$sub['error']['errorMsg']];
		}
	}
}