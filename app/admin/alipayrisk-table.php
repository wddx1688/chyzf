<?php
/**
 * 支付宝风险交易记录
**/
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");


$sqls="";
$links='';
if(isset($_GET['channel']) && $_GET['channel']>0) {
	$channel = intval($_GET['channel']);
	$sqls.=" AND `channel`='$channel'";
	$links.='&channel='.$channel;
}

if(isset($_GET['value']) && !empty($_GET['value'])) {
	$sql=" `{$_GET['column']}`='{$_GET['value']}'";
	$sql.=$sqls;
	$numrows=$DB->getColumn("SELECT count(*) from pre_alipayrisk WHERE{$sql}");
	$con='包含 '.$_GET['value'].' 的共有 <b>'.$numrows.'</b> 条记录';
	$link='&my=search&column='.$_GET['column'].'&value='.$_GET['value'].$links;
}else{
	$sql=" 1";
	$sql.=$sqls;
	$numrows=$DB->getColumn("SELECT count(*) from pre_alipayrisk WHERE{$sql}");
	$con='共有 <b>'.$numrows.'</b> 条记录';
	$link=$links;
}
?>
	  <div class="table-responsive">
<?php echo $con?>
        <table class="table table-striped table-bordered table-hover table-vcenter">
          <thead><tr><th>ID</th><th>通道ID</th><th>支付宝PID</th><th>风险交易号</th><th>风险类型</th><th>风险情况描述</th><th>用户投诉内容</th><th>记录时间</th><th>状态</th><th>操作</th></tr></thead>
          <tbody>
<?php
$pagesize=30;
$pages=ceil($numrows/$pagesize);
$page=isset($_GET['page'])?intval($_GET['page']):1;
$offset=$pagesize*($page - 1);

$rs=$DB->query("SELECT * FROM pre_alipayrisk WHERE{$sql} order by id desc limit $offset,$pagesize");
while($res = $rs->fetch())
{
echo '<tr><td><b>'.$res['id'].'</b></td><td>'.$res['channel'].'</td><td>'.($res['smid']?$res['smid']:$res['pid']).'</td><td><a href="./order.php?column=api_trade_no&value='.$res['tradeNos'].'" target="_blank">'.$res['tradeNos'].'</a></td><td>'.$res['risktype'].'</td><td>'.$res['risklevel'].(!empty($res['riskDesc'])?'&nbsp;<a href="javascript:layer.alert(\''.$res['riskDesc'].'\')" title="风险定位原因说明" class="btn btn-xs btn-default"><i class="glyphicon glyphicon-info-sign"></i></a>':'').'</td><td>'.$res['complainText'].($res['complainTime']?'('.$res['complainTime'].')':null).'</td><td>'.$res['date'].'</td><td>'.($res['status']==1?'<font color=green>已处理</font>':'<font color=blue>未处理</font>').'</td><td><a href="alipayrisk_process.php?id='.$res['id'].'" class="btn btn-xs btn-default" target="_blank">处理</a></td></tr>';
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
