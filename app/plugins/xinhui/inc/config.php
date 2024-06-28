<?php
/**
 * Created By YunLan
 * User: _cloud
 * Date: 2021/2/25
 * Time: 20:28
 */

return [
    'member_id'  => trim($channel['appid']), // 商户ID
    'member_key' => trim($channel['appkey']), // 商户秘钥
	'apidomain' => trim($channel['appurl']), // 支付接口域名
];