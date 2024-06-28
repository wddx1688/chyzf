<?php
// QQ钱包手机扫码页面

if(!defined('IN_PLUGIN'))exit();
?>
<html lang="zh-cn">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
  <meta name="renderer" content="webkit"/>
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title>QQ钱包支付手机版</title>
  <link href="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet"/>
</head>
<body>

<div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">
<div class="panel panel-default">
	<div class="panel-heading" style="text-align: center;"><h3 class="panel-title">
		<img src="/assets/icon/qqpay.ico">QQ钱包支付手机版
	</div>
		<div class="list-group" style="text-align: center;">
			<div class="list-group-item"><h1>￥<?php echo $order['realmoney']?><h1></div>
			<div class="list-group-item">商品名称：<?php echo $order['name']?><br/>商户订单号：<?php echo $order['trade_no']?><br/>创建时间：<?php echo $order['addtime']?></div>
			<div class="list-group-item"><a href="" id="openUrl" class="btn btn-primary btn-block">跳转到QQ支付</a></div>
			<div class="list-group-item"><a href="#" onclick="checkresult()" class="btn btn-success btn-block">检测支付状态</a></div>
			<?php if(PAY_PLUGIN=='qqpay'){?>
			<div class="list-group-item"><a href="/pay/qrcode/<?php echo TRADE_NO?>/" class="btn btn-default btn-sm btn-block">使用扫码支付</a></div>
			<?php }else{?>
			<div class="list-group-item"><a href="?qrcode=1" class="btn btn-default btn-sm btn-block">使用扫码支付</a></div>
			<?php }?>
		</div>
</div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
	var code_url = '<?php echo $code_url?>';
	var	url_scheme = 'mqqapi://forward/url?src_type=web&style=default&=1&version=1&url_prefix='+window.btoa(code_url);
    // 检查是否支付完成
    function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            timeout: 10000, //ajax请求超时时间10s
            data: {type: "qqpay", trade_no: "<?php echo $order['trade_no']?>"}, //post数据
            success: function (data, textStatus) {
                //从服务器得到数据，显示数据并继续查询
                if (data.code == 1) {
					layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.1,time: 15000});
					setTimeout(window.location.href=data.backurl, 1000);
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
	function checkresult() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            timeout: 10000, //ajax请求超时时间10s
            data: {type: "qqpay", trade_no: "<?php echo $order['trade_no']?>"}, //post数据
            success: function (data, textStatus) {
                //从服务器得到数据，显示数据并继续查询
                if (data.code == 1) {
					layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.1,time: 15000});
					setTimeout(window.location.href=data.backurl, 1000);
                }else{
					layer.msg('您还未完成付款，请继续付款', {shade: 0,time: 1500});
				}
            },
            //Ajax请求超时，继续查询
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.msg('服务器错误');
            }
        });
    }
	window.onload = function(){
		document.getElementById("openUrl").href = url_scheme; 
		window.location.href = url_scheme;
		setTimeout("loadmsg()", 2000);
	}
</script>
</body>
</html>