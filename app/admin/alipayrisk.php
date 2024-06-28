<?php
/**
 * 支付宝风险交易记录
**/
include("../includes/common.php");
$title='支付宝风险交易记录';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$alipay_channel = $DB->getAll("SELECT * FROM pre_channel WHERE plugin='alipay'");
?>
<div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">关闭</span>
				</button>
				<h4 class="modal-title">使用说明</h4>
			</div>
			<div class="modal-body">
<p>支付宝交易安全防护产品 <a href="https://nengli.alipay.com/abilityprod/detail?abilityCode=AM010501000000012613" target="_blank" rel="noreferrer">开通地址</a>，同时需要设置“应用网关地址”，用于接收支付宝的交易风险通知。</p>
<p>选择支付宝通道：<select id="alipay_channel"><?php foreach($alipay_channel as $channel){echo '<option value="'.$channel['id'].'">'.$channel['name'].'</option>';} ?></select></p>
<p>应用网关地址：<font color="green"><?php echo $siteurl?>pay/appgw/<span id="channel_id">0</span>/</font></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
			</div>
		</div>
	</div>
</div>

  <div class="container" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">
<form onsubmit="return searchRecord()" method="GET" class="form-inline">
  <div class="form-group">
    <label>搜索</label>
	<select name="column" class="form-control"><option value="tradeNos">风险交易号</option><option value="pid">支付宝PID</option><option value="risktype">风险类型</option></select>
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="value" placeholder="搜索内容" value="<?php echo @$_GET['value']?>">
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="channel" style="width: 80px;" placeholder="通道ID" value="<?php echo @$_GET['channel']?>">
  </div>
  <button type="submit" class="btn btn-primary">搜索</button>
  <a href="javascript:listTable('start')" class="btn btn-default" title="刷新列表"><i class="fa fa-refresh"></i></a>
  <a href="javascript:$('#myModal').modal('show');" class="btn btn-default">使用说明</a>
</form>

<div id="listTable"></div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
function listTable(query){
	var url = window.document.location.href.toString();
	var queryString = url.split("?")[1];
	query = query || queryString;
	if(query == 'start' || query == undefined){
		query = '';
		history.replaceState({}, null, './alipayrisk.php');
	}else if(query != undefined){
		history.replaceState({}, null, './alipayrisk.php?'+query);
	}
	layer.closeAll();
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'alipayrisk-table.php?'+query,
		dataType : 'html',
		cache : false,
		success : function(data) {
			layer.close(ii);
			$("#listTable").html(data)
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function searchRecord(){
	var column=$("select[name='column']").val();
	var value=$("input[name='value']").val();
	var channel=$("input[name='channel']").val();
	if(value==''){
		listTable('channel='+channel);
	}else{
		listTable('column='+column+'&value='+value+'&channel='+channel);
	}
	return false;
}
$(document).ready(function(){
	listTable();
	$("#alipay_channel").change(function () {
		$("#channel_id").text($(this).val());
	});
	$("#alipay_channel").change();
})
</script>