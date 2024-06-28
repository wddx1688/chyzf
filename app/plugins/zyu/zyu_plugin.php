<?php

class zyu_plugin
{
	static public $info = [
		'name'        => 'zyu', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '知宇支付', //支付插件显示名称
		'author'      => '知宇', //支付插件作者
		'link'        => '', //支付插件作者链接
		'types'       => ['alipay','qqpay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appurl' => [
				'name' => '支付网关地址',
				'type' => 'input',
				'note' => '',
			],
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
			'appmchid' => [
				'name' => '通道编码',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static private function make_sign($param, $key){
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

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		$apiurl = $channel['appurl'];
		$data = array(
			"pay_memberid" => $channel['appid'],
			"pay_orderid" => TRADE_NO,
			"pay_amount" => (float)$order['realmoney'],
			"pay_applydate" => date("Y-m-d H:i:s"),
			"pay_bankcode" => $channel['appmchid'],
			"pay_notifyurl" => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			"pay_callbackurl" => $siteurl.'pay/return/'.TRADE_NO.'/',
		);

		$data["pay_md5sign"] = self::make_sign($data, $channel['appkey']);
		$data["pay_productname"] = $ordername;
		/*$res = get_curl($apiurl,http_build_query($data));
		$result = json_decode($res,true);
		if($result['status']==200){
			$code_url = $result['data'];
			return ['type'=>'jump','url'=>$code_url];
		}else{
			return ['type'=>'error','msg'=>'创建订单失败！'.$result['msg']];
		}*/

		$html_text = '<form action="'.$apiurl.'" method="post" id="dopay">';
		foreach($data as $k => $v) {
			$html_text .= "<input type=\"hidden\" name=\"{$k}\" value=\"{$v}\" />\n";
		}
		$html_text .= '<input type="submit" value="正在跳转"></form><script>document.getElementById("dopay").submit();</script>';

		return ['type'=>'html','data'=>$html_text];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$data = array( // 返回字段
			"memberid" => $_REQUEST["memberid"], // 商户ID
			"orderid" =>  $_REQUEST["orderid"], // 订单号
			"amount" =>  $_REQUEST["amount"], // 交易金额
			"datetime" =>  $_REQUEST["datetime"], // 交易时间
			"transaction_id" =>  $_REQUEST["transaction_id"], // 流水号
			"returncode" => $_REQUEST["returncode"]
		);

		$sign = self::make_sign($data, $channel['appkey']);
		
		if ($sign === $_REQUEST["sign"]) {
		
			if ($data["returncode"] == "00") {
				//付款完成后，支付宝系统发送该交易状态通知
				$out_trade_no = daddslashes($data['orderid']);
				$trade_no = daddslashes($data['transaction_id']);
				if($out_trade_no == TRADE_NO && round($data["amount"],2)==round($order['realmoney'],2)){
					processNotify($order, $trade_no);
				}
			}
		
			return ['type'=>'html','data'=>'OK'];
		}
		else {
			//验证失败
			return ['type'=>'html','data'=>'FAIL'];
		}
	}

	//同步回调
	static public function return(){
		global $channel, $order;

		$data = array( // 返回字段
			"memberid" => $_REQUEST["memberid"], // 商户ID
			"orderid" =>  $_REQUEST["orderid"], // 订单号
			"amount" =>  $_REQUEST["amount"], // 交易金额
			"datetime" =>  $_REQUEST["datetime"], // 交易时间
			"transaction_id" =>  $_REQUEST["transaction_id"], // 流水号
			"returncode" => $_REQUEST["returncode"]
		);

		$sign = self::make_sign($data, $channel['appkey']);

		if ($sign === $_REQUEST["sign"]) {
		
		   if ($data["returncode"] == "00") {
				//付款完成后，支付宝系统发送该交易状态通知
				$out_trade_no = daddslashes($data['orderid']);
				$trade_no = daddslashes($data['transaction_id']);
				if($out_trade_no == TRADE_NO && round($data["amount"],2)==round($order['realmoney'],2)){
					processReturn($order, $trade_no);
				}else{
					return ['type'=>'error','msg'=>'订单信息校验失败'];
				}
			}
			else {
				return ['type'=>'error','msg'=>'returncode='.$data["returncode"]];
			}
		}
		else {
			//验证失败
			return ['type'=>'error','msg'=>'验证失败！'];
		}
	}

}