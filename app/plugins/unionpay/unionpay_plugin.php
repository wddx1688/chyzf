<?php

class unionpay_plugin
{
	static public $info = [
		'name'        => 'unionpay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '银联扫码支付', //支付插件显示名称
		'author'      => '银联', //支付插件作者
		'link'        => 'http://www.95516.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','qqpay','bank','jdpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
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
				'name' => '自定义网关URL',
				'type' => 'input',
				'note' => '可不填,默认是https://qra.95516.com/pay/gateway',
			],
			'appswitch' => [
				'name' => '微信是否支持H5',
				'type' => 'select',
				'options' => [0=>'否',1=>'是'],
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
		}elseif($order['typename']=='qqpay'){
			return ['type'=>'jump','url'=>'/pay/qqpay/'.TRADE_NO.'/?sitename='.$sitename];
		}elseif($order['typename']=='jdpay'){
			return ['type'=>'jump','url'=>'/pay/jdpay/'.TRADE_NO.'/?sitename='.$sitename];
		}elseif($order['typename']=='bank'){
			return ['type'=>'jump','url'=>'/pay/bank/'.TRADE_NO.'/?sitename='.$sitename];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat'){
                if ($channel['appwxmp']>0) {
					return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/?d=1'];
                }else{
					return self::wxjspay();
				}
			}elseif($device=='mobile'){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='qqpay'){
			return self::qqpay();
		}elseif($order['typename']=='jdpay'){
			return self::jdpay();
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	//扫码通用
	static private function nativepay(){
		global $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT.'inc/class/Utils.class.php');
		require(PAY_ROOT.'inc/config.php');
		require(PAY_ROOT.'inc/class/RequestHandler.class.php');
		require(PAY_ROOT.'inc/class/ClientResponseHandler.class.php');
		require(PAY_ROOT.'inc/class/PayHttpClient.class.php');

		$resHandler = new ClientResponseHandler();
		$reqHandler = new RequestHandler();
		$pay = new PayHttpClient();
		$cfg = new Config();

		$reqHandler->setGateUrl($cfg->C('url'));
		$reqHandler->setSignType($cfg->C('sign_type'));
		$reqHandler->setKey($cfg->C('key'));
		$reqHandler->setParameter('service','unified.trade.native');//接口类型
		$reqHandler->setParameter('mch_id',$cfg->C('mchId'));//必填项，商户号，由平台分配
		$reqHandler->setParameter('version',$cfg->C('version'));
		$reqHandler->setParameter('sign_type',$cfg->C('sign_type'));
		$reqHandler->setParameter('body',$ordername);
		$reqHandler->setParameter('total_fee',strval($order['realmoney']*100));
		$reqHandler->setParameter('mch_create_ip',$clientip);
		$reqHandler->setParameter('out_trade_no',TRADE_NO);
		$reqHandler->setParameter('notify_url',$conf['localurl'].'pay/notify/'.TRADE_NO.'/');
		$reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
		$reqHandler->createSign();//创建签名

		$data = Utils::toXml($reqHandler->getAllParameters());
		//var_dump($data);

		$pay->setReqContent($reqHandler->getGateURL(),$data);
		if($pay->call()){
			$resHandler->setContent($pay->getResContent());
			$resHandler->setKey($cfg->C('key'));
			if($resHandler->isTenpaySign()){
				//当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
				if($resHandler->getParameter('status') == 0 && $resHandler->getParameter('result_code') == 0){
					$code_url = $resHandler->getParameter('code_url');
					if(strpos($code_url,'myun.tenpay.com')){
						$qrcode=explode('&t=',$code_url);
						$code_url = 'https://qpay.qq.com/qr/'.$qrcode[1];
					}
					return $code_url;
				}elseif($resHandler->getParameter('status') == 0){
					throw new Exception('['.$resHandler->getParameter('err_code').']'.$resHandler->getParameter('err_msg'));
				}else{
					throw new Exception('['.$resHandler->getParameter('status').']'.$resHandler->getParameter('message'));
				}
			}else{
				if ($resHandler->getParameter('status') > 0) {
					throw new Exception('['.$resHandler->getParameter('status').']'.$resHandler->getParameter('message'));
                }else{
					throw new Exception('返回内容签名校验失败');
				}
			}
		}else{
			throw new Exception('['.$pay->getResponseCode().']'.$pay->getErrInfo());
		}
	}

