<?php
/**
 * 支付宝实名证件信息比对验证服务类
 */
require_once PAY_ROOT.'inc/AlipayService.php';
require_once PAY_ROOT.'inc/model/request/AlipayUserCertdocCertverifyPreconsultRequest.php';
require_once PAY_ROOT.'inc/model/request/AlipayUserCertdocCertverifyConsultRequest.php';

class AlipayCertdocService extends AlipayService {

	function __construct($alipay_config){
		parent::__construct($alipay_config);

		$this->redirect_uri = $alipay_config['redirect_uri'];
	}

	//实名证件信息比对验证预咨询
	public function preconsult($cert_name, $cert_no) {
		
		$BizContent = array(
			'user_name' => $cert_name, //真实姓名
			'cert_type' => 'IDENTITY_CARD', //证件类型
			'cert_no' => $cert_no
		);
		$request = new AlipayUserCertdocCertverifyPreconsultRequest();
		$request->setBizContent(json_encode($BizContent));

		$response = $this->aopclientRequestExecute($request);
		$response = $response->alipay_user_certdoc_certverify_preconsult_response;
		
		if(!empty($response->code)&&$response->code == 10000){
			$result = array('verify_id'=>$response->verify_id);
		}else{
			$result = array('code'=>$response->code, 'msg'=>$response->msg, 'sub_code'=>$response->sub_code, 'sub_msg'=>$response->sub_msg);
		}

		return $result;

	}

	//实名证件信息比对验证咨询
	public function consult($verify_id, $auth_token) {
		
		$BizContent = array(
			'verify_id' => $verify_id,
		);
		$request = new AlipayUserCertdocCertverifyConsultRequest();
		$request->setBizContent(json_encode($BizContent));

		$response = $this->aopclientRequestExecute($request, $auth_token);
		$response = $response->alipay_user_certdoc_certverify_consult_response;
		
		if(!empty($response->code)&&$response->code == 10000){
			$result = array('passed'=>$response->passed, 'fail_reason'=>$response->fail_reason, 'fail_params'=>$response->fail_params);
		}else{
			$result = array('code'=>$response->code, 'msg'=>$response->msg, 'sub_code'=>$response->sub_code, 'sub_msg'=>$response->sub_msg);
		}

		return $result;

	}

	//跳转支付宝授权页面
	public function getOauthUrl($verify_id, $state) {

		$param = array('app_id'=>$this->appid, 'scope'=>'id_verify', 'redirect_uri'=>$this->redirect_uri, 'cert_verify_id'=>$verify_id, 'state'=>$state);
		$url = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?'.http_build_query($param);

		return $url;

	}

	//换取授权访问令牌
	public function getToken($code, $grant_type = 'authorization_code') {

		$request = new AlipaySystemOauthTokenRequest();
		if($grant_type == 'refresh_token'){
			$request->setGrantType("refresh_token");
			$request->setRefreshToken($code);
		}else{
			$request->setGrantType("authorization_code");
			$request->setCode($code);
		}

		$response = $this->aopclientRequestExecute($request);
		$response = $response->alipay_system_oauth_token_response;
		
		if(!empty($response) && $response->user_id){
			$result = array('user_id'=>$response->user_id, 'access_token'=>$response->access_token);
		}else{
			$result = array('code'=>$response->code, 'msg'=>$response->msg, 'sub_code'=>$response->sub_code, 'sub_msg'=>$response->sub_msg);
		}

		return $result;

	}
}