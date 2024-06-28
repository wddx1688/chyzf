<?php

class zhangyishou_plugin
{
	static public $info = [
		'name'        => 'zhangyishou', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '掌易收聚合支付', //支付插件显示名称
		'author'      => '掌易收', //支付插件作者
		'link'        => 'http://www.zhangyishou.com/', //支付插件作者链接
		'types'       => ['alipay','qqpay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '登录账号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户密钥',
				'type' => 'input',
				'note' => '',
			],
			'appurl' => [
				'name' => '商户编号',
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
		'note' => '如果微信通道有扫码和小程序2种，直接在通道ID填写2个ID，用|隔开', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		return ['type'=>'jump','url'=>'/pay/'.$order['typename'].'/'.TRADE_NO.'/?sitename='.$sitename];
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		$typename = $order['typename'];
		return self::$typename();
	}

	//通用扫码
	static public function qrcode($type){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		session_start();

		require(PAY_ROOT."inc/config.php");
		$getwayurl = 'https://apipay.zhangyishou.com/api/Order/AddOrder';
		$params = [
			'MerchantId' => $pay_config['MerchantId'],
			'DownstreamOrderNo' => TRADE_NO,
			'OrderTime' => date('Y-m-d H:i:s'),
			'PayChannelId' => $pay_config['PayChannelId'],
			'AsynPath' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'OrderMoney' => sprintf("%.2f",$order['realmoney']),
			'IPPath' => $clientip,
		];

		$signStr = "";
		foreach($params as $row){
			$signStr .= $row;
		}
		$signStr .= $pay_config['key'];
		$params['MD5Sign'] = md5($signStr);
		$params['MerchantNo'] = $pay_config['MerchantNo'];
		$params['Mproductdesc'] = $ordername;
		if($type == 'qqpay' && strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/')!==false || $type == 'wxpay' && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
			$params['ReturnUrl'] = $siteurl.'pay/return/'.TRADE_NO.'/';
		}

		if($_SESSION[TRADE_NO.'_pay']){
			$data = $_SESSION[TRADE_NO.'_pay'];
		}else{
			$data = zz_get_curl($getwayurl, json_encode($params));
			$_SESSION[TRADE_NO.'_pay'] = $data;
		}

		$result = json_decode($data, true);

		if($result['Code']=='1009'){
			$code_url = $result['Info'];
		}else{
			//echo json_encode($params);
			throw new Exception('['.$result['Code'].']'.$result['Message'].':'.$result['Info']);
		}

		return $code_url;
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::qrcode('alipay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $device, $mdevice;

		if(strpos($channel['appmchid'],'|')){
			$appmchid = explode('|',$channel['appmchid']);
			$channel['appmchid'] = $appmchid[0];
            if (checkmobile() && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')===false || $device=='mobile' && $mdevice!='wechat') {
                $channel['appmchid'] = $appmchid[1];
				$isscheme = true;
            }
		}

		try{
			$code_url = self::qrcode('wxpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}
		
		if($isscheme){
			return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
		} elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
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
			$code_url = self::qrcode('qqpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'QQ钱包支付下单失败！'.$ex->getMessage()];
		}

		if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/')!==false){
			return ['type'=>'jump','url'=>$code_url];
		} elseif(checkmobile() && !isset($_GET['qrcode'])){
			return ['type'=>'qrcode','page'=>'qqpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'qqpay_qrcode','url'=>$code_url];
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::qrcode('bank');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/config.php");
		$json = file_get_contents("php://input");
		$data = json_decode($json, true);

		$signStr = $data['MerchantId'].$data['DownstreamOrderNo'].$pay_config['key'];
		$sign = md5($signStr);

		if($sign === $data['Signature']){
			if($data['OrderState'] == 1){
				$trade_no = $data['OrderNo'];
				if($data['DownstreamOrderNo'] == TRADE_NO && round($data['OrderMoney'],2)==round($order['realmoney'],2)){
					processNotify($order, $trade_no);
				}
				return ['type'=>'html','data'=>'OK'];
			}
		}
		return ['type'=>'html','data'=>'ERROR'];
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT."inc/config.php");
		$getwayurl = 'https://apipay.zhangyishou.com/api/OrderRefund/Refund';
		$params = [
			'MerchantId' => $pay_config['MerchantId'],
			'MerchantOrderNo' => TRADE_NO,
			'RefundAmount' => sprintf("%.2f",$order['refundmoney']),
		];

		$signStr = "";
		foreach($params as $row){
			$signStr .= $row;
		}
		$signStr .= $pay_config['key'];
		$params['MD5Sign'] = md5($signStr);

		$data = zz_get_curl($getwayurl, json_encode($params));

		$result = json_decode($data, true);

		if($result['Code']=='1009'){
			$result = ['code'=>0, 'trade_no'=>TRADE_NO, 'refund_fee'=>$order['refundmoney']];
		}else{
			$result = ['code'=>-1, 'msg'=>$result["Message"]];
		}
		return $result;
	}

}