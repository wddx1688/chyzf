<?php

class epay_plugin
{
	static public $info = [
		'name'        => 'epay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '彩虹易支付', //支付插件显示名称
		'author'      => '彩虹', //支付插件作者
		'link'        => '', //支付插件作者链接
		'types'       => ['alipay','qqpay','wxpay','bank','jdpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appurl' => [
				'name' => '接口地址',
				'type' => 'input',
				'note' => '必须以http://或https://开头，以/结尾',
			],
			'appid' => [
				'name' => '商户ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户密钥',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '是否使用mapi接口',
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
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		if($channel['appswitch']==1){
			return ['type'=>'jump','url'=>'/pay/'.$order['typename'].'/'.TRADE_NO.'/'];
		}else{

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");
		$parameter = array(
			"pid" => trim($epay_config['pid']),
			"type" => $order['typename'],
			"notify_url"	=> $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			"return_url"	=> $siteurl.'pay/return/'.TRADE_NO.'/',
			"out_trade_no"	=> TRADE_NO,
			"name"	=> $order['name'],
			"money"	=> (float)$order['realmoney']
		);
		//建立请求
		$epay = new EpayCore($epay_config);
		if(is_https() && substr($epay_config['apiurl'],0,7)=='http://'){
			$jump_url = $epay->getPayLink($parameter);
			return ['type'=>'jump','url'=>$jump_url];
		}else{
			$html_text = $epay->pagePay($parameter, '正在跳转');
			return ['type'=>'html','data'=>$html_text];
		}

		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;
		
		if($channel['appswitch']==1){
			$typename = $order['typename'];
			return self::$typename();
		}else{
			return ['type'=>'jump','url'=>$siteurl.'pay/submit/'.TRADE_NO.'/'];
        }
	}

	static private function getDevice(){
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false) {
			$device = 'wechat';
		}elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/')!==false) {
			$device = 'qq';
		}elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient')!==false) {
			$device = 'alipay';
		}elseif (checkmobile()) {
			$device = 'mobile';
		}else{
			$device = 'pc';
		}
		return $device;
	}

	//mapi接口下单
	static private function pay_mapi($type){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		session_start();

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");
		$parameter = array(
			"pid" => trim($epay_config['pid']),
			"type" => $type,
			"device" => self::getDevice(),
			"clientip" => $clientip,
			"notify_url"	=> $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			"return_url"	=> $siteurl.'pay/return/'.TRADE_NO.'/',
			"out_trade_no"	=> TRADE_NO,
			"name"	=> $order['name'],
			"money"	=> (float)$order['realmoney']
		);
		//建立请求
		$epay = new EpayCore($epay_config);

		if($_SESSION[TRADE_NO.'_pay']){
			$result = $_SESSION[TRADE_NO.'_pay'];
		}else{
			$result = $epay->apiPay($parameter);
			if($result) $_SESSION[TRADE_NO.'_pay'] = $result;
		}

		if(isset($result['code']) && $result['code']==1){
			if($result['payurl']){
				$method = 'jump';
				$url = $result['payurl'];
			}elseif($result['qrcode']){
				$method = 'qrcode';
				$url = $result['qrcode'];
			}elseif($result['urlscheme']){
				$method = 'scheme';
				$url = $result['urlscheme'];
			}else{
				throw new Exception('未返回支付链接');
			}
		}elseif(isset($result['msg'])){
			throw new Exception($result['msg']);
		}else{
			throw new Exception('获取支付接口数据失败');
		}
		return [$method, $url];
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			list($method, $url) = self::pay_mapi('alipay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}else{
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$url];
		}
	}

	//微信扫码支付
	static public function wxpay(){
		try{
			list($method, $url) = self::pay_mapi('wxpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}elseif($method == 'scheme'){
			return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$url];
		}else{
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
				return ['type'=>'jump','url'=>$url];
			} elseif (checkmobile()==true) {
				return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$url];
			} else {
				return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$url];
			}
		}
	}

	//QQ扫码支付
	static public function qqpay(){
		try{
			list($method, $url) = self::pay_mapi('qqpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}else{
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/')!==false){
				return ['type'=>'jump','url'=>$url];
			} elseif(checkmobile() && !isset($_GET['qrcode'])){
				return ['type'=>'qrcode','page'=>'qqpay_wap','url'=>$url];
			} else {
				return ['type'=>'qrcode','page'=>'qqpay_qrcode','url'=>$url];
			}
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			list($method, $url) = self::pay_mapi('bank');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}else{
			return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$url];
		}
	}

	//京东支付
	static public function jdpay(){
		try{
			list($method, $url) = self::pay_mapi('jdpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}
		
		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}else{
			return ['type'=>'qrcode','page'=>'jdpay_qrcode','url'=>$url];
		}
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");

		//计算得出通知验证结果
		$epayNotify = new EpayCore($epay_config);
		$verify_result = $epayNotify->verifyNotify();

		if($verify_result) {//验证成功
			//商户订单号
			$out_trade_no = daddslashes($_GET['out_trade_no']);

			//支付宝交易号
			$trade_no = daddslashes($_GET['trade_no']);

			//交易金额
			$money = $_GET['money'];

			if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
				//付款完成后，支付宝系统发送该交易状态通知
				if($out_trade_no == TRADE_NO && round($money,2)==round($order['realmoney'],2)){
					processNotify($order, $trade_no);
				}
			}
			return ['type'=>'html','data'=>'success'];
		}
		else {
			//验证失败
			return ['type'=>'html','data'=>'fail'];
		}
	}

	//同步回调
	static public function return(){
		global $channel, $order;

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");

		//计算得出通知验证结果
		$epayNotify = new EpayCore($epay_config);
		$verify_result = $epayNotify->verifyReturn();
		if($verify_result) {
			//商户订单号
			$out_trade_no = daddslashes($_GET['out_trade_no']);

			//支付宝交易号
			$trade_no = daddslashes($_GET['trade_no']);

			//交易金额
			$money = $_GET['money'];

			if($_GET['trade_status'] == 'TRADE_SUCCESS') {
				if ($out_trade_no == TRADE_NO && round($money, 2)==round($order['realmoney'], 2)) {
					processReturn($order, $trade_no);
				}else{
					return ['type'=>'error','msg'=>'订单信息校验失败'];
				}
			}else{
				return ['type'=>'error','msg'=>'trade_status='.$_GET['trade_status']];
			}
		}
		else {
			//验证失败
			return ['type'=>'error','msg'=>'验证失败！'];
		}
	}

}