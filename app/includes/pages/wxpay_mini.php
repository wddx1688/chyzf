<?php
// 微信小程序手机支付页面

if(!defined('IN_PLUGIN'))exit();
?>
<html lang="zh-cn">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
  <meta name="renderer" content="webkit"/>
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title>微信支付手机版</title>
  <link href="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet"/>
</head>
<body>
<br>
<div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">
    <div class="panel panel-primary">
        <div class="panel-heading" style="text-align: center;"><h3 class="panel-title">
            <img src="/assets/icon/wechat.ico">微信支付手机版
        </div>
        <div class="list-group" style="text-align: center;">
            <div class="list-group-item list-group-item-info">~~~~~~~~~~~~~~~~</div>
            <div class="list-group-item">
                <font style="font-size:30px">应付金额：<?php echo $order['realmoney']?>元</font>
            </div>
            <div class="list-group-item"><font color="grey">点击支付将跳转到微信小程序，完成支付后请返回当前页面查看结果</font></div>
            <div class="list-group-item">
                <a href="<?php echo $code_url?>" class="btn btn-success btn-lg btn-block"
                   style="font-size:20px">点我继续支付</a>
            </div>
            <div class="list-group-item">
                <a href="#" onclick="checkresult()" class="btn btn-info btn-lg btn-block">支付完毕返回</a>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
    // 检查是否支付完成
    function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            timeout: 10000, //ajax请求超时时间10s
            data: {type: "wxpay", trade_no: "<?php echo $order['trade_no']?>"}, //post数据
            success: function (data, textStatus) {
                //从服务器得到数据，显示数据并继续查询
                if (data.code == 1) {
					layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.01,time: 15000});
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
            data: {type: "wxpay", trade_no: "<?php echo $order['trade_no']?>"}, //post数据
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
		window.location.href = '<?php echo $code_url?>';
		setTimeout("loadmsg()", 2000);
	}
</script>
</body>
</html>