<?php

class xunhupay2_plugin
{
	static public $info = [
		'name'        => 'xunhupay2', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '虎皮椒支付', //支付插件显示名称
		'author'      => '虎皮椒', //支付插件作者
		'link'        => 'https://www.xunhupay.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户ID',
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
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/?sitename='.$sitename];
		}elseif($order['typename']=='wxpay'){
			return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/?sitename='.$sitename];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		$typename = $order['typename'];
		return self::$typename();
	}

	//通用下单
	static private function addOrder($type, &$message){
		global $channel, $order, $ordername, $conf, $clientip, $siteurl;

		require_once(PAY_ROOT."inc/xunhupay.class.php");
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$url ='https://api.xunhupay.com/payment/do.html';

		$data=array(
			'version'   => '1.1',
			'appid'     => $pay_config['mchid'],
			'trade_order_id'=> TRADE_NO,
			'payment'   => $type,
			'total_fee' => $order['realmoney'],
			'title'     => $ordername,
			'time'      => time(),
			'notify_url'=>  $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'return_url'=> $siteurl.'pay/return/'.TRADE_NO.'/',
			'nonce_str' => str_shuffle(time())
		);

		if($type == 'wechat' && checkmobile()==true){
			$data['type'] = 'WAP';
			$data['wap_url'] = $_SERVER['HTTP_HOST'];
			$data['wap_name'] = $conf['sitename'];
		}

		$data['hash']     = XH_Payment_Api::generate_xh_hash($data,$pay_config['apikey']);
		$response   	  = get_curl($url, json_encode($data));
		$result     	  = $response?json_decode($response,true):null;
		if(!$result){
			$message = $response;
			return false;
		}
		$hash       	  = XH_Payment_Api::generate_xh_hash($result,$pay_config['apikey']);
		if(!isset( $result['hash'])|| $hash!=$result['hash']){
			$message = '返回数据签名校验失败';
			return false;
		}
		if($result['errcode']!=0){
			$message = '['.$result['errcode'].']'.$result['errmsg'];
		}
		return $result;
	}

	//支付宝扫码支付
	static public function alipay(){
		$message = null;

		$result = self::addOrder('alipay',$message);
		if(!$result) return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$message];

		$code_url = $result['url'];

		return ['type'=>'jump','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		$message = null;

		$result = self::addOrder('wechat',$message);
		if(!$result) return ['type'=>'error','msg'=>'微信支付下单失败！'.$message];

		$code_url = $result['url'];
		return ['type'=>'jump','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require_once(PAY_ROOT."inc/xunhupay.class.php");
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$data = $_POST;
		if(!isset($data['hash'])||!isset($data['trade_order_id'])){
			return ['type'=>'html','data'=>'failed'];
		}
		$hash =XH_Payment_Api::generate_xh_hash($data,$pay_config['apikey']);
		if($data['hash']!=$hash){
			//签名验证失败
			return ['type'=>'html','data'=>'sign error'];
		}

		$out_trade_no = daddslashes($data['trade_order_id']);
		$order_id = daddslashes($data['open_order_id']);

		if($data['status']=='OD'){
			/************商户业务处理******************/
			$total_fee = $data['total_fee'];
			if($out_trade_no == TRADE_NO && round($total_fee,2)==round($order['realmoney'],2)){
				processNotify($order, $order_id);
			}
			/*************商户业务处理 END*****************/
			return ['type'=>'html','data'=>'success'];
		}else{
			return ['type'=>'html','data'=>'error'];
			//处理未支付的情况	
		}
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

}