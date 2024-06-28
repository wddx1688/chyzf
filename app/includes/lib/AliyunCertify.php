<?php
namespace lib;

class AliyunCertify {
	private $AccessKeyId;
	private $AccessKeySecret;
	private $Endpoint = 'saf.cn-shanghai.aliyuncs.com'; //API接入域名
	private $Version = '2017-03-31'; //API版本号
	private $SceneId;

	function __construct($AccessKeyId, $AccessKeySecret, $SceneId){
		$this->AccessKeyId = $AccessKeyId;
		$this->AccessKeySecret = $AccessKeySecret;
		$this->SceneId = $SceneId;
	}

	//身份认证初始化服务
	public function initialize($outer_order_no, $cert_name, $cert_no, $return_url) {
		$params = [
			'method' => 'init',
			'sceneId' => $this->SceneId,
			'outerOrderNo' => $outer_order_no,
			'bizCode' => 'FACE_SDK',
			'identityType' => 'CERT_INFO',
			'certType' => 'IDENTITY_CARD',
			'certNo' => $cert_no,
			'certName' => $cert_name,
			'returnUrl' => $return_url
		];
		$ServiceParameters = json_encode($params);
		return $this->ExecuteRequest($ServiceParameters);
	}

	//身份认证记录查询
	public function query($certify_id) {
		$params = [
			'method' => 'query',
			'certifyId' => $certify_id,
			'sceneId' => $this->SceneId
		];
		$ServiceParameters = json_encode($params);
		return $this->ExecuteRequest($ServiceParameters);
    }

	//执行请求
	private function ExecuteRequest($ServiceParameters){
		$param = ['Action' => 'ExecuteRequest', 'Service' => 'fin_face_verify', 'ServiceParameters' => $ServiceParameters];
		return $this->request($param, true);
	}

	//签名方法
	private function aliyunSignature($parameters, $accessKeySecret, $method)
	{
		ksort($parameters);
		$canonicalizedQueryString = '';
		foreach ($parameters as $key => $value) {
			$canonicalizedQueryString .= '&' . $this->percentEncode($key). '=' . $this->percentEncode($value);
		}
		$stringToSign = $method . '&%2F&' . $this->percentencode(substr($canonicalizedQueryString, 1));
		$signature = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret."&", true));

		return $signature;
	}
	private function percentEncode($str)
	{
		$res = urlencode($str);
		$res = preg_replace('/\+/', '%20', $res);
		$res = preg_replace('/\*/', '%2A', $res);
		$res = preg_replace('/%7E/', '~', $res);
		return $res;
	}
	//请求方法（当需要返回列表等数据时，returnData=true）
	private function request($param, $returnData=false){
		if(empty($this->AccessKeyId)||empty($this->AccessKeySecret))return false;
		$url='https://'.$this->Endpoint.'/';
		$data=array(
			'Format' => 'JSON',
			'Version' => $this->Version,
			'AccessKeyId' => $this->AccessKeyId,
			'SignatureMethod' => 'HMAC-SHA1',
			'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
			'SignatureVersion' => '1.0',
			'SignatureNonce' => $this->random(8));
		$data=array_merge($data, $param);
		$data['Signature'] = $this->aliyunSignature($data, $this->AccessKeySecret, 'POST');
		$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		$json=curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$arr=json_decode($json,true);
		if($returnData==true){
			return $arr;
		}else{
			if($httpCode==200){
				return true;
			}else{
				return $arr['Message'];
			}
		}
	}
	private function random($length, $numeric = 0) {
		$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
		$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
		$hash = '';
		$max = strlen($seed) - 1;
		for($i = 0; $i < $length; $i++) {
			$hash .= $seed[mt_rand(0, $max)];
		}
		return $hash;
	}
}
