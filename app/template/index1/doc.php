<?php
if(!defined('IN_CRONLITE'))exit();
require INDEX_ROOT.'head.php';
$paytype = $DB->getAll("SELECT * FROM pre_type WHERE status=1 ORDER BY id ASC");
?>
<div class="container api_doc">
	<div class="api_doc_bar">
		<dl>
			<dt>
									<a href="#pay0">页面跳转支付</a>
								</dt>
		</dl>
    <dl>
			<dt>
									<a href="#pay1">API接口支付</a>
								</dt>
		</dl>
		<dl>
			<dt>
									<a href="#pay2">支付结果通知</a>
								</dt>
		</dl>
		<dl>
			<dt>
									<a href="#pay3">MD5签名算法</a>
								</dt>
		</dl>
    <dl>
      <dt>
									<a href="#pay4">支付方式列表</a>
								</dt>
		</dl>
    <dl>
      <dt>
									<a href="#pay5">设备类型列表</a>
								</dt>
		</dl>
		<dl>
			<dt>
									<a href="#api1">[API]查询商户信息</a>
								</dt>
		</dl>
		<dl>
			<dt>
									<a href="#api3">[API]查询结算记录</a>
								</dt>
		</dl>
		<dl>
			<dt>
									<a href="#api4">[API]查询单个订单</a>
								</dt>
		</dl>
		<dl>
			<dt>
									<a href="#api5">[API]批量查询订单</a>
								</dt>
		</dl>

		<dl>
			<dt>
									<a href="#sdk0">SDK下载</a>
								</dt>
		</dl>

	</div>
	<div class="api_doc_content">
  <?php include TEMPLATE_ROOT.'default/doc.inc.php';?>
	</div>
</div>
<?php require INDEX_ROOT.'foot.php';?>