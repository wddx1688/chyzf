<?php

class alipaysl_plugin
{
	static public $info = [
		'name'        => 'alipaysl', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '支付宝官方支付服务商版', //支付插件显示名称
		'author'      => '支付宝', //支付插件作者
		'link'        => 'https://b.alipay.com/signing/productSetV2.htm', //支付插件作者链接
		'types'       => ['alipay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '应用APPID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '支付宝公钥',
				'type' => 'textarea',
				'note' => '填错会导致订单无法回调，如果用公钥证书模式此处留空',
			],
			'appsecret' => [
				'name' => '应用私钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appmchid' => [
				'name' => '商户授权token',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => [ //选择已开启的支付方式
			'1' => '电脑网站支付',
			'2' => '手机网站支付',
			'3' => '当面付扫码',
			'4' => 'JS支付',
		],
		'note' => '在支付宝服务商后台进件后可获取到子商户的授权链接，子商户访问之后即可得到商户授权token', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $submit2, $conf;

		$isMobile = checkmobile();
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient')!==false && in_array('4',$channel['apptype']) && !in_array('2',$channel['apptype'])){
			return ['type'=>'jump','url'=>'/pay/jspay/'.TRADE_NO.'/?d=1'];
		}
		elseif($isMobile && (in_array('3',$channel['apptype'])||in_array('4',$channel['apptype'])) && !in_array('2',$channel['apptype']) || !$isMobile && !in_array('1',$channel['apptype'])){
			return ['type'=>'jump','url'=>'/pay/qrcode/'.TRADE_NO.'/?sitename='.$sitename];
		}else{
		
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
			if(!$submit2){
				return ['type'=>'jump','url'=>'/pay/submit/'.TRADE_NO.'/'];
			}
			return ['type'=>'page','page'=>'wxopen'];
		}
		
		if(!empty($conf['localurl_alipay']) && !strpos($conf['localurl_alipay'],$_SERVER['HTTP_HOST'])){
			return ['type'=>'jump','url'=>$conf['localurl_alipay'].'pay/submit/'.TRADE_NO.'/'];
		}
		
		if($isMobile && in_array('2',$channel['apptype'])){
			require(PAY_ROOT."inc/model/builder/AlipayTradeWapPayContentBuilder.php");
			require(PAY_ROOT."inc/AlipayTradeService.php");
		
			//构造参数
			$payRequestBuilder = new AlipayTradeWapPayContentBuilder();
			$payRequestBuilder->setSubject($ordername);
			$payRequestBuilder->setTotalAmount($order['realmoney']);
			$payRequestBuilder->setOutTradeNo(TRADE_NO);
		
			$aop = new AlipayTradeService($config);
			$html = $aop->wapPay($payRequestBuilder);
			return ['type'=>'html','data'=>$html];
		}else{
			require(PAY_ROOT."inc/model/builder/AlipayTradePagePayContentBuilder.php");
			require(PAY_ROOT."inc/AlipayTradeService.php");
		
			//构造参数
			$payRequestBuilder = new AlipayTradePagePayContentBuilder();
			$payRequestBuilder->setSubject($ordername);
			$payRequestBuilder->setTotalAmount($order['realmoney']);
			$payRequestBuilder->setOutTradeNo(TRADE_NO);
		
			$aop = new AlipayTradeService($config);
			$html = $aop->pagePay($payRequestBuilder);
			return ['type'=>'html','data'=>$html];
		}
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		if($mdevice=='alipay' && in_array('4',$channel['apptype']) && !in_array('2',$channel['apptype'])){
			return ['type'=>'jump','url'=>$siteurl.'pay/jspay/'.TRADE_NO.'/?d=1'];
		}
		elseif($device=='mobile' && (in_array('3',$channel['apptype'])||in_array('4',$channel['apptype'])) && !in_array('2',$channel['apptype']) || $device=='pc' && !in_array('1',$channel['apptype'])){
			return self::qrcode();
		}else{
		
		if(!empty($conf['localurl_alipay']) && !strpos($conf['localurl_alipay'],$_SERVER['HTTP_HOST'])){
			return ['type'=>'jump','url'=>$conf['localurl_alipay'].'pay/submit/'.TRADE_NO.'/'];
		}else{
			return ['type'=>'jump','url'=>$siteurl.'pay/submit/'.TRADE_NO.'/'];
		}
		}
	}

	//扫码支付
	static public function qrcode(){
		global $siteurl, $channel, $order, $ordername, $conf;
		if(!in_array('3',$channel['apptype']) && in_array('2',$channel['apptype'])){
			$code_url = $siteurl.'pay/submit/'.TRADE_NO.'/';
		}elseif(!in_array('3',$channel['apptype']) && !in_array('2',$channel['apptype']) && in_array('4',$channel['apptype'])){
			$code_url = $siteurl.'pay/jspay/'.TRADE_NO.'/';
		}else{
		
		require(PAY_ROOT."inc/model/builder/AlipayTradePrecreateContentBuilder.php");
		require(PAY_ROOT."inc/AlipayTradeService.php");
		
		// 创建请求builder，设置请求参数
		$qrPayRequestBuilder = new AlipayTradePrecreateContentBuilder();
		$qrPayRequestBuilder->setOutTradeNo(TRADE_NO);
		$qrPayRequestBuilder->setTotalAmount($order['realmoney']);
		$qrPayRequestBuilder->setSubject($ordername);
		
		// 调用qrPay方法获取当面付应答
		$qrPay = new AlipayTradeService($config);
		try{
			$qrPayResult = $qrPay->qrPay($qrPayRequestBuilder);
		}catch(Exception $e){
			return ['type'=>'error','msg'=>'支付宝接口请求失败！'.$e->getMessage()];
		}
		
		//	根据状态值进行业务处理
		$status = $qrPayResult->getTradeStatus();
		$response = $qrPayResult->getResponse();
		if($status == 'SUCCESS'){
			$code_url = $response->qr_code;
		}elseif($status == 'FAILED'){
			return ['type'=>'error','msg'=>'支付宝创建订单失败！['.$response->sub_code.']'.$response->sub_msg];
		}else{
			//print_r($response);
			return ['type'=>'error','msg'=>'系统异常，状态未知！'];
		}
		}
		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//JS支付
	static public function jspay(){
		global $siteurl, $channel, $order, $ordername, $conf;
		
		require(PAY_ROOT."inc/model/builder/AlipayTradeCreateContentBuilder.php");
		require(PAY_ROOT."inc/AlipayOauthService.php");
		require(PAY_ROOT."inc/AlipayTradeService.php");

		$config['redirect_uri'] = (is_https() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$oauth = new AlipayOauthService($config);
		if(isset($_GET['auth_code'])){
			$result = $oauth->getToken($_GET['auth_code']);
			if($result['user_id']){
				$openid = $result['user_id'];
			}else{
				return ['type'=>'error','msg'=>'支付宝快捷登录失败！['.$result['sub_code'].']'.$result['sub_msg']];
			}
		}else{
			$oauth->oauth();
		}
		
		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks) return $blocks;

		// 创建请求builder，设置请求参数
		$qrPayRequestBuilder = new AlipayTradeCreateContentBuilder();
		$qrPayRequestBuilder->setOutTradeNo(TRADE_NO);
		$qrPayRequestBuilder->setTotalAmount($order['realmoney']);
		$qrPayRequestBuilder->setSubject($ordername);
		$qrPayRequestBuilder->setBuyerId($openid);

		// 调用qrPay方法获取当面付应答
		$qrPay = new AlipayTradeService($config);
		try{
			$qrPayResult = $qrPay->create($qrPayRequestBuilder);
		}catch(Exception $e){
			return ['type'=>'error','msg'=>'支付宝接口请求失败！'.$e->getMessage()];
		}

		//	根据状态值进行业务处理
		$status = $qrPayResult->getTradeStatus();
		$response = $qrPayResult->getResponse();
		if($status == 'SUCCESS'){
			$alipay_trade_no = $response->trade_no;
		}elseif($status == 'FAILED'){
			return ['type'=>'error','msg'=>'支付宝创建订单失败！['.$response->sub_code.']'.$response->sub_msg];
		}else{
			//print_r($response);
			return ['type'=>'error','msg'=>'系统异常，状态未知！'];
		}

		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'alipay_jspay','data'=>['alipay_trade_no'=>$alipay_trade_no, 'redirect_url'=>$redirect_url]];
	}

	//支付成功页面
	static public function ok(){
		return ['type'=>'page','page'=>'ok'];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/AlipayTradeService.php");

		//计算得出通知验证结果
		$alipaySevice = new AlipayTradeService($config); 
		//$alipaySevice->writeLog(var_export($_POST,true));
		$verify_result = $alipaySevice->check($_POST);

		if($verify_result) {//验证成功
			//商户订单号
			$out_trade_no = daddslashes($_POST['out_trade_no']);

			//支付宝交易号
			$trade_no = daddslashes($_POST['trade_no']);

			//买家支付宝
			$buyer_id = daddslashes($_POST['buyer_id']);

			//交易金额
			$total_amount = $_POST['total_amount'];

			if($_POST['trade_status'] == 'TRADE_FINISHED') {
				//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
			}
			else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
				if($out_trade_no == TRADE_NO && round($total_amount,2)==round($order['realmoney'],2)){
					processNotify($order, $trade_no, $buyer_id);
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

		require(PAY_ROOT."inc/AlipayTradeService.php");

		//计算得出通知验证结果
		$alipaySevice = new AlipayTradeService($config); 
		//$alipaySevice->writeLog(var_export($_POST,true));
		$verify_result = $alipaySevice->check($_GET);

		if($verify_result) {//验证成功
			//商户订单号
			$out_trade_no = daddslashes($_GET['out_trade_no']);

			//支付宝交易号
			$trade_no = daddslashes($_GET['trade_no']);

			//交易金额
			$total_amount = $_GET['total_amount'];

			if($out_trade_no == TRADE_NO && round($total_amount,2)==round($order['realmoney'],2)){
				processReturn($order, $trade_no);
			}else{
				return ['type'=>'error','msg'=>'订单信息校验失败'];
			}
		}
		else {
			//验证失败
			return ['type'=>'error','msg'=>'支付宝返回验证失败'];
		}
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT."inc/model/builder/AlipayTradeRefundContentBuilder.php");
		require(PAY_ROOT."inc/AlipayTradeService.php");

		// 创建请求builder，设置请求参数
		$requestBuilder = new AlipayTradeRefundContentBuilder();
		$requestBuilder->setTradeNo($order['api_trade_no']);
		$requestBuilder->setRefundAmount($order['refundmoney']);

		// 调用退款接口
		$trade = new AlipayTradeService($config);
		try{
			$refundResult = $trade->refund($requestBuilder);
		}catch(Exception $e){
			return ['code'=>-1, 'msg'=>'支付宝接口请求失败！'.$e->getMessage()];
		}

		//	根据状态值进行业务处理
		$status = $refundResult->getTradeStatus();
		$response = $refundResult->getResponse();
		if($status == 'SUCCESS'){
			$result = ['code'=>0, 'trade_no'=>$response->trade_no, 'refund_fee'=>$response->refund_fee, 'refund_time'=>$response->gmt_refund_pay, 'buyer'=>$response->buyer_user_id];
		}elseif($status == 'FAILED'){
			$result = ['code'=>-1, 'msg'=>'['.$response->sub_code.']'.$response->sub_msg];
		}else{
			$result = ['code'=>-1, 'msg'=>'未知错误'];
		}
		return $result;
	}

	//支付宝风险交易回调
	static public function appgw(){
		global $channel,$DB;
		require PAY_ROOT."inc/AlipaySecurityService.php";
		$alipaySevice = new AlipaySecurityService($config);
		$alipaySevice->writeLog(var_export($_POST,true));
		$verify_result = $alipaySevice->check($_POST);
		if($verify_result){
			if($_POST['service']=='alipay.adatabus.risk.end.push'){
				if($_POST['charset'] == 'GBK'){
					$_POST['risktype'] = mb_convert_encoding($_POST['risktype'], "UTF-8", "GBK");
					$_POST['risklevel'] = mb_convert_encoding($_POST['risklevel'], "UTF-8", "GBK");
					$_POST['riskDesc'] = mb_convert_encoding($_POST['riskDesc'], "UTF-8", "GBK");
					$_POST['complainText'] = mb_convert_encoding($_POST['complainText'], "UTF-8", "GBK");
				}
				$DB->exec("INSERT INTO `pre_alipayrisk` (`channel`,`pid`,`smid`,`tradeNos`,`risktype`,`risklevel`,`riskDesc`,`complainTime`,`complainText`,`date`,`status`) VALUES (:channel, :pid, :smid, :tradeNos, :risktype, :risklevel, :riskDesc, :complainTime, :complainText, NOW(), 0)", [':channel'=>$channelid, ':pid'=>$_POST['pid'], ':smid'=>$_POST['smid'], ':tradeNos'=>$_POST['tradeNos'], ':risktype'=>$_POST['risktype'], ':risklevel'=>$_POST['risklevel'], ':riskDesc'=>$_POST['riskDesc'], ':complainTime'=>$_POST['complainTime'], ':complainText'=>$_POST['complainText']]);
			}
			return ['type'=>'html','data'=>'success'];
		}else{
			return ['type'=>'html','data'=>'check sign fail'];
		}
	}
}