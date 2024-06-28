<?php
//威富通配置文件
define("PAY_API_APPID", $channel['appid']);
define("PAY_API_KEY", $channel['appkey']);
define("PAY_API_URL", $channel['appurl'] ? $channel['appurl'] : 'https://pay.swiftpass.cn/pay/gateway');
class Config{
    private $cfg = array(
        'url'=>PAY_API_URL, /*支付接口请求地址 */
        'mchId'=>PAY_API_APPID,/* 测试商户号 */
        'key'=>PAY_API_KEY,  /* 测试密钥 */
        'version'=>'2.0',
        'sign_type'=>'MD5'
       );
    
    public function C($cfgName){
        return $this->cfg[$cfgName];
    }
}
?>