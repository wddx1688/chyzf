<?php

class wxpay_plugin
{
	static public $info = [
		'name'        => 'wxpay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '微信官方支付', //支付插件显示名称
		'author'      => '微信', //支付插件作者
		'link'        => 'https://pay.weixin.qq.com/', //支付插件作者链接
		'types'       => ['wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '公众号或小程序APPID',
				'type' => 'input',
				'note' => '',
			],
			'appmchid' => [
				'name' => '商户号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户API密钥',
				'type' => 'input',
				'note' => 'APIv2密钥',
			],
		],
		'select' => [ //选择已开启的支付方式
			'1' => '扫码支付',
			'2' => '公众号支付',
			'3' => 'H5支付',
			'4' => '小程序支付',
		],
		'note' => '上方APPID填写公众号或小程序的皆可，需要在微信支付后台关联对应的公众号或小程序才能使用。无认证的公众号或小程序无法发起支付！', //支付密钥填写说明
		'bindwxmp' => true, //是否支持绑定微信公众号
		'bindwxa' => true, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $submit2, $conf;

		$urlpre = '/';
		if (!empty($conf['localurl_wxpay']) && !strpos($conf['localurl_wxpay'], $_SERVER['HTTP_HOST'])) {
			$urlpre = $conf['localurl_wxpay'];
		}
		
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
			if(in_array('2',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/jspay/'.TRADE_NO.'/?d=1'];
			}elseif(in_array('4',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/wap/'.TRADE_NO.'/'];
			}else{
				if(!$submit2){
					return ['type'=>'jump','url'=>'/pay/submit/'.TRADE_NO.'/'];
				}
				return ['type'=>'page','page'=>'wxopen'];
			}
		}elseif(checkmobile()==true){
			if(in_array('3',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/h5/'.TRADE_NO.'/'];
			}elseif(in_array('2',$channel['apptype']) || in_array('4',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/wap/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/qrcode/'.TRADE_NO.'/?sitename='.$sitename];
			}
		}else{
			return ['type'=>'jump','url'=>'/pay/qrcode/'.TRADE_NO.'/?sitename='.$sitename];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		$urlpre = $siteurl;
		if (!empty($conf['localurl_wxpay']) && !strpos($conf['localurl_wxpay'], $_SERVER['HTTP_HOST'])) {
			$urlpre = $conf['localurl_wxpay'];
		}

		if($mdevice=='wechat'){
			if(in_array('2',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/jspay/'.TRADE_NO.'/?d=1'];
			}elseif(in_array('4',$channel['apptype'])){
				return self::wap();
			}else{
				return ['type'=>'jump','url'=>$siteurl.'pay/submit/'.TRADE_NO.'/'];
			}
		}elseif($device=='mobile'){
			if(in_array('3',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/h5/'.TRADE_NO.'/'];
			}elseif(in_array('2',$channel['apptype']) || in_array('4',$channel['apptype'])){
				return self::wap();
			}else{
				return self::qrcode();
			}
		}else{
			return self::qrcode();
		}
	}

	//扫码支付
	static public function qrcode(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		if(in_array('1',$channel['apptype'])){
		require PAY_ROOT."inc/WxPay.Api.php";
		$input = new WxPayUnifiedOrder();
		$input->SetBody($ordername);
		$input->SetOut_trade_no(TRADE_NO);
		$input->SetTotal_fee(strval($order['realmoney']*100));
		$input->SetSpbill_create_ip($clientip);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetNotify_url($conf['localurl'].'pay/notify/'.TRADE_NO.'/');
		$input->SetTrade_type("NATIVE");
		$input->SetProduct_id("01001");
		$result = WxPayApi::unifiedOrder($input);
		if($result["result_code"]=='SUCCESS'){
			$code_url=$result['code_url'];
		}elseif(isset($result["err_code"])){
			return ['type'=>'error','msg'=>'微信支付下单失败！['.$result["err_code"].'] '.$result["err_code_des"]];
		}else{
			return ['type'=>'error','msg'=>'微信支付下单失败！['.$result["return_code"].'] '.$result["return_msg"]];
		}
		}elseif(in_array('2',$channel['apptype'])){
			$code_url = $siteurl.'pay/jspay/'.TRADE_NO.'/';
		}elseif(in_array('4',$channel['apptype'])){
			$code_url = $siteurl.'pay/wap/'.TRADE_NO.'/';
		}else{
			return ['type'=>'error','msg'=>'当前支付通道没有开启的支付方式'];
		}
		return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
	}

	//JS支付
	static public function jspay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;
		
		$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
		if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];
		$channel['appid'] = $wxinfo['appid'];
		$channel['appsecret'] = $wxinfo['appsecret'];
		
		session_start();
		if($_SESSION[TRADE_NO.'_wxpay']){
			$jsApiParameters=$_SESSION[TRADE_NO.'_wxpay'];
		}else{
		require PAY_ROOT."inc/WxPay.Api.php";
		require PAY_ROOT."inc/WxPay.JsApiPay.php";
		//①、获取用户openid
		$tools = new JsApiPay();
		$openId = $tools->GetOpenid();
		if(!$openId)return ['type'=>'error','msg'=>'OpenId获取失败('.$tools->data['errmsg'].')'];
		$blocks = checkBlockUser($openId, TRADE_NO);
		if($blocks) return $blocks;

		//②、统一下单
		$input = new WxPayUnifiedOrder();
		$input->SetBody($ordername);
		$input->SetOut_trade_no(TRADE_NO);
		$input->SetTotal_fee(strval($order['realmoney']*100));
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetNotify_url($conf['localurl'].'pay/notify/'.TRADE_NO.'/');
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($openId);
		$result = WxPayApi::unifiedOrder($input);
		
		if($result["result_code"]=='SUCCESS'){
			$jsApiParameters = $tools->GetJsApiParameters($result);
		}elseif(isset($result["err_code"])){
			return ['type'=>'error','msg'=>'微信支付下单失败！['.$result["err_code"].'] '.$result["err_code_des"]];
		}else{
			return ['type'=>'error','msg'=>'微信支付下单失败！['.$result["return_code"].'] '.$result["return_msg"]];
		}
		$_SESSION[TRADE_NO.'_wxpay'] = $jsApiParameters;
		}
		
		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>$jsApiParameters, 'redirect_url'=>$redirect_url]];
	}

	//手机支付
	static public function wap(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;
		
		if(in_array('4',$channel['apptype']) && !isset($_GET['qrcode'])){
			try{
				$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
				if(!$wxinfo)return ['type'=>'error','msg'=>'支付通道绑定的微信小程序不存在'];
				$access_token = wx_get_access_token($wxinfo['appid'], $wxinfo['appsecret']);
				if($access_token){
					$jump_url = $siteurl.'pay/mini/'.TRADE_NO.'/';
					$path = 'pages/pay/pay';
					$query = 'money='.$order['realmoney'].'&url='.$jump_url;
					$code_url = wxa_generate_scheme($access_token, $path, $query);
				}
			}catch(Exception $e){
				return ['type'=>'error','msg'=>$e->getMessage()];
			}
			return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
		}else{
			$code_url = $siteurl.'pay/jspay/'.TRADE_NO.'/';
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		}
	}

	//H5支付
	static public function h5(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require_once PAY_ROOT."inc/WxPay.Api.php";
		$input = new WxPayUnifiedOrder();
		$input->SetBody($ordername);
		$input->SetOut_trade_no(TRADE_NO);
		$input->SetTotal_fee(strval($order['realmoney']*100));
		$input->SetSpbill_create_ip($clientip);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetNotify_url($conf['localurl'].'pay/notify/'.TRADE_NO.'/');
		$input->SetTrade_type("MWEB");
		$result = WxPayApi::unifiedOrder($input);
		if($result["result_code"]=='SUCCESS'){
			$redirect_url=$siteurl.'pay/return/'.TRADE_NO.'/';
			$url=$result['mweb_url'].'&redirect_url='.urlencode($redirect_url);
			return ['type'=>'jump','url'=>$url];
		}elseif(isset($result["err_code"])){
			return ['type'=>'error','msg'=>'微信支付下单失败！['.$result["err_code"].'] '.$result["err_code_des"]];
		}else{
			return ['type'=>'error','msg'=>'微信支付下单失败！['.$result["return_code"].'] '.$result["return_msg"]];
		}
	}

	//小程序支付
	static public function mini(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		$code = isset($_GET['code'])?trim($_GET['code']):exit('{"code":-1,"msg":"code不能为空"}');

		$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
		if(!$wxinfo)exit('{"code":-1,"msg":"支付通道绑定的微信小程序不存在"}');
		$channel['appid'] = $wxinfo['appid'];
		$channel['appsecret'] = $wxinfo['appsecret'];
		
		require PAY_ROOT."inc/WxPay.Api.php";
		require PAY_ROOT."inc/WxPay.MiniAppPay.php";
		//①、获取用户openid
		$tools = new MiniAppPay();
		$openId = $tools->GetOpenid($code);
		if(!$openId)exit('{"code":-1,"msg":"OpenId获取失败('.$tools->data['errmsg'].')"}');
		$blocks = checkBlockUser($openId, TRADE_NO);
		if($blocks)exit('{"code":-1,"msg":"'.$blocks['msg'].'"}');

		//②、统一下单
		$input = new WxPayUnifiedOrder();
		$input->SetBody($ordername);
		$input->SetOut_trade_no(TRADE_NO);
		$input->SetTotal_fee(strval($order['realmoney']*100));
		$input->SetSpbill_create_ip($clientip);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetNotify_url($conf['localurl'].'pay/notify/'.TRADE_NO.'/');
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($openId);
		$result = WxPayApi::unifiedOrder($input);
		if($result["result_code"]=='SUCCESS'){
			$jsApiParameters = $tools->GetJsApiParameters($result);
			exit(json_encode(['code'=>0, 'data'=>$jsApiParameters]));
		}elseif(isset($result["err_code"])){
			exit(json_encode(['code'=>-1, 'msg'=>'微信支付下单失败！['.$result["err_code"].'] '.$result["err_code_des"]]));
		}else{
			exit(json_encode(['code'=>-1, 'msg'=>'微信支付下单失败！['.$result["return_code"].'] '.$result["return_msg"]]));
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

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/PayNotifyCallBack.php");

		$notify = new PayNotifyCallBack();
		$notify->Handle(false);

		return null;
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require_once PAY_ROOT."inc/WxPay.Api.php";
		try{
			$input = new WxPayRefund();
			$input->SetTransaction_id($order['api_trade_no']);
			$input->SetTotal_fee(strval($order['realmoney']*100));
			$input->SetRefund_fee(strval($order['refundmoney']*100));
			$input->SetOut_refund_no($order['trade_no']);
			$input->SetOp_user_id(WxPayConfig::MCHID);
			$result = WxPayApi::refund($input);
			if($result['return_code']=='SUCCESS' && $result['result_code']=='SUCCESS'){
				$result = ['code'=>0, 'trade_no'=>$result['transaction_id'], 'refund_fee'=>$result['refund_fee']];
			}elseif(isset($result["err_code"])){
				$result = ['code'=>-1, 'msg'=>'['.$result["err_code"].']'.$result["err_code_des"]];
			}else{
				$result = ['code'=>-1, 'msg'=>'['.$result["return_code"].']'.$result["return_msg"]];
			}
		} catch(Exception $e) {
			$result = ['code'=>-1, 'msg'=>$e->getMessage()];
		}
		return $result;
	}
}