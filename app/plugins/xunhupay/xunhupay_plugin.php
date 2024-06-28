<?php

class xunhupay_plugin
{
	static public $info = [
		'name'        => 'xunhupay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '迅虎支付', //支付插件显示名称
		'author'      => '迅虎', //支付插件作者
		'link'        => 'https://pay.xunhuweb.com/', //支付插件作者链接
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
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif(checkmobile()==true){
				if (!empty($conf['localurl_wxpay']) && !strpos($conf['localurl_wxpay'], $_SERVER['HTTP_HOST'])) {
					return ['type'=>'jump','url'=>$conf['localurl'].'pay/wxwappay/'.TRADE_NO.'/'];
				}else{
					return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/'];
				}
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/?sitename='.$sitename];
			}
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat'){
				return self::wxjspay();
			}elseif($device=='mobile'){
				if (!empty($conf['localurl_wxpay']) && !strpos($conf['localurl_wxpay'], $_SERVER['HTTP_HOST'])) {
					return ['type'=>'jump','url'=>$conf['localurl'].'pay/wxwappay/'.TRADE_NO.'/'];
				}else{
					return ['type'=>'jump','url'=>$siteurl.'pay/wxwappay/'.TRADE_NO.'/'];
				}
			}else{
				return self::wxpay();
			}
		}
	}

	//通用下单
	static private function addOrder($type, &$message){
		global $channel, $order, $ordername, $conf, $clientip;

		require_once(PAY_ROOT."inc/xunhupay.class.php");
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$url ='https://admin.xunhuweb.com/pay/payment';

		$data=array(
			'mchid'     	=> $pay_config['mchid'],
			'out_trade_no'	=> TRADE_NO,
			'type'  		=> $type,
			'trade_type'  	=> 'WEB',
			'total_fee' 	=> strval($order['realmoney']*100),
			'body'  		=> $ordername,
			'notify_url'	=> $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'nonce_str' 	=> str_shuffle(time())
		);

		$data['sign']     = XH_Payment_Api::generate_xh_hash($data,$pay_config['apikey']);
		$response   	  = XH_Payment_Api::http_post_json($url, json_encode($data));
		$result     	  = $response?json_decode($response,true):null;
		if(!$result){
			$message = $response;
			return false;
		}
		if($result['return_code']!='SUCCESS'){
			$message = '['.$result['err_code'].']'.$result['err_msg'];
			return false;
		}
		$sign       	  = XH_Payment_Api::generate_xh_hash($result,$pay_config['apikey']);
		if(!isset( $result['sign'])|| $sign!=$result['sign']){
			$message = '返回数据签名校验失败';
			return false;
		}
		return $result;
	}

	//支付宝扫码支付
	static public function alipay(){
		$message = null;

		$result = self::addOrder('alipay',$message);
		if(!$result) return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$message];

		$code_url = $result['code_url'];

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		$message = null;

		$result = self::addOrder('wechat',$message);
		if(!$result) return ['type'=>'error','msg'=>'微信支付下单失败！'.$message];

		$code_url = $result['code_url'];
		if (checkmobile()==true) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//微信公众号支付（收银台）
	static public function wxjspay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require_once(PAY_ROOT."inc/xunhupay.class.php");
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$url ='https://admin.xunhuweb.com/pay/cashier';

		$data=array(
			'mchid'     	=> $pay_config['mchid'],
			'out_trade_no'	=> TRADE_NO,
			'type'  		=> 'wechat',
			'attach'		=> $order['uid'],
			'total_fee' 	=> strval($order['realmoney']*100),
			'body'  		=> $ordername,
			'notify_url'	=> $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'redirect_url'	=> $siteurl.'pay/return/'.TRADE_NO.'/',
			'nonce_str' 	=> str_shuffle(time())
		);

		$data['sign']     = XH_Payment_Api::generate_xh_hash($data,$pay_config['apikey']);
		$pay_url     = XH_Payment_Api::data_link($url, $data);

		$pay_url=htmlspecialchars_decode($pay_url,ENT_NOQUOTES);

		return ['type'=>'jump','url'=>$pay_url];
	}

	//微信H5
	static public function wxwappay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require_once(PAY_ROOT."inc/xunhupay.class.php");
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$url ='https://admin.xunhuweb.com/pay/payment';

		$data=array(
			'mchid'     	=> $pay_config['mchid'],
			'out_trade_no'	=> TRADE_NO,
			'type'  		=> 'wechat',
			'total_fee' 	=> strval($order['realmoney']*100),
			'body'  		=> $ordername,
			'notify_url'	=> $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'trade_type'	=> 'WAP',
			'wap_url'		=> $_SERVER['HTTP_HOST'],
			'wap_name'		=> $conf['sitename'],
			'nonce_str' 	=> str_shuffle(time())
		);

		$data['sign']     = XH_Payment_Api::generate_xh_hash($data,$pay_config['apikey']);
		$response   	  = XH_Payment_Api::http_post_json($url, json_encode($data));
		$result     	  = $response?json_decode($response,true):null;
		if(!$result){
			return ['type'=>'error','msg'=>'微信支付下单失败！无返回数据'];
		}
		if($result['return_code']!='SUCCESS'){
			return ['type'=>'error','msg'=>'微信支付下单失败！['.$result['err_code'].']'.$result['err_msg']];
		}
		$sign       	  = XH_Payment_Api::generate_xh_hash($result,$pay_config['apikey']);
		if(!isset( $result['sign'])|| $sign!=$result['sign']){
			return ['type'=>'error','msg'=>'微信支付下单失败！返回数据签名校验失败'];
		}

		$redirect_url=$siteurl.'pay/return/'.TRADE_NO.'/';
		$h5_url = $result['mweb_url'].'&redirect_url='.urlencode($redirect_url);
		return ['type'=>'jump','url'=>$h5_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require_once(PAY_ROOT."inc/xunhupay.class.php");
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$json = file_get_contents('php://input');

		$data = json_decode($json, true);
		if(!$data){
			return ['type'=>'html','data'=>'faild!'];
		}

		// file_put_contents(realpath(dirname(__FILE__)) . "/log.txt",json_encode($data)."\r\n",FILE_APPEND);

		$out_trade_no = daddslashes($data['out_trade_no']);
		$order_id = daddslashes($data['order_id']);
		$hash = XH_Payment_Api::generate_xh_hash($data,$pay_config['apikey']);
		if($data['sign']!==$hash){
			//签名验证失败
			return ['type'=>'html','data'=>'sign error'];
		}
		if($data['status']=='complete'){
			/************商户业务处理******************/
			$total_fee = $data['total_fee'];
			if($out_trade_no == TRADE_NO && $total_fee==strval($order['realmoney']*100)){
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

	//退款
	static public function refund($order){
		global $channel, $conf;
		if(empty($order))exit();

		require_once(PAY_ROOT."inc/xunhupay.class.php");
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$url ='https://admin.xunhuweb.com/pay/refund';

		$data=array(
			'mchid'     	=> $pay_config['mchid'],
			'order_id'  => $order['api_trade_no'],
			'nonce_str' 	=> str_shuffle(time()),
			'refund_desc'	=> '订单退款',
			'notify_url'	=> $conf['localurl'].'pay/refundnotify/'.TRADE_NO.'/',
		);
		$data['sign']     = XH_Payment_Api::generate_xh_hash($data,$pay_config['apikey']);
		$response   	  = XH_Payment_Api::http_post_json($url, json_encode($data));
		$result     	  = $response?json_decode($response,true):null;
		if(!$result){
			return ['code'=>-1, 'msg'=>'无返回数据'];
		}
		if($result['return_code']!='SUCCESS'){
			return ['code'=>-1, 'msg'=>'['.$result['err_code'].']'.$result['err_msg']];
		}
		$sign       	  = XH_Payment_Api::generate_xh_hash($result,$pay_config['apikey']);
		if(!isset( $result['sign'])|| $sign!=$result['sign']){
			return ['code'=>-1, 'msg'=>'返回数据签名校验失败'];
		}
		return ['code'=>0, 'trade_no'=>$order['api_trade_no'], 'refund_fee'=>$order['realmoney']];
	}

	//退款回调
	static public function refundnotify(){
		global $channel, $order;

		require_once(PAY_ROOT."inc/xunhupay.class.php");
		$pay_config = require(PAY_ROOT.'inc/config.php');

		$json = file_get_contents('php://input');

		$data = json_decode($json, true);
		if(!$data){
			return ['type'=>'html','data'=>'faild!'];
		}

		$out_trade_no = daddslashes($data['out_trade_no']);
		$order_id = daddslashes($data['order_id']);
		$hash = XH_Payment_Api::generate_xh_hash($data,$pay_config['apikey']);
		if($data['sign']!==$hash){
			//签名验证失败
			return ['type'=>'html','data'=>'sign error'];
		}
		if($data['status']=='complete'){
			/************商户业务处理******************/

			/*************商户业务处理 END*****************/
			return ['type'=>'html','data'=>'success'];
		}else{
			return ['type'=>'html','data'=>'error'];
			//处理未支付的情况	
		}
	}
}