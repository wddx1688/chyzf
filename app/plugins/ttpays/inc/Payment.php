<?php

class TTPayment
{
    /**
     * 配置项
     * @var array
     */
    private $config = [
        "app_id" => '',
        "secret" => '',
    ];

    /**
     * 支付网关
     * @var string
     */
    private $gateway_url = 'https://api.ttpays.cn/api/v1/create';

    private $query_url = 'https://api.ttpays.cn/api/v1/query';

    private $refund_url = 'https://api.ttpays.cn/api/v1/refund';

    public function __construct($app_id, $secret)
    {
        $this->config['app_id'] = $app_id;
        $this->config['secret'] = $secret;
    }


    /**
     * 发起支付请求
     * @param array $data
     * @return mixed|null
     */
    public function commit(array $data)
    {
        $data['app_id'] = $this->config['app_id'];
        $data['sign'] = TTUtils::sign($this->config['secret'], $data);
        $result = TTUtils::sendPostRequest($this->gateway_url, $data);
        return $result;
    }

    /**
     * 订单查询
     * @param string $out_trade_no
     * @return mixed|null
     */
    public function query($out_trade_no)
    {
        $data = [];
        $data['app_id'] = $this->config['app_id'];
        $data['out_trade_no'] = $out_trade_no;
        $data['sign'] = TTUtils::sign($this->config['secret'], $data);
        $result = TTUtils::sendPostRequest($this->query_url, $data);
        return $result;
    }

    /**
     * 发起退款请求
     * @param string $out_trade_no
     * @return mixed|null
     */
    public function refund($out_trade_no)
    {
        $data = [];
        $data['app_id'] = $this->config['app_id'];
        $data['out_trade_no'] = $out_trade_no;
        $data['sign'] = TTUtils::sign($this->config['secret'], $data);
        $result = TTUtils::sendPostRequest($this->refund_url, $data);
        return $result;
    }


}