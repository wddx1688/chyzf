<?php
include("../includes/common.php");
$title='企业付款';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<?php
$app = isset($_GET['app'])?$_GET['app']:'alipay';

if(isset($_POST['submit'])){
	if(!checkRefererHost())exit();
	$out_biz_no = trim($_POST['out_biz_no']);
	if(!isset($_POST['paypwd']) || $_POST['paypwd']!==$conf['admin_paypwd'])showmsg('支付密码错误',3);
	$payee_account = trim($_POST['payee_account']);
	$payee_real_name = trim($_POST['payee_real_name']);
	$money = trim($_POST['money']);
	if($app=='alipay' || $app=='bank'){
		$payer_show_name = trim($_POST['payer_show_name']);
		if(!empty($payer_show_name))$conf['transfer_name']=$payer_show_name;
		$channel = \lib\Channel::get($conf['transfer_alipay']);
		if(!$channel)showmsg('当前支付通道信息不存在',4);
	}elseif($app=='wxpay'){
		$desc = trim($_POST['desc']);
		if(!empty($desc))$conf['transfer_desc']=$desc;
		$channel = \lib\Channel::get($conf['transfer_wxpay']);
		if(!$channel)showmsg('当前支付通道信息不存在',4);
	}elseif($app=='qqpay'){
		if (!is_numeric($payee_account) || strlen($payee_account)<6 || strlen($payee_account)>10)showmsg('QQ号码格式错误',3);
		$desc = trim($_POST['desc']);
		if(!empty($desc))$conf['transfer_desc']=$desc;
		$channel = \lib\Channel::get($conf['transfer_qqpay']);
		if(!$channel)showmsg('当前支付通道信息不存在',4);
	}else{
		showmsg('参数错误',4);
	}
	$result = transfer_do($app, $channel, $out_biz_no, $payee_account, $payee_real_name, $money);

	if($result['code']==0 && $result['ret']==1){
		$result='转账成功！转账单据号:'.$result['orderid'].' 支付时间:'.$result['paydate'];
		showmsg($result,1);
	}else{
		$result='转账失败 '.$result['msg'];
		showmsg($result,4);
	}
}

$out_biz_no = date("YmdHis").rand(11111,99999);
?>

	  <div class="panel panel-primary">
        <div class="panel-heading"><h3 class="panel-title">企业付款</h3></div>
        <div class="panel-body">
		<ul class="nav nav-tabs">
			<li class="<?php echo $app=='alipay'?'active':null;?>"><a href="?app=alipay">支付宝</a></li><li class="<?php echo $app=='wxpay'?'active':null;?>"><a href="?app=wxpay">微信</a></li><li class="<?php echo $app=='qqpay'?'active':null;?>"><a href="?app=qqpay">QQ钱包</a></li><li class="<?php echo $app=='bank'?'active':null;?>"><a href="?app=bank">银行卡</a></li>
		</ul>
		<div class="tab-pane active" id="alipay">
