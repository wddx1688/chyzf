<?php

require_once(PAY_ROOT.'vendor/autoload.php');
 
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
 
$clientId = $channel['appid'];
$clientSecret = $channel['appkey'];
$mode = $channel['appswitch'] == 1 ? 'sandbox' : 'live';
$apiContext = new ApiContext(
    new OAuthTokenCredential(
        $clientId,
        $clientSecret
    )
);
$apiContext->setConfig(
    array(
        'mode' => $mode,
        'log.LogEnabled' => false,
        'log.FileName' => '../PayPal.log',
        'log.LogLevel' => 'DEBUG', 
        'cache.enabled' => false
    )
);