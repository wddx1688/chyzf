<?php

class xinhui_plugin
{
	static public $info = [
		'name'        => 'xinhui', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '信汇支付', //支付插件显示名称
		'author'      => '信汇', //支付插件作者
		'link'        => '', //支付插件作者链接
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
			'appurl' => [
				'name' => '支付接口域名',
				'type' => 'input',
				'note' => '默认为www.zzhengt.com，只填写域名，不要带http://和/',
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => true, //是否支持绑定微信公众号
		'bindwxa' => true, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/?sitename='.$sitename];
		}elseif($order['typename']=='wxpay'){
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif(checkmobile()==true && ($channel['appwxa']>0||$channel['appwxmp']>0)){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/?sitename='.$sitename];
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
			if($mdevice=='wechat' && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif($device=='mobile' && ($channel['appwxa']>0||$channel['appwxmp']>0)){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		global $channel, $order, $ordername, $conf, $clientip;

		session_start();

		require PAY_ROOT . 'inc/XhPlatFormPay.php';
		$app = XhPlatFormPay::getInstance();
		$app->setConfig(include PAY_ROOT . 'inc/config.php');
		if(strpos($ordername,'-')){
			$subject = substr($ordername, 0, strpos($ordername,'-'));
			$subject_desc = substr($ordername, strpos($ordername,'-')+1);
		}else{
			$subject = $ordername;
			$subject_desc = '自助购物';
		}
		if($_SESSION[TRADE_NO.'_alipay']){
			$result = $_SESSION[TRADE_NO.'_alipay'];
		}else{
			$result = $app->order([
				'trade_no'     => TRADE_NO,
				'total_amount' => $order['realmoney'],
				'bizCode'      => 1002, // 支付宝扫码
				'notify_url'   => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
				'remote_addr' => $clientip,
				'subject' => $subject,
				'subject_desc' => $subject_desc,
			])->submit();
			$_SESSION[TRADE_NO.'_alipay'] = $result;
		}
		if (!$result) {
			return ['type'=>'error','msg'=>'支付宝创建订单失败：'.$app->getError()];
		}
		\lib\Payment::updateOrder(TRADE_NO, $result['upOrderId']);
		$code_url = $result['payUrl'];

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $order, $ordername, $conf, $clientip;

		session_start();

		require PAY_ROOT . 'inc/XhPlatFormPay.php';
		$app = XhPlatFormPay::getInstance();
		$app->setConfig(include PAY_ROOT . 'inc/config.php');
		if(strpos($ordername,'-')){
			$subject = substr($ordername, 0, strpos($ordername,'-'));
			$subject_desc = substr($ordername, strpos($ordername,'-')+1);
		}else{
			$subject = $ordername;
			$subject_desc = '自助购物';
		}
		if($_SESSION[TRADE_NO.'_wxpay']){
			$result = $_SESSION[TRADE_NO.'_wxpay'];
		}else{
			$result = $app->order([
				'trade_no'     => TRADE_NO,
				'total_amount' => $order['realmoney'],
				'bizCode'      => 1001, // 微信扫码支付
				'notify_url'   => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
				'remote_addr' => $clientip,
				'subject' => $subject,
				'subject_desc' => $subject_desc,
			])->submit();
			$_SESSION[TRADE_NO.'_wxpay'] = $result;
		}
		if (!$result) {
			return ['type'=>'error','msg'=>'微信支付创建订单失败：'.$app->getError()];
		}
		\lib\Payment::updateOrder(TRADE_NO, $result['upOrderId']);
		$code_url = $result['payUrl'];
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile()==true) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//微信公众号支付
	static public function wxjspay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		session_start();
		if($_SESSION[TRADE_NO.'_wxpay']){
			$result = $_SESSION[TRADE_NO.'_wxpay'];
		}else{

		//①、获取用户openid
		$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
		if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];
		$tools = new \lib\wechat\JsApiPay($wxinfo['appid'], $wxinfo['appsecret']);
		$openId = $tools->GetOpenid();
		if(!$openId)return ['type'=>'error','msg'=>'OpenId获取失败('.$tools->data['errmsg'].')'];
		$blocks = checkBlockUser($openId, TRADE_NO);
		if($blocks) return $blocks;

		if(strpos($ordername,'-')){
			$subject = substr($ordername, 0, strpos($ordername,'-'));
			$subject_desc = substr($ordername, strpos($ordername,'-')+1);
		}else{
			$subject = $ordername;
			$subject_desc = '自助购物';
		}
		
		//②、统一下单
		require PAY_ROOT . 'inc/XhPlatFormPay.php';
		$app = XhPlatFormPay::getInstance();
		$app->setConfig(include PAY_ROOT . 'inc/config.php');
		$result = $app->order([
			'trade_no'     => TRADE_NO,
			'total_amount' => $order['realmoney'],
			'bizCode'      => 1301, // 微信公众号支付
			'notify_url'   => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'remote_addr' => $clientip,
			'app_id' => WxPayConfig::APPID,
			'open_id' => $openId,
			'subject' => $subject,
			'subject_desc' => $subject_desc,
		])->submit();
		$_SESSION[TRADE_NO.'_wxpay'] = $result;
		}
		if (!$result) {
			return ['type'=>'error','msg'=>'微信支付创建订单失败：'.$app->getError()];
		}
		\lib\Payment::updateOrder(TRADE_NO, $result['upOrderId']);
		
		$jsApiParameters = $result['message'];

		if($_GET['d']==1){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>$jsApiParameters, 'redirect_url'=>$redirect_url]];
	}

	//微信手机支付
	static public function wxwappay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		if($channel['appwxa']>0){
			$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
			if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信小程序不存在'];
			try{
				$access_token = wx_get_access_token($wxinfo['appid'], $wxinfo['appsecret']);
				if($access_token){
					$jump_url = $siteurl.'pay/wxminipay/'.TRADE_NO.'/';
					$path = 'pages/pay/pay';
					$query = 'money='.$order['realmoney'].'&url='.$jump_url;
					$code_url = wxa_generate_scheme($access_token, $path, $query);
				}
			}catch(Exception $e){
				return ['type'=>'error','msg'=>$e->getMessage()];
			}
			return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
		}else{
			$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		}
	}

