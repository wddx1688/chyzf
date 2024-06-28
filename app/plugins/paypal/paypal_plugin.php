<?php

use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Exception;

class paypal_plugin
{
	static public $info = [
		'name'        => 'paypal', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'PayPal', //支付插件显示名称
		'author'      => 'PayPal', //支付插件作者
		'link'        => 'https://www.paypal.com/', //支付插件作者链接
		'types'       => ['paypal'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => 'ClientId',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'ClientSecret',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '模式选择',
				'type' => 'select',
				'options' => [0=>'线上模式',1=>'沙盒模式'],
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		require_once(PAY_ROOT."inc/common.php");

		$payer = new Payer();
		$payer->setPaymentMethod("paypal");

		$amount = new Amount();
		$amount->setCurrency("USD");
		$amount->setTotal($order['realmoney']);

		$transaction = new Transaction();
		$transaction->setAmount($amount);
		$transaction->setDescription($order['name']);
		$transaction->setInvoiceNumber(TRADE_NO);

		$redirectUrls = new RedirectUrls();
		$redirectUrls->setReturnUrl($siteurl.'pay/return/'.TRADE_NO.'/');
		$redirectUrls->setCancelUrl($siteurl.'pay/cancel/'.TRADE_NO.'/');

		$payment = new Payment();
		$payment->setIntent("sale")
			->setPayer($payer)
			->setRedirectUrls($redirectUrls)
			->setTransactions(array($transaction));
		try {
			//创建支付
			$payment->create($apiContext);
			//生成地址
			$approvalUrl = $payment->getApprovalLink();

			// var_dump($approvalUrl);
			//跳转
			return ['type'=>'jump','url'=>$approvalUrl];
		}
		catch (\Exception $ex) {
			sysmsg('PayPal下单失败：'.$ex->getMessage());
		}

	}

	//同步回调
	static public function return(){
		global $channel, $order;

		require_once(PAY_ROOT."inc/common.php");
		
		if (isset($_GET['paymentId']) && isset($_GET['PayerID'])) {
		
			$paymentId = $_GET['paymentId'];
			try {
				$payment = Payment::get($paymentId, $apiContext);
			} catch (\Exception $ex) {
				return ['type'=>'error','msg'=>'获取订单失败 '.$ex->getMessage()];
			}

			$execution = new PaymentExecution();
			$execution->setPayerId($_GET['PayerID']);

			$amount = new Amount();
			$amount->setCurrency('USD');
			$amount->setTotal($order['realmoney']);

			$transaction = new Transaction();
			$transaction->setAmount($amount);

			$execution->addTransaction($transaction); 

			try {
				$result = $payment->execute($execution, $apiContext);

				$payer = $result->payer->payer_info->email;
				$out_trade_no = $result->transactions[0]->invoice_number;

				if($out_trade_no == TRADE_NO){
					processReturn($order, $paymentId, $payer);
				}else{
					return ['type'=>'error','msg'=>'订单信息校验失败'];
				}
				
			} catch (\Exception $ex) {
				return ['type'=>'error','msg'=>'支付失败 '.$ex->getMessage()];
			}
		} else {
			return ['type'=>'error','msg'=>'PayPal返回参数错误'];
		}
	}

	static public function cancel(){
		return ['type'=>'page','page'=>'error'];
	}

}