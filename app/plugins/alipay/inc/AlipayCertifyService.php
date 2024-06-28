<?php
/**
 * 支付宝身份认证服务类
 */
require_once PAY_ROOT.'inc/AlipayService.php';
require_once PAY_ROOT.'inc/model/request/AlipayUserCertifyOpenInitializeRequest.php';
require_once PAY_ROOT.'inc/model/request/AlipayUserCertifyOpenCertifyRequest.php';
require_once PAY_ROOT.'inc/model/request/AlipayUserCertifyOpenQueryRequest.php';

class AlipayCertifyService extends AlipayService {

	function __construct($alipay_config){
		parent::__construct($alipay_config);

		$this->return_url = $alipay_config['cert_return_url'];
	}

	//身份认证初始化服务
	public function initialize($outer_order_no, $cert_name, $cert_no, $biz_code = 'SMART_FACE') {
		
		$BizContent = array(
			'outer_order_no' => $outer_order_no, //商户请求的唯一标识
			'biz_code' => $biz_code, //认证场景码
			'identity_param' => [
				'identity_type' => 'CERT_INFO', //身份信息参数类型
				'cert_type' => 'IDENTITY_CARD', //证件类型
				'cert_name' => $cert_name, //真实姓名
				'cert_no' => $cert_no, //证件号码
				],
			'merchant_config' => ['return_url'=>$this->return_url], //商户个性化配置
		);
		$request = new AlipayUserCertifyOpenInitializeRequest();
		$request->setBizContent(json_encode($BizContent));

		$response = $this->aopclientRequestExecute($request);
		$response = $response->alipay_user_certify_open_initialize_response;
		
		if(!empty($response->code)&&$response->code == 10000){
			$result = array('certify_id'=>$response->certify_id);
		}else{
			$result = array('code'=>$response->code, 'msg'=>$response->msg, 'sub_code'=>$response->sub_code, 'sub_msg'=>$response->sub_msg);
		}

		return $result;

	}

	//身份认证开始认证
	public function certify($certify_id) {

		$BizContent = array(
			'certify_id' => $certify_id,
		);
		$request = new AlipayUserCertifyOpenCertifyRequest();
		$request->setBizContent(json_encode($BizContent));

		$response = $this->aopclientRequestPageExecute($request);
		
		return $response;

	}

	//身份认证记录查询
	public function query($certify_id) {
		
		$BizContent = array(
			'certify_id' => $certify_id,
		);
		$request = new AlipayUserCertifyOpenQueryRequest();
		$request->setBizContent(json_encode($BizContent));

		$response = $this->aopclientRequestExecute($request);
		$response = $response->alipay_user_certify_open_query_response;
		
		if(!empty($response->code)&&$response->code == 10000){
			$result = array('passed'=>$response->passed[0], 'identity_info'=>$response->identity_info, 'material_info'=>$response->material_info);
		}else{
			$result = array('code'=>$response->code, 'msg'=>$response->msg, 'sub_code'=>$response->sub_code, 'sub_msg'=>$response->sub_msg);
		}

		return $result;

	}
}