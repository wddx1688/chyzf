<?php

class xingyi_plugin
{
	static public $info = [
		'name'        => 'xingyi', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '星益云聚合收银台', //支付插件显示名称
		'author'      => '星益云', //支付插件作者
		'link'        => 'http://pay.96xy.cn/', //支付插件作者链接
		'types'       => ['alipay','qqpay','wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appurl' => [
				'name' => '接口地址',
				'type' => 'input',
				'note' => '必须以http://或https://开头，以/结尾',
			],
			'appid' => [
				'name' => '商户UID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'API密钥',
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
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		require(PAY_ROOT."inc/config.php");
		require(PAY_ROOT."inc/CashierFunction.php");
		$CashierFunction = new CashierFunction;
		$api = $config['url']."submit/";
		$params = array(
			'time' => time(),
			'mod'=>'api',
			'third_trade_no' => TRADE_NO,  //订单号
			'type' => $order['typename'],  //支付方式
			'money' => $order['realmoney'],  //支付金额
			'trade_name' => $order['name'],  //商品名称
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',  //异步通知地址
			'return_url' => $siteurl.'pay/return/'.TRADE_NO.'/'  //同步跳转地址
		);
		$url = $CashierFunction->createUrlStr(
			$params,  //参数
			true,  //需要商户验证
			$config,  //配置信息、商户信息
			$config['eliminate'],  //URL剔除字段
			true
		);
		$url = $api."?".$url;
		return ['type'=>'jump','url'=>$url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/config.php");
		require(PAY_ROOT."inc/CashierCallback.php");

		//计算得出通知验证结果
		$CashierCallback = new CashierCallback($config);
		$verify_result = $CashierCallback->verifyNotify();

		if($verify_result) {//验证成功
			//商户订单号
			$out_trade_no = daddslashes($_GET['third_trade_no']);

			//支付宝交易号
			$trade_no = daddslashes($_GET['out_trade_no']);

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

		require(PAY_ROOT."inc/config.php");
		require(PAY_ROOT."inc/CashierCallback.php");

		@header('Content-Type: text/html; charset=UTF-8');

		//计算得出通知验证结果
		$CashierCallback = new CashierCallback($config);
		$verify_result = $CashierCallback->verifyNotify();
		if($verify_result) {
			//商户订单号
			$out_trade_no = daddslashes($_GET['third_trade_no']);

			//支付宝交易号
			$trade_no = daddslashes($_GET['out_trade_no']);

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