<?php if($app=='alipay'){?>
          <form action="?app=alipay" method="POST" role="form">
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">交易号</div>
				<input type="text" name="out_biz_no" value="<?php echo $out_biz_no?>" class="form-control" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">支付宝账号</div>
				<input type="text" name="payee_account" value="" class="form-control" required placeholder="支付宝登录账号或支付宝UID"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">支付宝姓名</div>
				<input type="text" name="payee_real_name" value="" class="form-control" placeholder="不填写则不校验真实姓名"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">转账金额</div>
				<input type="text" name="money" value="" class="form-control" placeholder="RMB/元" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">付款方姓名</div>
				<input type="text" name="payer_show_name" value="" class="form-control" placeholder="可留空，默认为：<?php echo $conf['transfer_name']?>"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">支付密码</div>
				<input type="text" name="paypwd" value="" class="form-control" required/>
			</div></div>
            <p><input type="submit" name="submit" value="立即转账" class="btn btn-primary form-control"/></p>
			<p><a href="javascript:alipayQuery()" class="btn btn-block btn-default">查询支付宝账户余额</a></p>
          </form>
<?php }elseif($app=='wxpay'){?>
          <form action="?app=wxpay" method="POST" role="form">
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">交易号</div>
				<input type="text" name="out_biz_no" value="<?php echo $out_biz_no?>" class="form-control" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">Openid</div>
				<input type="text" name="payee_account" value="" class="form-control" required placeholder="微信Openid"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">真实姓名</div>
				<input type="text" name="payee_real_name" value="" class="form-control" placeholder="不填写则不校验真实姓名"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">转账金额</div>
				<input type="text" name="money" value="" class="form-control" placeholder="RMB/元" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">转账备注</div>
				<input type="text" name="desc" value="" class="form-control" placeholder="可留空，默认为：<?php echo $conf['transfer_desc']?>"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">支付密码</div>
				<input type="text" name="paypwd" value="" class="form-control" required/>
			</div></div>
            <p><input type="submit" name="submit" value="立即转账" class="btn btn-primary form-control"/></p>
          </form>
		  <font color="green">Openid获取地址，在微信打开：<?php echo $siteurl?>user/openid.php</font>
<?php }elseif($app=='qqpay'){?>
          <form action="?app=qqpay" method="POST" role="form">
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">交易号</div>
				<input type="text" name="out_biz_no" value="<?php echo $out_biz_no?>" class="form-control" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">收款方QQ</div>
				<input type="text" name="payee_account" value="" class="form-control" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">真实姓名</div>
				<input type="text" name="payee_real_name" value="" class="form-control" placeholder="不填写则不校验真实姓名"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">转账金额</div>
				<input type="text" name="money" value="" class="form-control" placeholder="RMB/元" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">转账备注</div>
				<input type="text" name="desc" value="" class="form-control" placeholder="可留空，默认为：<?php echo $conf['transfer_desc']?>"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">支付密码</div>
				<input type="text" name="paypwd" value="" class="form-control" required/>
			</div></div>
            <p><input type="submit" name="submit" value="立即转账" class="btn btn-primary form-control"/></p>
          </form>
<?php }elseif($app=='bank'){?>
          <form action="?app=bank" method="POST" role="form">
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">交易号</div>
				<input type="text" name="out_biz_no" value="<?php echo $out_biz_no?>" class="form-control" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">银行卡号</div>
				<input type="text" name="payee_account" value="" class="form-control" required placeholder="收款方银行卡号"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">姓名</div>
				<input type="text" name="payee_real_name" value="" class="form-control" placeholder="收款方银行账户名称"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">转账金额</div>
				<input type="text" name="money" value="" class="form-control" placeholder="RMB/元" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">付款方姓名</div>
				<input type="text" name="payer_show_name" value="" class="form-control" placeholder="可留空，默认为：<?php echo $conf['transfer_name']?>"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">支付密码</div>
				<input type="text" name="paypwd" value="" class="form-control" required/>
			</div></div>
            <p><input type="submit" name="submit" value="立即转账" class="btn btn-primary form-control"/></p>
			<p><a href="javascript:alipayQuery()" class="btn btn-block btn-default">查询支付宝账户余额</a></p>
          </form>
<?php }?>
        </div>
		</div>
		<div class="panel-footer">
          <span class="glyphicon glyphicon-info-sign"></span> 交易号可以防止重复转账，同一个交易号只能提交同一次转账。<br/>
		  <a href="./set.php?mod=account">修改支付密码</a>
        </div>
      </div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
function alipayQuery(){
	layer.prompt({title: '填写当前支付宝的UID', value: $.cookie('alipay_user_id'), formType: 0}, function(text, index){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : 'POST',
			url : 'ajax.php?act=alipayQuery',
			dataType : 'json',
			data : {alipay_user_id: text},
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					$.cookie('alipay_user_id', text, {expires:30});
					layer.alert('可用于支付或提现的余额：'+data.amount+'元');
				}else{
					layer.alert(data.msg, {icon: 2})
				}
			},
			error:function(data){
				layer.msg('服务器错误');
				return false;
			}
		});
	});
}
</script>