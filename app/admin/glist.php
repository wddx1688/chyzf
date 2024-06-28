<?php
/**
 * 用户组设置
**/
include("../includes/common.php");
$title='用户组设置';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
<style>
.table>tbody>tr>td{vertical-align: middle;}</style>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-10 center-block" style="float: none;">
<?php

$paytype = [];
$rs = $DB->getAll("SELECT * FROM pre_type WHERE status=1 ORDER BY id ASC");
foreach($rs as $row){
	$paytype[$row['id']] = $row['showname'];
}
unset($rs);

function display_info($info){
	global $paytype;
	$result = '';
	$arr = json_decode($info, true);
	foreach($arr as $k=>$v){
		if($v['channel']==0)continue;
		$result .= $paytype[$k].'('.$v['channel'].'):'.$v['rate'].',';
	}
	return substr($result,0,-1);
}

$list = $DB->getAll("SELECT * FROM pre_group ORDER BY gid ASC");
?>
<div class="modal" id="modal-store" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content animated flipInX">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span
							aria-hidden="true">&times;</span><span
							class="sr-only">Close</span></button>
				<h4 class="modal-title" id="modal-title">用户组修改/添加</h4>
			</div>
			<div class="modal-body">
				<form class="form-horizontal" id="form-store">
					<input type="hidden" name="action" id="action"/>
					<input type="hidden" name="gid" id="gid"/>
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right">显示名称</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" name="name" id="name" placeholder="不要与其他用户组名称重复">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">通道费率</label>
						<div class="col-sm-10">
							<table class="table">
							  <thead><tr><th style="min-width:100px">支付方式</th><th>选择支付通道</th><th>填写分成比例</th></tr></thead>
							  <tbody>
<?php
foreach($paytype as $key=>$value)
{
$select = '';
$rs = $DB->getAll("SELECT * FROM pre_channel WHERE type='$key' AND status=1");
foreach($rs as $row){
	$select .= '<option value="'.$row['id'].'" rate="'.$row['rate'].'" type="channel">'.$row['name'].'</option>';
}
$rs = $DB->getAll("SELECT * FROM pre_roll WHERE type='$key' AND status=1");
foreach($rs as $row){
	$select .= '<option value="'.$row['id'].'" rate="'.$row['rate'].'" type="roll">'.$row['name'].'</option>';
}
echo '<tr><td><b>'.$value.'</b><input type="hidden" name="info['.$key.'][type]" value=""></td><td><select id="channel" name="info['.$key.'][channel]" class="form-control" onchange="changeChannel('.$key.')"><option value="0">关闭</option><option value="-1" type="channel">随机可用通道</option>'.$select.'</select></td><td><div class="input-group"><input type="text" class="form-control" name="info['.$key.'][rate]" id="rate" placeholder="百分数"><span class="input-group-addon">%</span></div></td></tr>';
}
?>
							  </tbody>
							</table>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">结算开关</label>
						<div class="col-sm-10">
							<select name="settle_open" id="settle_open" class="form-control">
								<option value="0">缺省（与系统设置一致）</option><option value="1">只开启每日自动结算</option><option value="2">只开启手动申请结算</option><option value="3">开启自动+手动结算</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">手动结算</label>
						<div class="col-sm-10">
							<select name="settle_type" id="settle_type" class="form-control">
								<option value="0">缺省（与系统设置一致）</option><option value="1">T+0（可提现全部余额）</option><option value="2">T+1（可提现1天前的余额）</option><option value="3">T+0秒到账（申请提现后通过转账接口转账）</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right">用户变量</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" name="settings" id="settings" placeholder="没有请勿填写，格式：变量名1:显示名1,变量名2:显示名2">
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
				<button type="button" class="btn btn-primary" id="store" onclick="save()">保存</button>
			</div>
		</div>
	</div>
</div>

<div class="panel panel-success">
   <div class="panel-heading"><h3 class="panel-title">系统共有 <b><?php echo count($list);?></b> 个用户组&nbsp;<span class="pull-right"><a href="javascript:addframe()" class="btn btn-default btn-xs"><i class="fa fa-plus"></i> 新增</a></span></h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>GID</th><th>用户组名称</th><th>通道与费率</th><th>操作</th></tr></thead>
          <tbody>
<?php
foreach($list as $res)
{
echo '<tr><td><b>'.$res['gid'].'</b></td><td>'.$res['name'].'</td><td>'.display_info($res['info']).'</td><td><a class="btn btn-xs btn-default" href="./ulist.php?column=gid&value='.$res['gid'].'">用户</a>&nbsp;<a class="btn btn-xs btn-info" onclick="editframe('.$res['gid'].')">编辑</a>&nbsp;<a class="btn btn-xs btn-danger" onclick="delItem('.$res['gid'].')">删除</a></td></tr>';
}
?>
          </tbody>
        </table>
      </div>
	  <div class="panel-footer">
          <span class="glyphicon glyphicon-info-sign"></span> 未设置用户组的用户是默认用户组，会自动使用已添加的可用支付通道和通道默认费率
        </div>
	</div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
function changeChannel(type){
	var rate = $("select[name='info["+type+"][channel]'] option:selected").attr('rate');
	var type2 = $("select[name='info["+type+"][channel]'] option:selected").attr('type');
	if($("input[name='info["+type+"][rate]']").val()=='')$("input[name='info["+type+"][rate]']").val(rate);
	$("input[name='info["+type+"][type]']").val(type2);
}
function addframe(){
	$("#modal-store").modal('show');
	$("#modal-title").html("新增用户组");
	$("#action").val("add");
	$("#gid").val('');
	$("#name").val('');
	$("#settings").val('');
	$("#settle_open").val(0);
	$("#settle_type").val(0);
}
function editframe(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=getGroup&gid='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#modal-store").modal('show');
				$("#modal-title").html("修改用户组");
				$("#action").val("edit");
				$("#gid").val(data.gid);
				$("#name").val(data.name);
				$("#settings").val(data.settings);
				$("#settle_open").val(data.settle_open);
				$("#settle_type").val(data.settle_type);
				$.each(data.info, function (i, res) {
					$("select[name='info["+i+"][channel]']").val(res.channel);
					$("input[name='info["+i+"][rate]']").val(res.rate);
					$("input[name='info["+i+"][type]']").val(res.type);
				})
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function save(){
	if($("#name").val()==''){
		layer.alert('请确保各项不能为空！');return false;
	}
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=saveGroup',
		data : $("#form-store").serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg,{
					icon: 1,
					closeBtn: false
				}, function(){
				  window.location.reload()
				});
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function delItem(id) {
	if(id==0){
		layer.msg('系统自带默认用户组不支持删除');
		return false;
	}
	var confirmobj = layer.confirm('你确实要删除此用户组吗？', {
	  btn: ['确定','取消']
	}, function(){
	  $.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=delGroup&gid='+id,
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				window.location.reload()
			}else{
				layer.alert(data.msg, {icon: 2});
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	  });
	}, function(){
	  layer.close(confirmobj);
	});
}
</script>