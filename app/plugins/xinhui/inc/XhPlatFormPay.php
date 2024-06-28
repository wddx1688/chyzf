<?php
/**
 * Created By YunLan
 * User: _cloud
 * Date: 2021/2/23
 * Time: 19:42
 */

class XhPlatFormPay
{
    // 单向实例化
    protected static $instance = null;
    // 错误信息
    protected $error = '';

    protected $orderModel = [];

    protected $separator = '&';

    protected $apiUrl = 'http://www.zzhengt.com%s';

    protected $ignoreKeys = [
        'sign',
    ];

    protected $config = [
    ];

    protected $rawBody = [];

    /**
     * XhPlatFormPay constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    public function submit()
    {
        /**
         * 公共请求参数
         */
        $requestParams         = [
            'orderId'     => $this->order('trade_no'), // 交易订单号
            'merchantId'  => $this->getConfig('member_id'), // 商户号
            'version'     => $this->order('version', null, '0.0.1'), // 系统版本号[固定值]
            'bizCode'     => $this->order('bizCode', null, 1002), // 支付交易编码
            'bgUrl'       => $this->order('notify_url'), // 回调地址
            'terminalIp'  => $this->order('remote_addr', null, $_SERVER['REMOTE_ADDR']), // 子客户端IP地址
            'productName' => $this->order('subject', null, 'test'), // 商品名称
            'productDes'  => $this->order('subject_desc', null, 'test'), // 商品描述
            'orderAmt'    => abs(sprintf('%.2f', $this->order('total_amount', null, 1))) * 100, // 订单交易金额
            'openId'      => $this->order('open_id'), // 用户Openid
            'appId'       => $this->order('app_id'), // 商家公众号ID
            'mTerminalId' => $this->order('terminal_id'), // 商户扫码设备编号(条码必填)
            'mmStoreNo'   => $this->order('store_id'), // 门店编号
            'timeExpire'  => $this->order('time_expire'), // 订单超时时间
            'authCode'    => $this->order('auth_code'), // 认证码编号
        ];
        $requestParams['sign'] = $this->buildSignature($requestParams);
        $response              = $this->requestSend($this->action('/order/pay.do'), ['type' => 'POST', 'body' => $requestParams]);
        if ($response['errCode'] !== 0) {
            $this->setError($response['errMsg']);
            return false;
        }
        $result = $response['resultData'];
        if ($result['rspCode'] != '00') {
            $this->setError($result['rspMsg']);
            return false;
        }
        return $result;
    }

    /**
     * 交易订单查询
     * @param        $trade_no 交易订单号
     * @param string $type     查询方式
     */
    public function orderQuery($trade_no, $type = '*')
    {
        $requestParams         = [
            'orderId'    => trim($trade_no),
            'merchantId' => $this->getConfig('member_id'),
            'bizCode'    => $this->order('biz_code', null, 4001),
            'version'    => $this->order('version', null, '0.0.1'),
        ];
        $requestParams['sign'] = $this->buildSignature($requestParams, CASE_UPPER);
        $response              = $this->requestSend($this->action('/query/order.do'), ['type' => 'POST', 'body' => $requestParams]);
        if ($response['errCode'] !== 0) {
            $this->setError($response['errMsg']);
        }
        return $response['resultData'];
    }

    /**
     * 退款
     * @param        $orderId 退款流水号
     * @param string $origOrderId 支付订单号
     * @param string $refundAmt 退款金额
     */
    public function refund($orderId, $origOrderId, $refundAmt)
    {
        $requestParams         = [
            'orderId'    => trim($orderId),
            'merchantId' => $this->getConfig('member_id'),
            'bizCode'    => $this->order('biz_code', null, 2900),
            'version'    => $this->order('version', null, '0.0.1'),
            'refundAmt' => $refundAmt,
            'bgUrl' => $this->order('notify_url'),
            'origOrderId' => $origOrderId,
        ];
        $requestParams['sign'] = $this->buildSignature($requestParams, CASE_UPPER);
        $response              = $this->requestSend($this->action('/refund/pay.do'), ['type' => 'POST', 'body' => $requestParams]);
        if ($response['errCode'] !== 0) {
            $this->setError($response['errMsg']);
        }
        $result = $response['resultData'];
        if ($result['rspCode'] != '00') {
            $this->setError($result['rspMsg']);
            return false;
        }
        return $result;
    }

    /**
     * 支付回调验证
     * @param array $paramters
     * @return bool
     */
    public function payNotify($paramters): bool
    {
        $paramters = $this->toArray($paramters);
        $this->setInput($paramters);
        if (!$this->beforeCheckRequestParamterForBinding($paramters)) {
            return false;
        }
        // 签名验证
        $verifySignSecret = $this->signurateVerify($paramters['sign'], $paramters);
        // 订单支付验证
        $verifyOrderFinshed = $this->beforInspectPayOrderFinshed($paramters);
        return $verifyOrderFinshed && $verifySignSecret;
    }

    /**
     * 同步回调验证
     * @return bool
     */
    public function payReturn(): bool
    {
        return true;
    }

    /**
     * @param array $paramters
     * @return bool
     */
    public function beforeCheckRequestParamterForBinding(array $paramters): bool
    {
        $requiredKeys = [
            'orderAmt', 'upOrderId', 'status', 'orderId', 'merchantId', 'sign',
        ];
        return (count(array_intersect_key(array_flip($requiredKeys), $paramters)) >= count($requiredKeys)) &&
            $this->beforeInspectRequestPmsComplete($paramters);
    }

    /**
     * @param array $paramters
     * @return bool
     */
    protected function beforInspectPayOrderFinshed(array $paramters): bool
    {
        return $this->checkPayOrderStateComplete($paramters['orderId']);
    }

    /**
     * @param array $paramters
     * @return bool
     */
    protected function beforeInspectRequestPmsComplete(array $paramters): bool
    {
        return $paramters['status'] == '00';
    }

    /**
     * @param        $trade_no 交易订单号
     * @param string $action   查询方式
     * @return bool
     */
    public function checkPayOrderStateComplete($trade_no, $action = '*'): bool
    {
        $orderResponse = $this->orderQuery($trade_no, $action);
        if (!$orderResponse) return false;
        return $orderResponse['rspCode'] == '00' && $orderResponse['status'] == '00' &&
            $orderResponse['orderId'] == $trade_no;
    }

    /**
     * 签名验证
     * @param string $signurate
     * @param array  $paramters
     * @return bool
     */
    public function signurateVerify(string $signurate, array $paramters)
    {
        return strtoupper($signurate) === $this->buildSignature($paramters, CASE_UPPER);
    }

    /**
     * @access protected
     * @param array $array
     */
    protected function setInput(array $array)
    {
        $this->rawBody = $array;
    }

    /**
     * 获取请求参数值
     * @param        $name
     * @param string $default
     * @return mixed|string
     */
    public function getInput($name, $default = '')
    {
        return isset($this->rawBody[$name]) ? $this->rawBody[$name] : $default;
    }

    /**
     * 请求参数转换
     * @param $paramter
     * @return array|false|mixed|string
     */
    protected function toArray($paramter)
    {
        $value = $paramter;
        if ($paramter instanceof \Closure) {
            $value = $paramter($this);
        } else if ($paramter instanceof \StdClass) {
            $value = (array)$paramter;
        } else if (is_object($paramter)) {
            $value = get_object_vars($paramter);
        } else if (is_string($paramter)) {
            if (!is_null($data = json_decode($paramter, true))) {
                $value = $data;
            } else {
                $value = [$paramter];
            }
        }
        return $value;
    }

    /**
     * 请求方法
     * @param mixed ...$vars
     * @return string
     */
    protected function action(...$vars)
    {
        $domain = $this->getConfig('apidomain');
        if(!empty($domain) && strpos($domain,'.')){
            $apiUrl = 'http://'.$domain.'%s';
        }else{
            $apiUrl = 'http://www.zzhengt.com%s';
        }
        return sprintf($apiUrl, ...$vars);
    }

    /**
     * @param array $_array
     * @param int   $case
     * @return string
     */
    public function buildSignature(array $_array, $case = CASE_UPPER)
    {
        $_array    = $this->argSortArray($this->filterArray($_array));
        $prestr    = $this->createLinkString($_array) . $this->separator . 'key=' . $this->getConfig('member_key');
        $signature = md5($prestr);
        return $case & CASE_UPPER ? strtoupper($signature) : strtolower($signature);
    }

    /**
     * 响应回调成功结果内容
     * @return string
     */
    public function returnResponseContent()
    {
        return 'ok';
    }

    /**
     * @param array $_array
     * @return array
     */
    protected function filterArray(array $_array)
    {
        $new_array = [];
        foreach ($_array as $key => $val) {
            if ('' === $val || in_array($key, (is_array($this->ignoreKeys) ? $this->ignoreKeys : [$this->ignoreKeys])))
                continue;
            $new_array[$key] = $_array[$key];
        }
        return $new_array;
    }

    /**
     * @param      $_array
     * @param bool $encode
     * @return string
     */
    protected function createLinkString($_array, $encode = false): string
    {
        $arg       = '';
        $separator = $this->separator;
        foreach ($_array as $key => $val) {
            if (is_array($val)) {
                $val = $this->createLinkString($val, $encode);
            }
            $arg .= $key . '=' . ($encode ? urlencode($val) : $val) . $separator;
        }
        return rtrim($arg, $separator);
    }

    /**
     * @param array $array
     * @return array
     */
    public function argSortArray(array $array): array
    {
        ksort($array);
        reset($array);
        return $array;
    }

    public function order($name = null, $value = null, $default = '')
    {
        if (is_null($value) && is_string($name)) {
            return $this->orderModel[$name] ?? ($default ?: '');
        } else if (is_array($name)) {
            $this->orderModel = array_merge($this->orderModel, $name);
        } else if (!is_null($value)) {
            $this->orderModel[$name] = $value;
        }
        return $this;
    }

    /**
     * @param string|null $name
     * @param string      $default
     * @return array|mixed|string
     */
    public function getConfig(string $name = null, $default = '')
    {
        if (empty($name)) {
            return $this->config;
        }
        $name = strtolower($name);
        if (false === stripos($name, '.')) {
            return isset($this->config[$name]) ? $this->config[$name] : $default;
        }
        $name   = array_filter(explode('.', $name));
        $config = $this->config;
        foreach ($name as $val) {
            if (isset($config[$val])) {
                $config = $config[$val];
            } else {
                return $default;
            }
        }
        return $config;
    }

    /**
     * @param             $config
     * @param string|null $name
     * @return array
     */
    public function setConfig($config, string $name = null)
    {
        if (!empty($name)) {
            if (isset($this->config[$name])) {
                $result = is_array($this->config[$name]) ? array_merge($this->config, $config) : $config;
            } else {
                $result = $config;
            }
            $this->config[$name] = $config;
        } else {
            $result = $this->config = array_merge($this->config, array_change_key_case($config));
        }
        return $result;
    }

    /**
     * @param array $config
     * @return XhPlatFormPay
     */
    static function getInstance($config = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * 请求访问
     * @param       $url               目标地址
     * @param array $httpConfig        配置参数
     * @param int   $timeOut           请求超时上限
     * @param array $httpHeaders       请求头部
     * @return array
     */
    public function requestSend($url, array $httpConfig = [], $timeOut = 60, array $httpHeaders = [])
    {
        $params = is_array($httpConfig['body']) ? http_build_query($httpConfig['body']) : $httpConfig['body'];
        $requestMethod = $httpConfig['type'];
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeOut);
        if ($requestMethod === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.193 Safari/537.36');
        $response = curl_exec($curl);
        if (false === $response) {
            $result = ['errCode' => -999, 'errMsg' => curl_error($curl)];
        } else if (($httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE)) && $httpCode != 200) {
            $result = ['errCode' => -3, 'errMsg' => "({$httpCode})->" . curl_error($curl), 'rawBody' => $response, 'httpCode' => $httpCode];
        } else {
            $result = ['errCode' => 0, 'errMsg' => 'Ok', 'httpCode' => $httpCode, 'resultData' => json_decode($response, true), 'rawBody' => $response];
        }
        curl_close($curl);
        return $result;
    }

    /**
     * 对象克隆
     */
    public function __clone()
    {
        // TODO: Implement __clone() method.
        $this->config     = [];
        $this->orderModel = [];
    }

    /**
     * 设置错误信息到容器
     * @param $error
     */
    protected function setError($error): void
    {
        $this->error = $error;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

}

?>