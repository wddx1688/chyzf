<?php
/**
 * 支付宝转账服务类
 */
require_once PAY_ROOT.'inc/AlipayService.php';
require_once PAY_ROOT.'inc/model/request/AlipayFundTransUniTransferRequest.php';
require_once PAY_ROOT.'inc/model/request/AlipayFundTransToaccountTransferRequest.php';
require_once PAY_ROOT.'inc/model/request/AlipayFundTransCommonQueryRequest.php';
require_once PAY_ROOT.'inc/model/request/AlipayFundAccountQueryRequest.php';

class AlipayTransferService extends AlipayService {

	function __construct($alipay_config){
		parent::__construct($alipay_config);
	}

	//转账到支付宝账号
	public function transferToAccount($out_trade_no, $money, $is_userid, $payee_account, $payee_real_name, $payer_show_name) {

		if(!empty($this->alipayCertPath) && !empty($this->appCertPath) && !empty($this->rootCertPath)){
			$payee_type = $is_userid?'ALIPAY_USER_ID':'ALIPAY_LOGON_ID';
			$BizContent = array(
				'out_biz_no' => $out_trade_no, //商户转账唯一订单号
				'trans_amount' => $money, //转账金额
				'product_code' => 'TRANS_ACCOUNT_NO_PWD',
				'biz_scene' => 'DIRECT_TRANSFER',
				'order_title' => $payer_show_name, //付款方显示名称
				'payee_info' => array('identity' => $payee_account, 'identity_type' => $payee_type),
			);
			if(!empty($payee_real_name))$BizContent['payee_info']['name'] = $payee_real_name; //收款方真实姓名
			$request = new AlipayFundTransUniTransferRequest();
		}else{
			$payee_type = $is_userid?'ALIPAY_USERID':'ALIPAY_LOGONID';
			$BizContent = array(
				'out_biz_no' => $out_trade_no, //商户转账唯一订单号
				'payee_type' => $payee_type, //收款方账户类型
				'payee_account' => $payee_account, //收款方账户
				'amount' => $money, //转账金额
				'payer_show_name' => $payer_show_name, //付款方显示姓名
			);
			if(!empty($payee_real_name))$BizContent['payee_real_name'] = $payee_real_name; //收款方真实姓名
			$request = new AlipayFundTransToaccountTransferRequest();
		}

		$request->setBizContent(json_encode($BizContent));

		$response = $this->aopclientRequestExecute($request);
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$response = json_decode(json_encode($response->$responseNode), true);

		return $response;

	}

	//转账到银行卡账户
	public function transferToBankCard($out_trade_no, $money, $payee_account, $payee_real_name, $payer_show_name) {

		$BizContent = array(
			'out_biz_no' => $out_trade_no, //商户转账唯一订单号
			'trans_amount' => $money, //转账金额
			'product_code' => 'TRANS_BANKCARD_NO_PWD',
			'biz_scene' => 'DIRECT_TRANSFER',
			'order_title' => $payer_show_name, //付款方显示名称
			'payee_info' => array(
				'identity_type' => 'BANKCARD_ACCOUNT',
				'identity' => $payee_account,
				'name' => $payee_real_name,
				'bankcard_ext_info' => array(
					'account_type' => '2'
				)
			),
		);
		$request = new AlipayFundTransUniTransferRequest();

		$request->setBizContent(json_encode($BizContent));

		$response = $this->aopclientRequestExecute($request);
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$response = json_decode(json_encode($response->$responseNode), true);

		return $response;

	}

	//转账单据查询
	public function query($order_id, $type=0) {
		
		$BizContent = array(
			'product_code' => 'TRANS_ACCOUNT_NO_PWD',
			'biz_scene' => 'DIRECT_TRANSFER',
		);
		if($type==1){
			$BizContent['pay_fund_order_id'] = $order_id;
		}elseif($type==2){
			$BizContent['out_biz_no'] = $order_id;
		}else{
			$BizContent['order_id'] = $order_id;
		}

		$request = new AlipayFundTransCommonQueryRequest();
		$request->setBizContent(json_encode($BizContent));

		$response = $this->aopclientRequestExecute($request);
		$response = $response->alipay_fund_trans_common_query_response;
		$result = json_decode(json_encode($response), true);
		
		return $result;

	}

	//账户余额查询
	public function accountQuery($alipay_user_id) {
		
		$BizContent = array(
			'alipay_user_id' => $alipay_user_id,
			'account_type' => 'ACCTRANS_ACCOUNT',
		);

		$request = new AlipayFundAccountQueryRequest();
		$request->setBizContent(json_encode($BizContent));

		$response = $this->aopclientRequestExecute($request);
		$response = $response->alipay_fund_account_query_response;
		$result = json_decode(json_encode($response), true);
		
		return $result;

	}

	//转账单据状态变更通知
	public function check($arr){
		if(!empty($this->alipayCertPath) && !empty($this->appCertPath) && !empty($this->rootCertPath)){
			$aop = new AopCertClient ();
			$aop->alipayrsaPublicKey = $aop->getPublicKey($this->alipayCertPath);
			$result = $aop->rsaCheckV1($arr, $this->alipayCertPath, $this->signtype);
		}else{
			$aop = new AopClient();
			$aop->alipayrsaPublicKey = $this->alipay_public_key;
			$result = $aop->rsaCheckV1($arr, $this->alipay_public_key, $this->signtype);
		}
		if($result){
			return true;
		}
		return false;
	}
}