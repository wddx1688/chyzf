<?php

class jdpay_plugin
{
	static public $info = [
		'name'        => 'jdpay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '京东支付', //支付插件显示名称
		'author'      => '京东支付', //支付插件作者
		'link'        => 'https://www.jdpay.com/', //支付插件作者链接
		'types'       => ['jdpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户DES密钥',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => null,
		'note' => '需要将密钥文件上传到 /plugins/jdpay/inc/cert 文件夹内', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $submit2, $conf;

		require(PAY_ROOT."inc/common/SignUtil.php");
		require(PAY_ROOT."inc/common/TDESUtil.php");

		if(checkmobile()==true){
			$oriUrl = 'https://h5pay.jd.com/jdpay/saveOrder';
		}else{
			$oriUrl = 'https://wepay.jd.com/jdpay/saveOrder';
		}

		$param=array();
		$param["version"]='V2.0';
		$param["merchant"]=$channel['appid'];
		$param["tradeNum"]=TRADE_NO;
		$param["tradeName"]=$ordername;
		$param["tradeTime"]= date('YmdHis');
		$param["amount"]= strval($order['realmoney']*100);
		$param["currency"]= 'CNY';
		$param["callbackUrl"]= $siteurl.'pay/return/'.TRADE_NO.'/';
		$param["notifyUrl"]= $conf['localurl'].'pay/notify/'.TRADE_NO.'/';
		$param["ip"]= $clientip;
		$param["userId"]= '';
		$param["orderType"]= '1';
		$unSignKeyList = array("sign");
		$desKey = $channel['appkey'];
		$sign = SignUtil::signWithoutToHex($param, $unSignKeyList);
		//echo $sign."<br/>";
		$param["sign"] = $sign;
		$keys = base64_decode($desKey);

		$param["tradeNum"]=TDESUtil::encrypt2HexStr($keys, $param["tradeNum"]);
		if($param["tradeName"] != null && $param["tradeName"]!=""){
			$param["tradeName"]=TDESUtil::encrypt2HexStr($keys, $param["tradeName"]);
		}
		$param["tradeTime"]=TDESUtil::encrypt2HexStr($keys, $param["tradeTime"]);
		$param["amount"]=TDESUtil::encrypt2HexStr($keys, $param["amount"]);
		$param["currency"]=TDESUtil::encrypt2HexStr($keys, $param["currency"]);
		$param["callbackUrl"]=TDESUtil::encrypt2HexStr($keys, $param["callbackUrl"]);
		$param["notifyUrl"]=TDESUtil::encrypt2HexStr($keys, $param["notifyUrl"]);
		$param["ip"]=TDESUtil::encrypt2HexStr($keys, $param["ip"]);

		if($param["userId"] != null && $param["userId"]!=""){
			$param["userId"]=TDESUtil::encrypt2HexStr($keys, $param["userId"]);
		}
		if($param["orderType"] != null && $param["orderType"]!=""){
			$param["orderType"]=TDESUtil::encrypt2HexStr($keys, $param["orderType"]);
		}
		//print_R($param);exit;

		$html_text = '<form action="'.$oriUrl.'" method="post" id="dopay">';
		foreach($param as $k => $v) {
			$html_text .= "<input type=\"hidden\" name=\"{$k}\" value=\"{$v}\" />\n";
		}
		$html_text .= '<input type="submit" value="正在跳转"></form><script>document.getElementById("dopay").submit();</script>';

		return ['type'=>'html','data'=>$html_text];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/common/XMLUtil.php");

		define("Confid_desKey",$channel['appkey']);
		$xml = file_get_contents("php://input");
		$flag = XMLUtil::decryptResXml($xml, $param);
		//var_dump($flag);
		if($flag){
			$trade_no = daddslashes($param["tradeNum"]);
			$out_trade_no = daddslashes($param["tradeNum"]);
			if($param["status"]==2) {
				if($out_trade_no == TRADE_NO && $param["amount"]==strval($order['realmoney']*100)){
					processNotify($order, $trade_no);
				}
			}
			return ['type'=>'html','data'=>'success'];
		}else{
			return ['type'=>'html','data'=>'error'];
		}
	}

	//同步回调
	static public function return(){
		global $channel, $order;

		require(PAY_ROOT."inc/common/SignUtil.php");
		require(PAY_ROOT."inc/common/TDESUtil.php");

		$desKey = $channel['appkey'];
		$keys = base64_decode($desKey);
		$param = array();
		if(!empty($_POST["tradeNum"])){
			$param["tradeNum"]=TDESUtil::decrypt4HexStr($keys, $_POST["tradeNum"]);
		}
		if(!empty($_POST["amount"])){
			$param["amount"]=TDESUtil::decrypt4HexStr($keys, $_POST["amount"]);
		}
		if(!empty($_POST["currency"])){
			$param["currency"]=TDESUtil::decrypt4HexStr($keys, $_POST["currency"]);
		}
		if(!empty($_POST["tradeTime"])){
			$param["tradeTime"]=TDESUtil::decrypt4HexStr($keys, $_POST["tradeTime"]);
		}
		if(!empty($_POST["status"])){
			$param["status"]=TDESUtil::decrypt4HexStr($keys, $_POST["status"]);
		}

		$sign = $_POST["sign"];
		$strSourceData = SignUtil::signString($param, array());
		//echo "strSourceData=".htmlspecialchars($strSourceData)."<br/>";
		//$decryptBASE64Arr = base64_decode($sign);
		$decryptStr = RSAUtils::decryptByPublicKey($sign);
		//echo "decryptStr=".htmlspecialchars($decryptStr)."<br/>";
		$sha256SourceSignString = hash ( "sha256", $strSourceData);
		//echo "sha256SourceSignString=".htmlspecialchars($sha256SourceSignString)."<br/>";
		if($decryptStr == $sha256SourceSignString){
			$trade_no = daddslashes($param["tradeNum"]);
			$out_trade_no = daddslashes($param["tradeNum"]);
			if($out_trade_no == TRADE_NO && $param["amount"]==$order['realmoney']*100){
				processReturn($order, $trade_no);
			}else{
				return ['type'=>'error','msg'=>'订单信息校验失败'];
			}
		}else{
			return ['type'=>'error','msg'=>'验证签名失败！strSourceData='.htmlspecialchars($strSourceData)];
		}
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT."inc/common/XMLUtil.php");
		require(PAY_ROOT."inc/common/HttpUtils.php");

		define("Confid_desKey",$channel['appkey']);

		$param["version"]="V2.0";
		$param["merchant"]=$channel['appid'];
		$param["tradeNum"]=$order['trade_no'].rand(000,999);
		$param["oTradeNum"]=$order['api_trade_no'];
		$param["amount"]=$order['refundmoney']*100;
		$param["currency"]="CNY";

		$reqXmlStr = XMLUtil::encryptReqXml($param);
		$url = 'https://paygate.jd.com/service/refund';

		$httputil = new HttpUtils();
		list ( $return_code, $return_content )  = $httputil->http_post_data($url, $reqXmlStr);
		//echo $return_content."\n";

		$flag=XMLUtil::decryptResXml($return_content,$resData);
		//echo var_dump($resData);

		if($flag){
			if($resData['status'] == "1"){
				$result = ['code'=>0, 'trade_no'=>$resData['oTradeNum'], 'refund_fee'=>$resData['amount']];
			}else{
				$result = ['code'=>-1, 'msg'=>'['.$resData['result']['code'].']'.$resData['result']['desc']];
			}
		}else{
			$result = ['code'=>-1, 'msg'=>'验签失败'];
		}

		return $result;
	}

}