	//微信JS支付
	static private function weixinjspay($sub_appid, $sub_openid, $is_minipg = 0){
		global $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT.'inc/class/Utils.class.php');
		require(PAY_ROOT.'inc/config.php');
		require(PAY_ROOT.'inc/class/RequestHandler.class.php');
		require(PAY_ROOT.'inc/class/ClientResponseHandler.class.php');
		require(PAY_ROOT.'inc/class/PayHttpClient.class.php');

		$resHandler = new ClientResponseHandler();
		$reqHandler = new RequestHandler();
		$pay = new PayHttpClient();
		$cfg = new Config();

		$reqHandler->setGateUrl($cfg->C('url'));
		$reqHandler->setSignType($cfg->C('sign_type'));
		$reqHandler->setKey($cfg->C('key'));
		$reqHandler->setParameter('service','pay.weixin.jspay');//接口类型
		$reqHandler->setParameter('mch_id',$cfg->C('mchId'));//必填项，商户号，由平台分配
		$reqHandler->setParameter('version',$cfg->C('version'));
		$reqHandler->setParameter('sign_type',$cfg->C('sign_type'));
		$reqHandler->setParameter('is_raw','1');
		$reqHandler->setParameter('is_minipg',$is_minipg);
		$reqHandler->setParameter('body',$ordername);
		$reqHandler->setParameter('sub_appid',$sub_appid);
		$reqHandler->setParameter('sub_openid',$sub_openid);
		$reqHandler->setParameter('total_fee',strval($order['realmoney']*100));
		$reqHandler->setParameter('mch_create_ip',$clientip);
		$reqHandler->setParameter('out_trade_no',TRADE_NO);
		$reqHandler->setParameter('device_info', 'AND_WAP');//应用类型
		$reqHandler->setParameter('notify_url',$conf['localurl'].'pay/notify/'.TRADE_NO.'/');
		$reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
		$reqHandler->createSign();//创建签名

		$data = Utils::toXml($reqHandler->getAllParameters());
		//var_dump($data);

