<?php
// 支付宝JS支付页面

if(!defined('IN_PLUGIN'))exit();
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="utf-8" />
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
    <link href="<?php echo $cdnpublic?>ionic/1.3.2/css/ionic.min.css" rel="stylesheet" />
</head>
<body>
<div class="bar bar-header bar-light" align-title="center">
	<h1 class="title">支付宝支付</h1>
</div>
<div class="has-header" style="padding: 5px;position: absolute;width: 100%;">
<div class="text-center" style="color: #a09ee5;">
<i class="icon ion-information-circled" style="font-size: 80px;"></i><br>
<span>正在跳转...</span>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });

var tradeNO = '<?php echo $alipay_trade_no?>';

function Alipayready(callback) {
    // 如果jsbridge已经注入则直接调用
    if (window.AlipayJSBridge) {
        callback && callback();
    } else {
        // 如果没有注入则监听注入的事件
        document.addEventListener('AlipayJSBridgeReady', callback, false);
    }
}
function AlipayJsPay() {
	Alipayready(function(){
		AlipayJSBridge.call("tradePay",{
			tradeNO: tradeNO
		}, function(result){
			var msg = "";
			if(result.resultCode == "9000"){
				loadmsg();
			}else if(result.resultCode == "8000"){
				msg = "正在处理中";
			}else if(result.resultCode == "4000"){
				msg = "订单支付失败";
			}else if(result.resultCode == "6002"){
				msg = "网络连接出错";
			}
			if (msg!="") {
				layer.msg(msg);
			}
		});
	});
}
// 检查是否支付完成
function loadmsg() {
	$.ajax({
		type: "GET",
		dataType: "json",
		url: "/getshop.php",
		timeout: 10000, //ajax请求超时时间10s
		data: {type: "wxpay", trade_no: "<?php echo TRADE_NO?>"}, //post数据
		success: function (data, textStatus) {
			//从服务器得到数据，显示数据并继续查询
			if (data.code == 1) {
				layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.01,time: 15000});
				window.location.href=<?php echo $redirect_url?>;
			}else{
				setTimeout("loadmsg()", 2000);
			}
		},
		//Ajax请求超时，继续查询
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			if (textStatus == "timeout") {
				setTimeout("loadmsg()", 1000);
			} else { //异常
				setTimeout("loadmsg()", 3000);
			}
		}
	});
}
window.onload = AlipayJsPay();
</script>
</div>
</div>
</body>
</html>