<?php

class qqpay_plugin
{
	static public $info = [
		'name'        => 'qqpay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'QQ钱包官方支付', //支付插件显示名称
		'author'      => 'QQ钱包', //支付插件作者
		'link'        => 'https://mp.qpay.tenpay.cn/', //支付插件作者链接
		'types'       => ['qqpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => 'QQ钱包商户号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'QQ钱包API密钥',
				'type' => 'input',
				'note' => '',
			],
			'appurl' => [
				'name' => '企业付款-操作员ID',
				'type' => 'input',
				'note' => '如果不需企业付款功能可留空',
			],
			'appmchid' => [
				'name' => '企业付款-操作员密码',
				'type' => 'input',
				'note' => '如果不需企业付款功能可留空',
			],
		],
		'select' => [ //选择已开启的支付方式
			'1' => '扫码支付(包含H5)',
			'2' => '公众号支付',
		],
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $submit2, $conf;

		if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/')!==false && in_array('2',$channel['apptype'])){
			return ['type'=>'jump','url'=>'/pay/jspay/'.TRADE_NO.'/'];
		}elseif(checkmobile()==true){
			return ['type'=>'jump','url'=>'/pay/wap/'.TRADE_NO.'/?sitename='.$sitename];
		}else{
			return ['type'=>'jump','url'=>'/pay/qrcode/'.TRADE_NO.'/?sitename='.$sitename];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $device, $mdevice;

		if($mdevice=='qq' && in_array('2',$channel['apptype'])){
			return ['type'=>'jump','url'=>$siteurl.'pay/jspay/'.TRADE_NO.'/'];
		}else{
			return self::qrcode();
		}
	}

	//扫码支付
	static public function qrcode(){
		global $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT.'inc/qpayMchAPI.class.php');

		//入参
		$params = array();
		$params["out_trade_no"] = TRADE_NO;
		$params["body"] = $ordername;
		$params["fee_type"] = "CNY";
		$params["notify_url"] = $conf['localurl'].'pay/notify/'.TRADE_NO.'/';
		$params["spbill_create_ip"] = $clientip;
		$params["total_fee"] = strval($order['realmoney']*100);
		$params["trade_type"] = "NATIVE";

		//api调用
		$qpayApi = new QpayMchAPI('https://qpay.qq.com/cgi-bin/pay/qpay_unified_order.cgi', null, 10);
		$ret = $qpayApi->reqQpay($params);
		$result = QpayMchUtil::xmlToArray($ret);
		//print_r($result);

		if($result['return_code']=='SUCCESS' && $result['result_code']=='SUCCESS'){
			$code_url = $result['code_url'];
		}elseif(isset($result["err_code"])){
			return ['type'=>'error','msg'=>'QQ钱包支付下单失败！['.$result["err_code"].'] '.$result["err_code_des"]];
		}else{
			return ['type'=>'error','msg'=>'QQ钱包支付下单失败！['.$result["return_code"].'] '.$result["return_msg"]];
		}
		return ['type'=>'qrcode','page'=>'qqpay_qrcode','url'=>$code_url];
	}

	//手机支付
	static public function wap(){
		global $channel, $order, $ordername, $conf, $clientip;
		
		require(PAY_ROOT.'inc/qpayMchAPI.class.php');

		//入参
		$params = array();
		$params["out_trade_no"] = TRADE_NO;
		$params["body"] = $ordername;
		$params["fee_type"] = "CNY";
		$params["notify_url"] = $conf['localurl'].'pay/notify/'.TRADE_NO.'/';
		$params["spbill_create_ip"] = $clientip;
		$params["total_fee"] = strval($order['realmoney']*100);
		$params["trade_type"] = "NATIVE";

		//api调用
		$qpayApi = new QpayMchAPI('https://qpay.qq.com/cgi-bin/pay/qpay_unified_order.cgi', null, 10);
		$ret = $qpayApi->reqQpay($params);
		$result = QpayMchUtil::xmlToArray($ret);
		//print_r($arr);

		if($result['return_code']=='SUCCESS' && $result['result_code']=='SUCCESS'){
			$code_url = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t='.$result['prepay_id'];
		}elseif(isset($result["err_code"])){
			return ['type'=>'error','msg'=>'QQ钱包支付下单失败！['.$result["err_code"].'] '.$result["err_code_des"]];
		}else{
			return ['type'=>'error','msg'=>'QQ钱包支付下单失败！['.$result["return_code"].'] '.$result["return_msg"]];
		}
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/')!==false){
			return ['type'=>'jump','url'=>$code_url];
		}
		return ['type'=>'qrcode','page'=>'qqpay_wap','url'=>$code_url];
	}

	//JS支付
	static public function jspay(){
		global $channel, $order, $ordername, $conf, $clientip;
		
		require(PAY_ROOT.'inc/qpayMchAPI.class.php');

		//入参
		$params = array();
		$params["out_trade_no"] = TRADE_NO;
		$params["body"] = $ordername;
		$params["fee_type"] = "CNY";
		$params["notify_url"] = $conf['localurl'].'pay/notify/'.TRADE_NO.'/';
		$params["spbill_create_ip"] = $clientip;
		$params["total_fee"] = strval($order['realmoney']*100);
		$params["trade_type"] = "JSAPI";

		//api调用
		$qpayApi = new QpayMchAPI('https://qpay.qq.com/cgi-bin/pay/qpay_unified_order.cgi', null, 10);
		$ret = $qpayApi->reqQpay($params);
		$result = QpayMchUtil::xmlToArray($ret);
		//print_r($result);

		if($result['return_code']=='SUCCESS' && $result['result_code']=='SUCCESS'){
			$prepay_id = $result['prepay_id'];
		}elseif(isset($result["err_code"])){
			return ['type'=>'error','msg'=>'QQ钱包支付下单失败！['.$result["err_code"].'] '.$result["err_code_des"]];
		}else{
			return ['type'=>'error','msg'=>'QQ钱包支付下单失败！['.$result["return_code"].'] '.$result["return_msg"]];
		}
		return ['type'=>'page','page'=>'qqpay_jspay','data'=>['prepay_id'=>$prepay_id, 'mchappid'=>QpayMchConf::MCH_APPID, 'mchid'=>QpayMchConf::MCH_ID]];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT.'inc/qpayNotify.class.php');

		$qpayNotify = new QpayNotify();
		$result = $qpayNotify->getParams();
		//判断签名
		if($qpayNotify->verifySign()) { //判断签名及结果（即时到帐）
			if($result['trade_state'] == "SUCCESS") {
				//商户订单号
				$out_trade_no = daddslashes($result['out_trade_no']);
				//QQ钱包订单号
				$transaction_id = daddslashes($result['transaction_id']);
				//金额,以分为单位
				$total_fee = $result['total_fee'];
				//币种
				$fee_type = $result['fee_type'];
				//用户标识
				$openid = daddslashes($result['openid']);

				//------------------------------
				//处理业务开始
				//------------------------------
				if($out_trade_no == TRADE_NO && $total_fee==strval($order['realmoney']*100)){
					processNotify($order, $transaction_id, $openid);
				}
				//------------------------------
				//处理业务完毕
				//------------------------------
				return ['type'=>'html','data'=>'<xml><return_code>SUCCESS</return_code></xml>'];
			} else {
				return ['type'=>'html','data'=>'<xml><return_code>FAIL</return_code></xml>'];
			}

		} else {
			//回调签名错误
			return ['type'=>'html','data'=>'<xml><return_code>FAIL</return_code><return_msg>sign error</return_msg></xml>'];
		}
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT.'inc/qpayMchAPI.class.php');

		//入参
		$params = array();
		$params["transaction_id"] = $order['api_trade_no'];
		$params["out_refund_no"] = $order['trade_no'];
		$params["refund_fee"] = strval($order['refundmoney']*100);
		$params["op_user_id"] = QpayMchConf::OP_USERID;
		$params["op_user_passwd"] = md5(QpayMchConf::OP_USERPWD);

		//api调用
		$qpayApi = new QpayMchAPI('https://api.qpay.qq.com/cgi-bin/pay/qpay_refund.cgi', true, 10);
		$ret = $qpayApi->reqQpay($params);
		$result = QpayMchUtil::xmlToArray($ret);
		//print_r($result);

		if($result['return_code']=='SUCCESS' && $result['result_code']=='SUCCESS'){
			$result = ['code'=>0, 'trade_no'=>$result['transaction_id'], 'refund_fee'=>$result['total_fee']];
		}elseif(isset($result["err_code"])){
			$result = ['code'=>-1, 'msg'=>'['.$result["err_code"].']'.$result["err_code_des"]];
		}else{
			$result = ['code'=>-1, 'msg'=>'['.$result["return_code"].']'.$result["return_msg"]];
		}
		return $result;
	}
}