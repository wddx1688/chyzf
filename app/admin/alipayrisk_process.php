<?php
include("../includes/common.php");
$title='支付宝风险交易处理';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-sm-12 col-md-10 col-lg-8 center-block" style="float: none;">
<?php

$id = isset($_GET['id'])?intval($_GET['id']):showmsg('参数不能为空',4);
$row=$DB->getRow("select * from pre_alipayrisk where id='$id' limit 1");
if(!$row)showmsg('记录不存在',4);

if(isset($_POST['submit'])){
	if(!checkRefererHost())exit();
	$plat_account = trim($_POST['plat_account']);
	$process_code = trim($_POST['process_code']);
	if(empty($plat_account) || empty($process_code))showmsg('支付密码错误',3);
	$channel = \lib\Channel::get($row['channel']);
	if(!$channel)showmsg('当前支付通道信息不存在',4);
	define("IN_PLUGIN", true);
	define("PAY_ROOT", PLUGIN_ROOT.'alipay/');
	require_once PAY_ROOT."inc/AlipaySecurityService.php";
	$alipaySevice = new AlipaySecurityService($config); 
	$result = $alipaySevice->customerriskSend($plat_account,$row['tradeNos'],$row['pid'],$process_code);
	if(!empty($result['code'])&&$result['code'] == 10000){
		$result='提交成功！';
		showmsg($result,1);
	}else{
		$result='提交失败 ['.$result['sub_code'].']'.$result['sub_msg'];
		showmsg($result,4);
	}
}

?>

	  <div class="panel panel-primary">
        <div class="panel-heading"><h3 class="panel-title">支付宝风险交易处理</h3></div>
        <div class="panel-body">
          <form action="?id=<?php echo $id?>" method="POST" role="form">
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">记录ID</div>
				<input type="text" value="<?php echo $id?>" class="form-control" disabled/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">风险类型</div>
				<input type="text" value="<?php echo $row['risktype']?>" class="form-control" disabled/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">风险情况描述</div>
				<input type="text" value="<?php echo $row['risklevel']?>" class="form-control" disabled/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">风险交易号</div>
				<input type="text" value="<?php echo $row['tradeNos']?>" class="form-control" disabled/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">合作者身份ID</div>
				<input type="text" value="<?php echo $row['pid']?>" class="form-control" disabled/>
			</div></div>
			
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">平台账号</div>
				<input type="text" name="plat_account" value="" class="form-control" required/>
			</div><font color="green">指第三方在商户平台注册成功后，平台给予的账户号</font></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">处理结果</div>
				<select class="form-control" name="process_code"><option value="01">01：暂停发货</option><option value="02">02：延迟结算</option><option value="03">03：关停账户</option><option value="04">04：暂停发货+关停账户</option><option value="05">05：延迟结算+关停账户</option><option value="06">06：其他</option><option value="07">07：平台进行退款退订</option><option value="08">08：平台跟用户沟通后，用户撤诉</option><option value="09">09：未进行处理</option></select>
			</div><font color="green">商户对该账户采取的措施</font></div>
            <p><input type="submit" name="submit" value="提交" class="btn btn-primary form-control"/></p>
          </form>
		</div>
		<div class="panel-footer">
          <span class="glyphicon glyphicon-info-sign"></span> 用于向支付宝返回针对风险交易的处理方式
        </div>
      </div>
    </div>
  </div>