		$pay->setReqContent($reqHandler->getGateURL(),$data);
		if($pay->call()){
			$resHandler->setContent($pay->getResContent());
			$resHandler->setKey($cfg->C('key'));
			if($resHandler->isTenpaySign()){
				//当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
				if($resHandler->getParameter('status') == 0 && $resHandler->getParameter('result_code') == 0){
					$pay_info = $resHandler->getParameter('pay_info');
					return $pay_info;
				}elseif($resHandler->getParameter('status') == 0){
					throw new Exception('['.$resHandler->getParameter('err_code').']'.$resHandler->getParameter('err_msg'));
				}else{
					throw new Exception('['.$resHandler->getParameter('status').']'.$resHandler->getParameter('message'));
				}
			}else{
				if ($resHandler->getParameter('status') > 0) {
					throw new Exception('['.$resHandler->getParameter('status').']'.$resHandler->getParameter('message'));
                }else{
					throw new Exception('返回内容签名校验失败');
				}
			}
		}else{
			throw new Exception('['.$pay->getResponseCode().']'.$pay->getErrInfo());
		}
	}

	//微信H5支付
	static private function weixinh5pay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip, $sitename;

		require(PAY_ROOT.'inc/class/Utils.class.php');
		require(PAY_ROOT.'inc/config.php');
		require(PAY_ROOT.'inc/class/RequestHandler.class.php');
		require(PAY_ROOT.'inc/class/ClientResponseHandler.class.php');
		require(PAY_ROOT.'inc/class/PayHttpClient.class.php');

		$resHandler = new ClientResponseHandler();
		$reqHandler = new RequestHandler();
		$pay = new PayHttpClient();
		$cfg = new Config();

		$reqHandler->setGateUrl($cfg->C('url'));
		$reqHandler->setSignType($cfg->C('sign_type'));
		$reqHandler->setKey($cfg->C('key'));
		$reqHandler->setParameter('service','pay.weixin.wappay');//接口类型
		$reqHandler->setParameter('mch_id',$cfg->C('mchId'));//必填项，商户号，由平台分配
		$reqHandler->setParameter('version',$cfg->C('version'));
		$reqHandler->setParameter('sign_type',$cfg->C('sign_type'));
		$reqHandler->setParameter('body',$ordername);
		$reqHandler->setParameter('total_fee',strval($order['realmoney']*100));
		$reqHandler->setParameter('mch_create_ip',$clientip);
		$reqHandler->setParameter('out_trade_no',TRADE_NO);
		$reqHandler->setParameter('device_info', 'AND_WAP');//应用类型
		$reqHandler->setParameter('mch_app_name',$sitename);//应用名 
		$reqHandler->setParameter('mch_app_id',$siteurl);//应用标识
		$reqHandler->setParameter('notify_url',$conf['localurl'].'pay/notify/'.TRADE_NO.'/');
		$reqHandler->setParameter('callback_url',$siteurl.'pay/return/'.TRADE_NO.'/');
		$reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
		$reqHandler->createSign();//创建签名

		$data = Utils::toXml($reqHandler->getAllParameters());
		//var_dump($data);

		$pay->setReqContent($reqHandler->getGateURL(),$data);
		if($pay->call()){
			$resHandler->setContent($pay->getResContent());
			$resHandler->setKey($cfg->C('key'));
			if($resHandler->isTenpaySign()){
				//当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
				if($resHandler->getParameter('status') == 0 && $resHandler->getParameter('result_code') == 0){
					$pay_info = $resHandler->getParameter('pay_info');
					return $pay_info;
				}elseif($resHandler->getParameter('status') == 0){
					throw new Exception('['.$resHandler->getParameter('err_code').']'.$resHandler->getParameter('err_msg'));
				}else{
					throw new Exception('['.$resHandler->getParameter('status').']'.$resHandler->getParameter('message'));
				}
			}else{
				if ($resHandler->getParameter('status') > 0) {
					throw new Exception('['.$resHandler->getParameter('status').']'.$resHandler->getParameter('message'));
                }else{
					throw new Exception('返回内容签名校验失败');
				}
			}
		}else{
			throw new Exception('['.$pay->getResponseCode().']'.$pay->getErrInfo());
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::nativepay();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败 '.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		try{
			$code_url = self::nativepay();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败 '.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
	}

	//QQ扫码支付
	static public function qqpay(){
		try{
			$code_url = self::nativepay();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'QQ钱包支付下单失败 '.$ex->getMessage()];
		}

		if(checkmobile()==true && !isset($_GET['qrcode'])){
			return ['type'=>'qrcode','page'=>'qqpay_wap','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'qqpay_qrcode','url'=>$code_url];
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::nativepay();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败 '.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//京东扫码支付
	static public function jdpay(){
		try{
			$code_url = self::nativepay();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'京东支付下单失败 '.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'jdpay_qrcode','url'=>$code_url];
	}


	//微信公众号支付
	static public function wxjspay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		if($channel['appwxmp']>0){
			$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
			if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];

			$tools = new \lib\wechat\JsApiPay($wxinfo['appid'], $wxinfo['appsecret']);
			$openId = $tools->GetOpenid();
			if(!$openId)return ['type'=>'error','msg'=>'OpenId获取失败('.$tools->data['errmsg'].')'];
			$blocks = checkBlockUser($openId, TRADE_NO);
			if($blocks) return $blocks;

			try{
				$pay_info = self::weixinjspay($wxinfo['appid'], $openId);
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败 '.$ex->getMessage()];
			}

			if($_GET['d']=='1'){
				$redirect_url='data.backurl';
			}else{
				$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
			}
			return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>$pay_info, 'redirect_url'=>$redirect_url]];
		}else{
			$code_url = self::nativepay();
			return ['type'=>'jump','url'=>$code_url];
		}
	}

	//微信小程序支付
	static public function wxminipay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		$code = isset($_GET['code'])?trim($_GET['code']):exit('{"code":-1,"msg":"code不能为空"}');

		$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
		if(!$wxinfo)exit('{"code":-1,"msg":"支付通道绑定的微信小程序不存在"}');

		$tools = new \lib\wechat\MiniAppPay($wxinfo['appid'], $wxinfo['appsecret']);
		$openId = $tools->GetOpenid($code);
		if(!$openId)exit('{"code":-1,"msg":"OpenId获取失败('.$tools->data['errmsg'].')"}');
		$blocks = checkBlockUser($openId, TRADE_NO);
		if($blocks)exit('{"code":-1,"msg":"'.$blocks['msg'].'"}');

		try{
			$pay_info = self::weixinjspay($wxinfo['appid'], $openId, '1');
		}catch(Exception $ex){
			exit(json_encode(['code'=>-1, 'msg'=>'微信支付下单失败 '.$ex->getMessage()]));
		}

		exit(json_encode(['code'=>0, 'data'=>json_decode($pay_info, true)]));
	}

	//微信手机支付
	static public function wxwappay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		if($channel['appswitch']==1){
			try{
				$pay_info = self::weixinh5pay();
				return ['type'=>'jump','url'=>$pay_info];
			}catch(Exception $ex){
				return ['type'=>'error','msg'=>'微信支付下单失败 '.$ex->getMessage()];
			}
		}elseif($channel['appwxa']>0){
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

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT.'inc/class/Utils.class.php');
		require(PAY_ROOT.'inc/config.php');
		require(PAY_ROOT.'inc/class/ClientResponseHandler.class.php');

		$resHandler = new ClientResponseHandler();
		$cfg = new Config();

		$xml = file_get_contents('php://input');

		$resHandler->setContent($xml);

		$resHandler->setKey($cfg->C('key'));
		if($resHandler->isTenpaySign()){
			
			if($resHandler->getParameter('status') == 0 && $resHandler->getParameter('result_code') == 0){
				$transaction_id = $resHandler->getParameter('transaction_id');
				$out_trade_no = $resHandler->getParameter('out_trade_no');
				$total_fee = $resHandler->getParameter('total_fee');
				$fee_type = $resHandler->getParameter('fee_type');
				$openid = $resHandler->getParameter('openid');
				if($out_trade_no == TRADE_NO && $total_fee==strval($order['realmoney']*100)){
					processNotify($order, $transaction_id, $openid);
				}
				return ['type'=>'html','data'=>'success'];
			}else{
				return ['type'=>'html','data'=>'failure1'];
			}
		}else{
			return ['type'=>'html','data'=>'failure2'];
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

		require(PAY_ROOT.'inc/class/Utils.class.php');
		require(PAY_ROOT.'inc/config.php');
		require(PAY_ROOT.'inc/class/RequestHandler.class.php');
		require(PAY_ROOT.'inc/class/ClientResponseHandler.class.php');
		require(PAY_ROOT.'inc/class/PayHttpClient.class.php');

		$resHandler = new ClientResponseHandler();
		$reqHandler = new RequestHandler();
		$pay = new PayHttpClient();
		$cfg = new Config();

		$reqHandler->setGateUrl($cfg->C('url'));
		$reqHandler->setSignType($cfg->C('sign_type'));
		$reqHandler->setKey($cfg->C('key'));
		$reqHandler->setParameter('service','unified.trade.refund');//接口类型
		$reqHandler->setParameter('mch_id',$cfg->C('mchId'));//必填项，商户号，由平台分配
		$reqHandler->setParameter('version',$cfg->C('version'));
		$reqHandler->setParameter('sign_type',$cfg->C('sign_type'));
		$reqHandler->setParameter('transaction_id',$order['api_trade_no']);
		$reqHandler->setParameter('out_refund_no',TRADE_NO.'REF');
		$reqHandler->setParameter('total_fee',strval($order['realmoney']*100));
		$reqHandler->setParameter('refund_fee',strval($order['refundmoney']*100));
		$reqHandler->setParameter('op_user_id',$cfg->C('mchId'));
		$reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
		$reqHandler->createSign();//创建签名

		$data = Utils::toXml($reqHandler->getAllParameters());
		//var_dump($data);

		$pay->setReqContent($reqHandler->getGateURL(),$data);
		if($pay->call()){
			$resHandler->setContent($pay->getResContent());
			$resHandler->setKey($cfg->C('key'));
			if($resHandler->isTenpaySign()){
				//当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
				if($resHandler->getParameter('status') == 0 && $resHandler->getParameter('result_code') == 0){
					$result = ['code'=>0, 'trade_no'=>$resHandler->getParameter('refund_id'), 'refund_fee'=>$resHandler->getParameter('refund_fee')];
				}elseif($resHandler->getParameter('status') == 0){
					$result = ['code'=>-1, 'msg'=>'['.$resHandler->getParameter('err_code').']'.$resHandler->getParameter('err_msg')];
				}else{
					$result = ['code'=>-1, 'msg'=>'['.$resHandler->getParameter('status').']'.$resHandler->getParameter('message')];
				}
			}else{
				$result = ['code'=>-1, 'msg'=>'返回内容签名校验失败'];
			}
		}else{
			$result = ['code'=>-1, 'msg'=>'['.$pay->getResponseCode().']'.$pay->getErrInfo()];
		}
		return $result;
	}
}