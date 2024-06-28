<?php
class Payjs
{
    private $key;
    private $mchid;

    public function __construct($config) {
        $this->mchid = $config['mchid'];
		$this->key = $config['key'];
    }

    public function pay($data){
		$apiurl = 'https://payjs.cn/api/native';

        $data['mchid'] = $this->mchid;
        $data['sign'] = $this->sign($data);

        $return = $this->post($data, $apiurl);
		return json_decode($return, true);
    }

	public function mwebpay($data){
		$apiurl = 'https://payjs.cn/api/mweb';

        $data['mchid'] = $this->mchid;
        $data['sign'] = $this->sign($data);

        $return = $this->post($data, $apiurl);
		return json_decode($return, true);
    }

	public function cashier($data){
		$apiurl = 'https://payjs.cn/api/cashier';

        $data['mchid'] = $this->mchid;
        $data['sign'] = $this->sign($data);

        return $apiurl.'?'.http_build_query($data);
    }

	public function checkOrder($payjs_order_id){
		$apiurl = 'https://payjs.cn/api/check';

        $data['payjs_order_id'] = $payjs_order_id;
        $data['sign'] = $this->sign($data);

        $return = $this->post($data, $apiurl);
		return json_decode($return, true);
    }

	public function closeOrder($payjs_order_id){
		$apiurl = 'https://payjs.cn/api/close';

        $data['payjs_order_id'] = $payjs_order_id;
        $data['sign'] = $this->sign($data);

        $return = $this->post($data, $apiurl);
		return json_decode($return, true);
    }

	public function refund($payjs_order_id){
		$apiurl = 'https://payjs.cn/api/refund';

        $data['payjs_order_id'] = $payjs_order_id;
        $data['sign'] = $this->sign($data);

        $return = $this->post($data, $apiurl);
		return json_decode($return, true);
    }

    public function post($data, $url) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $rst = curl_exec($ch);
        curl_close($ch);

        return $rst;
    }

    public function sign($attributes) {
        ksort($attributes);
        $sign = strtoupper(md5(urldecode(http_build_query($attributes)) . '&key=' . $this->key));
        return $sign;
    }

	public function checkSign($arr)
	{
		$user_sign = $arr['sign'];
		unset($arr['sign']);
		array_filter($arr);
		ksort($arr);
		$check_sign = strtoupper(md5(urldecode(http_build_query($arr) . '&key=' . $this->key)));
		if ($user_sign != $check_sign)return false;
		else return true;
	}
}
