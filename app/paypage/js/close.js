if(navigator.userAgent.indexOf("AlipayClient/") > -1){
    function Alipayready(callback) {
        if (window.AlipayJSBridge) {
            callback && callback();
        } else {
            document.addEventListener('AlipayJSBridgeReady', callback, false);
        }
    }
    Alipayready(function(){
        $('#Close').click(function() {
            AlipayJSBridge.call('popWindow');
        });
    })
}else if(navigator.userAgent.indexOf("MicroMessenger/") > -1){
    if (typeof WeixinJSBridge == "undefined") {
        if (document.addEventListener) {
            document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
        } else if (document.attachEvent) {
            document.attachEvent('WeixinJSBridgeReady', jsApiCall);
            document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
        }
    } else {
        jsApiCall();
    }
    function jsApiCall() {
        $('#Close').click(function() {
            WeixinJSBridge.call('closeWindow');
        });
    }
}else if(navigator.userAgent.indexOf("QQ/") > -1){
    $('#Close').hide();
}else {
    $('#Close').click(function() {
        window.opener=null;window.close();
    });
}