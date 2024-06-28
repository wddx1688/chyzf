<?php

class ympay_plugin
{
	static public $info = [
		'name'        => 'ympay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '源铭SAAS平台', //支付插件显示名称
		'author'      => '源铭', //支付插件作者
		'link'        => 'https://www.xgymwl.cn/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appurl' => [
				'name' => 'API接口地址',
				'type' => 'input',
				'note' => '以http://或https://开头，以/结尾',
			],
			'appid' => [
				'name' => '应用APPID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '应用秘钥',
				'type' => 'input',
				'note' => '',
			],
			'appmchid' => [
				'name' => '通道ID',
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

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/?sitename='.$sitename];
		}elseif($order['typename']=='wxpay'){
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/?sitename='.$sitename];
			}elseif(checkmobile()==true){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/?sitename='.$sitename];
			}
		}elseif($order['typename']=='bank'){
			return ['type'=>'jump','url'=>'/pay/bank/'.TRADE_NO.'/?sitename='.$sitename];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat'){
				return self::wxpay();
			}elseif($device=='mobile'){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	static private function make_sign($param, $key){
		$param = array_filter($param);
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

	static private function getApiUrl(){
		global $channel;
		$apiurl = $channel['appurl'];
		if(substr($apiurl, -1, 1) == '/')$apiurl = substr($apiurl, 0, -1);
		return $apiurl;
	}

	static private function sendRequest($url, $param, $key){
		$url = self::getApiUrl().$url;
		$post = json_encode($param);
		$sign = self::make_sign($param, $key);
		$response = get_curl($url,$post,0,0,0,0,0,['Content-Type: application/json', 'YM-BODY-SIGN: '.$sign]);
		return json_decode($response, true);
	}

	//通用创建订单
	static private function addOrder($type, $extra = null){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		$param = [
			'appid' => $channel['appid'],
			'tradeType' => $type,
			'channelTypeId' => $channel['appmchid'],
			'outTradeNo' => TRADE_NO,
			'total' => $order['realmoney'],
			'description' => $ordername,
			'attach' => $order['typename'],
			'timestamp' => date("Y-m-d H:i:s"),
			'notifyUrl' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'returnUrl' => $siteurl.'pay/ok/'.TRADE_NO.'/',
			'clientIp' => $clientip,
			'nonce' => random(10),
			//'channeExpend' => ['is_raw' => 1]
		];
		if($extra){
			$param = array_merge($param, $extra);
		}

		$result = self::sendRequest('/v1/submitpay', $param, $channel['appkey']);

		if(isset($result["errcode"]) && $result["errcode"]==0){
			\lib\Payment::updateOrder(TRADE_NO, $result['data']["tradeNo"]);
			$code_url = $result['data']['payurl'];
		}else{
			throw new Exception($result["errmsg"]?$result["errmsg"]:'返回数据解析失败');
		}
		return $code_url;
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::addOrder('alipayQr');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		try{
			$code_url = self::addOrder('wechatQr');
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

	//微信手机支付
	static public function wxwappay(){
		try{
			$code_url = self::addOrder('wechatLiteH5');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::addOrder('unionQr');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$arr = $_POST;
		$sign = self::make_sign($arr,$channel['appkey']);

		if($sign===$arr["sign"]){
			if($arr['tradeState'] == 'SUCCESS'){
				$out_trade_no = $arr['outTradeNo'];
				$trade_no = $arr['tradeNo'];

				if ($out_trade_no == TRADE_NO) {
					processNotify($order, $trade_no);
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

	//支付成功页面
	static public function ok(){
		return ['type'=>'page','page'=>'ok'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		$param = [
			'appid' => $channel['appid'],
			'nonce' => random(10),
			'outTradeNo' => $order['trade_no'],
		];

		$result = self::sendRequest('/v1/PayRefund', $param, $channel['appkey']);

		if(isset($result["errcode"]) && $result["errcode"]==0 && $result["refund_state"]=='SUCCESS'){
			return ['code'=>0, 'trade_no'=>$order['api_trade_no'], 'refund_fee'=>$order['realmoney']];
		}else{
			return ['code'=>-1, 'msg'=>$result["errmsg"]?$result["errmsg"]:'返回数据解析失败'];
		}
	}
}