<?php
class App
{
	const APIURL = 'https://openapi.duolabao.com/v1/customer/order/payurl/create';
	const REFUNDURL = 'https://openapi.duolabao.com/v1/customer/order/refund';
	private $config = [];
	
	protected function __construct($config = [])
	{
		$this->config = array_merge($this->config , $config);
		if (!$this->config['customerNum'] || !$this->config['shopNum'] ||
			!$this->config['secretKey']   || !$this->config['accessKey']) {
				throw new \Exception('参数不完整！');
			}
	}
	
	public function submit($trade_order , $money)
	{
		global $conf , $siteurl;
		$navite = array(
			'customerNum' => trim($this->config['customerNum']),
			'shopNum'     => trim($this->config['shopNum']),
			'requestNum'  => $trade_order,
			'amount'      => sprintf('%.2f' , $money),
			'source'      => 'API',
			'callbackUrl' => $conf['localurl'] . 'pay/notify/' . $trade_order . '/',
			//'completeUrl' => $siteurl . 'pay/return/' . $trade_order . '/', //此参数不必携带
		);
		$result = $this->Curl(static::APIURL , $navite);
		if (!$result || !($data = json_decode($result , true))) {
			return false;
		}
		return $data;
	}

	public function refund($trade_order)
	{
		$navite = array(
			'customerNum' => trim($this->config['customerNum']),
			'shopNum'     => trim($this->config['shopNum']),
			'requestNum'  => $trade_order,
		);
		$result = $this->Curl(static::REFUNDURL , $navite);
		if (!$result || !($data = json_decode($result , true))) {
			return false;
		}
		return $data;
	}
	
	public function verifyNotify()
	{
		$headers = array(); 
		foreach ($_SERVER as $key => $value) { 
			if ('HTTP_' == substr($key, 0, 5)) { 
				$headers[str_replace('_', '-', substr($key, 5))] = $value; 
			}
		}
		$signString = "secretKey={$this->config['secretKey']}&timestamp={$headers['TIMESTAMP']}";
		$token = strtoupper(sha1($signString));
		if ($token !== $headers['TOKEN']) {
			return false;
		}
		return true;
	}	
	
	public function verifyReturn()
	{
	}
	
	static function config($config = [])
	{
		return new static($config);
	}
	
	public function __toString()
	{
		return 'Ok';
	}
	
	private function Curl($url , $post = null , int $timeout = 35 ,array $headers = [])
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$httpheader[] = "Accept: */*";
		$httpheader[] = "Accept-Encoding: gzip,deflate";
		$httpheader[] = "Accept-Language: zh-CN,zh;q=0.9";
		$httpheader[] = "Connection: keep-alive";
		curl_setopt($ch, CURLOPT_TIMEOUT , $timeout);
		if ($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
		}
		if (is_array($headers) && count($headers) > 0) {
			foreach ($headers as $name => $val) {
				if (!$name || !$val) continue;
				array_push($httpheader , "{$name}: {$val}");
			}
		}
		$time = time();
		$sign_data = [
			'secretKey' => $this->config['secretKey'],
			'timestamp' => $time,
			'path'      => '/v1/customer/order/payurl/create',
			'body'      => json_encode($post),
		];
		$o = '';
		foreach ($sign_data as $k => $v) {
			 $o .= "{$k}={$v}&";
		}
		$o = substr($o , 0 , -1);
		$token = strtoupper(sha1($o));
		$httpheader[] = 'Content-Type: application/json';
		$httpheader[] = 'accessKey: ' . $this->config['accessKey'];
		$httpheader[] = 'timestamp: ' . $time;
		$httpheader[] = 'token: ' . $token;
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36');
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}