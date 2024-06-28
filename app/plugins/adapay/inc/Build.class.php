<?php
Class AdaTools
{
	public $rsaPrivateKeyFilePath;
	public $rsaPublicKeyFilePath;
	public $rsaPrivateKey;
	public $rsaPublicKey;
	
	public function __construct()
	{
	}
	
	public function generateSignature($url , $params = []):string
	{
		$data = '';
		if (is_array($params)) {
			$data .= $url . json_encode($params);
		} else {
			$data .= $url . $params;
		}
		$sign = $this->SHA1withRSA($data);
		return $sign;
	}
	
	public function SHA1withRSA($data)
	{
		if ($this->checkEmpty($this->rsaPrivateKeyFilePath)) {
			$privKey = trim($this->rsaPrivateKey);
			$key = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($privKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
		} else {
			$privKey = file_get_contents($this->rsaPrivateKeyFilePath);
			$key = openssl_get_privatekey($privKey);
		}
		openssl_sign($data , $signature , $key , OPENSSL_ALGO_SHA1);
		return base64_encode($signature);
	}
	
	public function verifySign($signature , $data)
	{
		if ($this->checkEmpty($this->rsaPublicKeyFilePath)) {
			$pubKey = trim($this->rsaPublicKey);
			$key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
		} else {
			$pubKey = file_get_contents($this->rsaPublicKeyFilePath);
			$key = openssl_get_publickey($pubKey);
		}
		if (openssl_verify($data , base64_decode($signature) , $key , OPENSSL_ALGO_SHA1)) {
			return true;
		}
		return false;
	}
	
	public function checkEmpty($value)
	{
		if (!isset($value) || ('' === trim($value)) || is_null($value)) {
			return true;
		}
		return false;
	}
}
class AdaPay 
{
	const SDK_VERSION = 'v1.0.0';
	static $gateWayUrl = 'https://api.adapay.tech'; //网关地址
	static $header = ['Content-Type:application/json'];
	static $headerText = ['Content-Type:text/html'];
	static $rsaPrivateKey;
	static $rsaPublicKey = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwN6xgd6Ad8v2hIIsQVnbt8a3JituR8o4Tc3B5WlcFR55bz4OMqrG/356Ur3cPbc2Fe8ArNd/0gZbC9q56Eb16JTkVNA/fye4SXznWxdyBPR7+guuJZHc/VW2fKH2lfZ2P3Tt0QkKZZoawYOGSMdIvO+WqK44updyax0ikK6JlNQIDAQAB";
	static $signType = 'RSA2';
	static $app_id;
	static $api_key;
	public $ada_tools;
	private $endpoint = '/v1/payments';
	private $orderInfo = [];
	
	public function __construct($config_info)
	{
		if (empty($config_info) || !is_array($config_info)) {
			throw new \Exception('缺少SDK配置信息');
		}
		if (empty($config_info['app_id'])) {
			throw new \Exception('渠道ID不能为空');
		}
		if (empty($config_info['api_key_live'])) {
			throw new \Exception('API密钥不能为空');
		}
		if (empty($config_info['rsa_private_key'])) {
			throw new \Exception('商户私钥不能为空');
		}

		$sdk_version = self::SDK_VERSION;
		array_push(self::$header , "sdk_version:{$sdk_version}");
		array_push(self::$headerText , "sdk_version:{$sdk_version}");

		self::$app_id = trim($config_info['app_id']);
		self::$api_key = trim($config_info['api_key_live']);
		self::$rsaPrivateKey = trim($config_info['rsa_private_key']);

		$this->ada_tools = new AdaTools();
		$this->ada_tools->rsaPrivateKey = self::$rsaPrivateKey;
		$this->ada_tools->rsaPublicKey = self::$rsaPublicKey;
	}

	static function config($config = [])
	{
		return new static($config);
	}

	private function request_header($req_url , $postData , array $header = [])
	{
		array_push($header , 'Authorization:' . self::$api_key);
		array_push($header , 'Signature:' . $this->ada_tools->generateSignature($req_url , $postData));
		return $header;
	}

	//创建支付订单
	public function submit($channel, $openid = null)
	{
		$params = [
			'order_no'		=> $this->order('trade_no'),
			'app_id'		=> self::$app_id,
			'pay_channel' 	=> $channel,
			'pay_amt'		=> sprintf('%.2f' , $this->order('money')),
			'goods_title' 	=> $this->order('goods_title' , null , '自助购物'),
			'goods_desc'  	=> $this->order('goods_desc' , null , '自助购物'),
			'currency'		=> $this->order('currency' , null , 'cny'),
			'sign_type'		=> self::$signType,
			'notify_url'	=> $this->order('notify_url'),
		];
		if ($channel === 'wx_pub' || $channel === 'wx_lite') {
			$params['expend'] = [
				'openid' => $openid,
			];
		}
		$request_params = $params;
		$req_url = self::$gateWayUrl . $this->endpoint;
		$headers = $this->request_header($req_url , $request_params , self::$header);
		$response = $this->curl($req_url , $request_params , $headers);
		if (!$response || !($result = json_decode($response , true))) {
			throw new \Exception('网络错误:请求[Curl]远程访问组件出现错误或JSON字符串未能正确解析！');
		}
		extract($result);
		$data = array_change_key_case(json_decode($data , true) , CASE_LOWER);

		if (strtolower($data['status']) !== 'succeeded') {
			throw new \Exception('['.$data['error_code'].']'.$data['error_msg']);
		}
		return $data;
	}

	//查询订单
	public function orderQuery($id)
	{
		$req_url = self::$gateWayUrl . $this->endpoint . "/{$id}";
		$headers = $this->request_header($req_url , "",  self::$headerText);
		$response = $this->curl($req_url , null , $headers);
		if (!$response || !($result = json_decode($response , true))) {
			throw new \Exception('网络错误:请求[Curl]远程访问组件出现错误或JSON字符串未能正确解析！');
		}
		extract($result);
		$data = array_change_key_case(json_decode($data , true) , CASE_LOWER);
		if (strtolower($data['status']) !== 'succeeded') {
			throw new \Exception('['.$data['error_code'].']'.$data['error_msg']);
		}
		return $data;
	}

	public function order($name = null, $value = null , $default = '')
	{
		if (is_null($value) && is_string($name)) {
			return $this->orderInfo[$name] ?: ($default ?: '');
		} else if (is_array($name)) {
			$this->orderInfo = array_merge($this->orderInfo , $name);
		} else if (!is_null($value)) {
			$this->orderInfo[$name] = $value;
		}
		return $this;
	}

	/**
	 * 创建退款对象
	 * @Author   Kelly
	 * @DateTime 2020-10-22
	 * @version  V1.1.4
	 * @param    array
	 * @return   array
	 */
	public function createRefund($params=array()){
		$request_params = $params;
		$charge_id = isset($params['payment_id']) ? $params['payment_id'] : '';
		$req_url = self::$gateWayUrl .$this->endpoint."/". $charge_id. "/refunds";
		$header =  $this->request_header($req_url, $request_params, self::$header);
		$response = $this->curl($req_url, $request_params, $header);
		if (!$response || !($result = json_decode($response , true))) {
			throw new \Exception('网络错误:请求[Curl]远程访问组件出现错误或JSON字符串未能正确解析！');
		}
		extract($result);
		$data = array_change_key_case(json_decode($data , true) , CASE_LOWER);
		if (strtolower($data['status']) !== 'succeeded') {
			throw new \Exception('['.$data['error_code'].']'.$data['error_msg']);
		}
		return $data;
	}

	/**
	 * 查询退款对象
	 * @Author   Kelly
	 * @DateTime 2020-10-22
	 * @version  V1.1.4
	 * @param    array
	 * @return   array
	 */
	public function queryRefund($params=array()){
		$request_params = $params;
		$req_url = self::$gateWayUrl .$this->endpoint."/refunds";
		$header = $this->request_header($req_url, http_build_query($request_params), self::$headerText);
		$response = $this->curl($req_url."?".http_build_query($request_params), null, $header);
		if (!$response || !($result = json_decode($response , true))) {
			throw new \Exception('网络错误:请求[Curl]远程访问组件出现错误或JSON字符串未能正确解析！');
		}
		extract($result);
		$data = array_change_key_case(json_decode($data , true) , CASE_LOWER);

		if (strtolower($data['status']) !== 'succeeded') {
			throw new \Exception('['.$data['error_code'].']'.$data['error_msg']);
		}
		return $data;
	}
	
	/**
	 * @param $url
	 * @param null $post
	 * @param null $cookie
	 * @return bool|string
	 */
	protected function curl($url, $post = null, array $headers = [])
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		if (!is_null($post)) {
			curl_setopt($ch, CURLOPT_POST, true);
			if (is_array($post)) {
				$postData = json_encode($post);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
				array_push($headers , 'Content-Length:' . strlen($postData));
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			}
		}
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}
}