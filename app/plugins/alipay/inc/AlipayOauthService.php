<?php
/**
 * 支付宝快捷登录服务类
 */
require_once PAY_ROOT.'inc/AlipayService.php';
require_once PAY_ROOT.'inc/model/request/AlipaySystemOauthTokenRequest.php';
require_once PAY_ROOT.'inc/model/request/AlipayUserInfoShareRequest.php';

class AlipayOauthService extends AlipayService {

	function __construct($alipay_config){
		parent::__construct($alipay_config);

		$this->redirect_uri = $alipay_config['redirect_uri'];
	}

	//跳转支付宝授权页面
	public function oauth() {

		$param = array('app_id'=>$this->appid, 'scope'=>'auth_base', 'redirect_uri'=>$this->redirect_uri);
		$url = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?'.http_build_query($param);

		Header("Location: $url");
		exit();

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

	//支付宝会员授权信息查询
	public function userinfo($accessToken) {

		$request = new AlipayUserInfoShareRequest();

		$response = $this->aopclientRequestExecute($request, $accessToken);
		$response = $response->alipay_user_info_share_response;
		
		if(!empty($response) && $response->code == "10000"){
			$result = json_decode(json_encode($response), true);
		}else{
			$result = array('code'=>$response->code, 'msg'=>$response->msg, 'sub_code'=>$response->sub_code, 'sub_msg'=>$response->sub_msg);
		}

		return $result;

	}

}