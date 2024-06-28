<?php

class aliold_plugin
{
	static public $info = [
		'name'        => 'aliold', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '支付宝旧版接口', //支付插件显示名称
		'author'      => '支付宝', //支付插件作者
		'link'        => 'https://b.alipay.com/signing/productSetV2.htm', //支付插件作者链接
		'types'       => ['alipay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '合作者身份(PID)',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '安全校验码(Key)',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => [ //选择已开启的支付方式
			'1' => '电脑网站支付',
			'2' => '手机网站支付',
		],
		'note' => '选择可用的接口，只能选择已经签约的产品，否则会无法支付。', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $submit2, $conf;

		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
			if(!$submit2){
				return ['type'=>'jump','url'=>'/pay/submit/'.TRADE_NO.'/'];
			}
			return ['type'=>'page','page'=>'wxopen'];
		}
		if(!empty($conf['localurl_alipay']) && !strpos($conf['localurl_alipay'],$_SERVER['HTTP_HOST'])){
			return ['type'=>'jump','url'=>$conf['localurl_alipay'].'pay/submit/'.TRADE_NO.'/'];
		}

		require(PAY_ROOT."inc/alipay.config.php");
		require(PAY_ROOT."inc/alipay_submit.class.php");

		if(checkmobile()==true && in_array('2',$channel['apptype'])){
			$alipay_service = "alipay.wap.create.direct.pay.by.user";
		}else{
			$alipay_service = "create_direct_pay_by_user";
		}
		$parameter = array(
			"service" => $alipay_service,
			"partner" => trim($alipay_config['partner']), //合作身份者id
			"seller_id" => trim($alipay_config['partner']), //收款支付宝用户号
			"payment_type"	=> "1", //支付方式
			"notify_url"	=> $conf['localurl'].'pay/notify/'.TRADE_NO.'/', //服务器异步通知页面路径
			"return_url"	=> $siteurl.'pay/return/'.TRADE_NO.'/', //页面跳转同步通知页面路径
			"out_trade_no"	=> TRADE_NO, //商户订单号
			"subject"	=> $ordername, //订单名称
			"total_fee"	=> $order['realmoney'], //付款金额
			"_input_charset"	=> strtolower('utf-8')
		);
		if($alipay_service=="alipay.wap.create.direct.pay.by.user"){
			$parameter['app_pay'] = "Y";
		}

		//建立请求
		$alipaySubmit = new AlipaySubmit($alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "正在跳转");
		return ['type'=>'html','data'=>$html_text];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/alipay.config.php");
		require(PAY_ROOT."inc/alipay_notify.class.php");

		//计算得出通知验证结果
		$alipayNotify = new AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();

		if($verify_result) {//验证成功
			//商户订单号
			$out_trade_no = daddslashes($_POST['out_trade_no']);

			//支付宝交易号
			$trade_no = daddslashes($_POST['trade_no']);

			//买家支付宝
			$buyer_id = daddslashes($_POST['buyer_id']);

			//交易金额
			$total_fee = $_POST['total_fee'];

			if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
				//付款完成后，支付宝系统发送该交易状态通知
				if($out_trade_no == TRADE_NO && round($total_fee,2)==round($order['realmoney'],2)){
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

		require(PAY_ROOT."inc/alipay.config.php");
		require(PAY_ROOT."inc/alipay_notify.class.php");

		//计算得出通知验证结果
		$alipayNotify = new AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyReturn();
		if($verify_result) {
			//商户订单号
			$out_trade_no = daddslashes($_GET['out_trade_no']);

			//支付宝交易号
			$trade_no = daddslashes($_GET['trade_no']);

			//买家支付宝
			$buyer_id = daddslashes($_GET['buyer_id']);

			//交易金额
			$total_fee = $_GET['total_fee'];

			if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
				if($out_trade_no == TRADE_NO && round($total_fee,2)==round($order['realmoney'],2)){
                    processReturn($order, $trade_no, $buyer_id);
                }else{
					return ['type'=>'error','msg'=>'订单信息校验失败'];
				}
			}else{
				return ['type'=>'error','msg'=>'trade_status='.$_GET['trade_status']];
			}
		}
		else {
			//验证失败
			return ['type'=>'error','msg'=>'支付宝返回验证失败！'];
		}
	}

}