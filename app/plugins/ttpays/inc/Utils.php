<?php

class TTUtils
{

    /**
     * 签名方法
     * @param $secret
     * @param $data
     * @return bool|string
     */
    public static function sign($secret, array $data)
    {
        if (!is_null($secret)) {
            ksort($data);
            $data['signKey'] = $secret;
            $str = json_encode($data);
            return sha1($str);
        }
        return false;
    }


    /**
     * POST请求
     * @param $url
     * @param array $data
     * @return mixed
     */
    public static function sendPostRequest($url, array $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

    /**
     * 验签
     * @param $secret
     * @param $data
     * @return bool
     */
    public static function verifySign($secret, array $data)
    {
        $sign = $data['sign'];
        unset($data['sign']);
        if ($sign === static::sign($secret, $data)) {
            return true;
        }
        return false;
    }

}