	//微信小程序支付
	static public function wxminipay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		$code = isset($_GET['code'])?trim($_GET['code']):exit('{"code":-1,"msg":"code不能为空"}');
		
		//①、获取用户openid
		$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
		if(!$wxinfo)exit('{"code":-1,"msg":"支付通道绑定的微信小程序不存在"}');
		$tools = new \lib\wechat\MiniAppPay($wxinfo['appid'], $wxinfo['appsecret']);
		$openId = $tools->GetOpenid($code);
		if(!$openId)exit('{"code":-1,"msg":"OpenId获取失败('.$tools->data['errmsg'].')"}');
		$blocks = checkBlockUser($openId, TRADE_NO);
		if($blocks)exit('{"code":-1,"msg":"'.$blocks['msg'].'"}');

		if(strpos($ordername,'-')){
			$subject = substr($ordername, 0, strpos($ordername,'-'));
			$subject_desc = substr($ordername, strpos($ordername,'-')+1);
		}else{
			$subject = $ordername;
			$subject_desc = '自助购物';
		}
		
		//②、统一下单
		require PAY_ROOT . 'inc/XhPlatFormPay.php';
		$app = XhPlatFormPay::getInstance();
		$app->setConfig(include PAY_ROOT . 'inc/config.php');
		$result = $app->order([
			'trade_no'     => TRADE_NO,
			'total_amount' => $order['realmoney'],
			'bizCode'      => 1301, // 微信公众号支付
			'notify_url'   => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'remote_addr' => $clientip,
			'app_id' => WxPayConfig::APPID,
			'open_id' => $openId,
			'subject' => $subject,
			'subject_desc' => $subject_desc,
		])->submit();
		if (!$result) {
			exit(json_encode(['code'=>-1, 'msg'=>'微信支付创建订单失败：'.$app->getError()]));
		}
		\lib\Payment::updateOrder(TRADE_NO, $result['upOrderId']);
		$jsApiParameters = $result['message'];
		exit(json_encode(['code'=>0, 'data'=>json_decode($jsApiParameters, true)]));
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$post = file_get_contents('php://input');
		if(!$post)return ['type'=>'html','data'=>'error data'];

		require PAY_ROOT . 'inc/XhPlatFormPay.php';
		$app = XhPlatFormPay::getInstance();
		$app->setConfig(include PAY_ROOT . 'inc/config.php');
		if ($app->payNotify($post)) {
			$out_trade_no     = daddslashes($app->getInput('orderId'));
			$trade_no     = daddslashes($app->getInput('upOrderId'));
			$orderAmount  = sprintf('%.2f', abs($app->getInput('orderAmt')) / 100);
			$_orderAmount = sprintf('%.2f', $order['realmoney']);
			if ($orderAmount === $_orderAmount && $out_trade_no==TRADE_NO) {
				processNotify($order, $trade_no);
			}
			return ['type'=>'html','data'=>'ok'];
		} else {
			return ['type'=>'html','data'=>'error sign'];
		}
	}

	//支付成功页面
	static public function ok(){
		return ['type'=>'page','page'=>'ok'];
	}
	
	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require PAY_ROOT . 'inc/XhPlatFormPay.php';
		$app = XhPlatFormPay::getInstance();
		$app->setConfig(include PAY_ROOT . 'inc/config.php');
		$result = $app->order([
			'notify_url'   => $conf['localurl'] . 'pay/refundnotify/' . TRADE_NO . '/',
		])->refund(TRADE_NO.'REF', $order['api_trade_no'], $order['realmoney']);
		if (!$result) {
			$result = ['code'=>-1, 'msg'=>$app->getError()];
		}else{
			$result = ['code'=>0, 'trade_no'=>$result['orderId'], 'refund_fee'=>$result['refundAmt']];
		}
		return $result;
	}

	//退款回调
	static public function refundnotify(){
		global $channel, $order;

		$post = file_get_contents('php://input');
		if(!$post)return ['type'=>'html','data'=>'error data'];

		require PAY_ROOT . 'inc/XhPlatFormPay.php';
		$app = XhPlatFormPay::getInstance();
		$app->setConfig(include PAY_ROOT . 'inc/config.php');
		if ($app->payNotify($post)) {
			$out_trade_no     = daddslashes($app->getInput('orderId'));
			$trade_no     = daddslashes($app->getInput('upOrderId'));
			$orderAmount  = sprintf('%.2f', abs($app->getInput('orderAmt')) / 100);
			$_orderAmount = sprintf('%.2f', $order['realmoney']);
			return ['type'=>'html','data'=>'ok'];
		} else {
			return ['type'=>'html','data'=>'error sign'];
		}
	}
}