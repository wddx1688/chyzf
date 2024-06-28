<?php

class adapay_plugin
{
	static public $info = [
		'name'        => 'adapay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'AdaPay聚合支付', //支付插件显示名称
		'author'      => 'AdaPay', //支付插件作者
		'link'        => 'https://www.adapay.tech/', //支付插件作者链接
		'types'       => ['alipay','wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '渠道ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'API密钥(prod模式)',
				'type' => 'input',
				'note' => '',
			],
			'appsecret' => [
				'name' => '商户私钥',
				'type' => 'textarea',
				'note' => '',
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
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif(checkmobile()==true){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/?sitename='.$sitename];
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
				return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/?d=1'];
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

		session_start();

		require PAY_ROOT . 'inc/Build.class.php';
		$pay_config = include PAY_ROOT . 'inc/config.php';
		$app = AdaPay::config($pay_config)->order([
			'trade_no' => TRADE_NO,
			'money' => $order['realmoney'],
			'goods_title' => $ordername,
			'goods_desc' => $ordername,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
		]);

		if($_SESSION[TRADE_NO.'_alipay']){
			$result = $_SESSION[TRADE_NO.'_alipay'];
		}else{
			try{
				$result = $app->submit('alipay');
			}catch (Exception $e) {
				return ['type'=>'error','msg'=>'支付宝支付下单失败 '.$e->getMessage()];
			}
			$_SESSION[TRADE_NO.'_alipay'] = $result;
		}

		$code_url = $result['expend']['pay_info'];

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		global $siteurl;

		$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';

		return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
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

		require PAY_ROOT . 'inc/Build.class.php';
		$trade_no = TRADE_NO;
		$app = AdaPay::config(include PAY_ROOT . 'inc/config.php')->order([
			'trade_no' => $trade_no,
			'money' => $order['realmoney'],
			'goods_title' => $ordername,
			'goods_desc' => $ordername,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
		]);

		try{
			$result = $app->submit('wx_pub', $openId);
		}catch (Exception $e) {
			return ['type'=>'error','msg'=>'微信支付下单失败 '.$e->getMessage()];
		}
		$_SESSION[TRADE_NO.'_wxpay'] = $result;
		}
		$jsApiParameters = $result['expend']['pay_info'];

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

		//②、统一下单
		require PAY_ROOT . 'inc/Build.class.php';
		$trade_no = TRADE_NO;
		$app = AdaPay::config(include PAY_ROOT . 'inc/config.php')->order([
			'trade_no' => $trade_no,
			'money' => $order['realmoney'],
			'goods_title' => $ordername,
			'goods_desc' => $ordername,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
		]);

		try{
			$result = $app->submit('wx_lite', $openId);
		}catch (Exception $e) {
			exit(json_encode(['code'=>-1, 'msg'=>'微信支付下单失败 '.$e->getMessage()]));
		}
		$jsApiParameters = $result['expend']['pay_info'];
		exit(json_encode(['code'=>0, 'data'=>json_decode($jsApiParameters, true)]));
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		//file_put_contents('logs.txt',http_build_query($_POST));

		require_once PAY_ROOT . 'inc/Build.class.php';
		$app = AdaPay::config(include PAY_ROOT . 'inc/config.php');
		if ($app->ada_tools->verifySign($_POST['sign'] , $_POST['data'])) {
			$_data = json_decode($_POST['data'] , true);
			if ($_data['status'] == 'succeeded') {
				$api_trade_no = daddslashes($_data['id']);
				$trade_no = daddslashes($_data['order_no']);
				$orderAmount = sprintf('%.2f' , $_data['pay_amt']);
				if (sprintf('%.2f' ,$order['realmoney']) == $orderAmount && $trade_no == TRADE_NO) {
					processNotify($order, $api_trade_no);
				}
				return ['type'=>'html','data'=>'Ok'];
			} else {
				return ['type'=>'html','data'=>'No'];
			}
		} else {
			return ['type'=>'html','data'=>'No'];
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

		require PAY_ROOT . 'inc/Build.class.php';
		try{
			$res = AdaPay::config(include PAY_ROOT . 'inc/config.php')->createRefund([
				'payment_id' => $order['api_trade_no'],
				'refund_order_no' => TRADE_NO.'REF',
				'refund_amt' => $order['realmoney']
			]);
		}catch(Exception $e){
			$result = ['code'=>-1, 'msg'=>$e->getMessage()];
			return $result;
		}

		if($res['status']=='succeeded'||$res['status']=='pending'){
			$result = ['code'=>0, 'trade_no'=>$res['id'], 'refund_fee'=>$res['refund_amt']];
		}else{
			$result = ['code'=>-1, 'msg'=>'['.$res["error_code"].']'.$res["error_msg"]];
		}
		return $result;
	}
}