<?php
/**
 * 商户列表
**/
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

function display_status($status, $uid){
	if($status == 1){
		return '<a href="javascript:setStatus('.$uid.',\'user\',0)"><font color=green><i class="fa fa-check-circle"></i>正常</font></a>';
	}else{
		return '<a href="javascript:setStatus('.$uid.',\'user\',1)"><font color=red><i class="fa fa-times-circle"></i>封禁</font></a>';
	}
}
function display_paystatus($status, $uid){
	if($status == 2){
		return '<a href="javascript:setStatus('.$uid.',\'pay\',1)"><font color=orange><i class="fa fa-exclamation-circle"></i>未审核</font></a>';
	}elseif($status == 1){
		return '<a href="javascript:setStatus('.$uid.',\'pay\',0)"><font color=green><i class="fa fa-check-circle"></i>支付</font></a>';
	}else{
		return '<a href="javascript:setStatus('.$uid.',\'pay\',1)"><font color=red><i class="fa fa-times-circle"></i>支付</font></a>';
	}
}
function display_settlestatus($status, $uid){
	if($status == 1){
		return '<a href="javascript:setStatus('.$uid.',\'settle\',0)"><font color=green><i class="fa fa-check-circle"></i>结算</font></a>';
	}else{
		return '<a href="javascript:setStatus('.$uid.',\'settle\',1)"><font color=red><i class="fa fa-times-circle"></i>结算</font></a>';
	}
}

$usergroup = [0=>'默认用户组'];
$rs = $DB->getAll("SELECT * FROM pre_group");
foreach($rs as $row){
	$usergroup[$row['gid']] = $row['name'];
}
unset($rs);

if(isset($_GET['dstatus']) && $_GET['dstatus']!='0') {
	$dstatus = explode('_',$_GET['dstatus']);
	$sqls=" `{$dstatus[0]}`='{$dstatus[1]}'";
}

if(isset($_GET['value']) && !empty($_GET['value'])) {
	$sql=" `{$_GET['column']}`='{$_GET['value']}'";
	if($sqls)$sql.=" AND ".$sqls;
	$numrows=$DB->getColumn("SELECT count(*) from pre_user WHERE{$sql}");
	$con='包含 '.$_GET['value'].' 的共有 <b>'.$numrows.'</b> 个商户';
	$link='&column='.$_GET['column'].'&value='.$_GET['value'];
}else{
	$numrows=$DB->getColumn("SELECT count(*) from pre_user WHERE 1");
	$sql=" 1";
	if($sqls)$sql.=" AND ".$sqls;
	$con='共有 <b>'.$numrows.'</b> 个商户';
}
if (isset($_GET['dstatus']) && $_GET['dstatus']!='0') {
	$link.="&dstatus".$_GET['dstatus'];
}

?>
	  <div class="table-responsive">
<?php echo $con?>
        <table class="table table-striped table-bordered table-vcenter">
          <thead><tr><th>商户号/用户组</th><th>余额</th><th>结算账号/姓名</th><th>联系方式</th><th>域名/添加时间</th><th>状态</th><th>操作</th></tr></thead>
          <tbody>
<?php
$pagesize=30;
$pages=ceil($numrows/$pagesize);
$page=isset($_GET['page'])?intval($_GET['page']):1;
$offset=$pagesize*($page - 1);

$rs=$DB->query("SELECT * FROM pre_user WHERE{$sql} order by uid desc limit $offset,$pagesize");
while($res = $rs->fetch())
{
echo '<tr><td><b>'.$res['uid'].'</b>[<a href="javascript:showKey('.$res['uid'].',\''.$res['key'].'\')">查看密钥</a>]<br/><span onclick="editGroup('.$res['uid'].','.$res['gid'].')" style="color:blue">'.$usergroup[$res['gid']].'</span></td><td class="money"><b><a href="javascript:showRecharge('.$res['uid'].')">'.$res['money'].'</a></b></td><td><span onclick="inputInfo('.$res['uid'].')">'.($res['settle_id']==2?'<font color="green">WX:</font>':null).($res['settle_id']==3?'<font color="green">QQ:</font>':null).$res['account'].'<br/>'.$res['username'].'</span></td><td>QQ:<a href="tencent://message/?uin='.$res['qq'].'&amp;Site=Epay&amp;Menu=yes">'.$res['qq'].'</a><br/>'.($res['phone']?$res['phone']:$res['email']).'</td><td>'.$res['url'].'<br/>'.$res['addtime'].'</td><td>'.display_status($res['status'],$res['uid']).'&nbsp;'.($res['cert']==1?'<a href="javascript:showCert('.$res['uid'].')" title="查看实名认证信息"><font color=green><i class="fa fa-check-circle-o"></i>已实名</font></a>':'<a href="javascript:showCert('.$res['uid'].')" title="查看实名认证信息"><font color=grey><i class="fa fa-times-circle"></i>未实名</font></a>').'<br/>'.display_paystatus($res['pay'],$res['uid']).'&nbsp;'.display_settlestatus($res['settle'],$res['uid']).'</td><td><a href="./uset.php?my=edit&uid='.$res['uid'].'" class="btn btn-xs btn-info">编辑</a>&nbsp;<a href="./sso.php?uid='.$res['uid'].'" target="_blank" class="btn btn-xs btn-success">登录</a>&nbsp;<a href="./uset.php?my=delete&uid='.$res['uid'].'" class="btn btn-xs btn-danger" onclick="return confirm(\'你确实要删除此商户吗？\');">删除</a><br/><a href="./order.php?uid='.$res['uid'].'" target="_blank" class="btn btn-xs btn-default">订单</a>&nbsp;<a href="./slist.php?column=uid&value='.$res['uid'].'" target="_blank" class="btn btn-xs btn-default">结算</a>&nbsp;<a href="./record.php?column=uid&value='.$res['uid'].'" target="_blank" class="btn btn-xs btn-default">明细</a></td></tr>';
}
?>
          </tbody>
        </table>
      </div>
<?php
echo'<div class="text-center"><ul class="pagination">';
$first=1;
$prev=$page-1;
$next=$page+1;
$last=$pages;
if ($page>1)
{
echo '<li><a href="javascript:void(0)" onclick="listTable(\'page='.$first.$link.'\')">首页</a></li>';
echo '<li><a href="javascript:void(0)" onclick="listTable(\'page='.$prev.$link.'\')">&laquo;</a></li>';
} else {
echo '<li class="disabled"><a>首页</a></li>';
echo '<li class="disabled"><a>&laquo;</a></li>';
}
$start=$page-10>1?$page-10:1;
$end=$page+10<$pages?$page+10:$pages;
for ($i=$start;$i<$page;$i++)
echo '<li><a href="javascript:void(0)" onclick="listTable(\'page='.$i.$link.'\')">'.$i .'</a></li>';
echo '<li class="disabled"><a>'.$page.'</a></li>';
for ($i=$page+1;$i<=$end;$i++)
echo '<li><a href="javascript:void(0)" onclick="listTable(\'page='.$i.$link.'\')">'.$i .'</a></li>';
if ($page<$pages)
{
echo '<li><a href="javascript:void(0)" onclick="listTable(\'page='.$next.$link.'\')">&raquo;</a></li>';
echo '<li><a href="javascript:void(0)" onclick="listTable(\'page='.$last.$link.'\')">尾页</a></li>';
} else {
echo '<li class="disabled"><a>&raquo;</a></li>';
echo '<li class="disabled"><a>尾页</a></li>';
}
echo'</ul></div>';
