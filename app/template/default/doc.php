<?php
if(!defined('IN_CRONLITE'))exit();
require INDEX_ROOT.'head.php';
$paytype = $DB->getAll("SELECT * FROM pre_type WHERE status=1 ORDER BY id ASC");
?>
<style type="text/css">
body{color:#000;}header { position: relative; }
.bann{ content:'';background-size:100%;background:#4280cb;background:-webkit-gradient(linear,0 0,0 100%,from(#4585d2),to(#4280cb));background:-moz-linear-gradient(top,#4585d2,#4280cb);background:linear-gradient(to bottom,#4585d2,#4280cb);top:0;left:0;z-index:-1;min-height:50px;width:100%}.fl .active{ color:#3F5061;background:#fff;border-color:#fff}
.api_block{margin-bottom: 4em;}
</style>

<div class="bann">


<div class="col-xs-12"style="text-align:center;">
<div class="h3"style="color:#ffffff;margin-top: 35px;margin-bottom: 30px;">开发文档</div>
                  
<div style="clear:both;"></div>
</div><div style="clear:both;"></div>
</div>


<div class="container">

  <!-- Docs nav
  ================================================== -->
  <div class="row">
    <div class="col-md-3 ">
      <div id="toc" class="bc-sidebar">
		<ul class="nav">
			<hr/>
			<li class="toc-h2"><a href="#pay0">页面跳转支付</a></li>
      <li class="toc-h2"><a href="#pay1">API接口支付</a></li>
			<li class="toc-h2"><a href="#pay2">支付结果通知</a></li>
			<li class="toc-h2"><a href="#pay3">MD5签名算法</a></li>
      <li class="toc-h2"><a href="#pay4">支付方式列表</a></li>
      <li class="toc-h2"><a href="#pay5">设备类型列表</a></li>
			<hr/>
			<li class="toc-h2"><a href="#api1">[API]查询商户信息</a></li>
			<li class="toc-h2"><a href="#api3">[API]查询结算记录</a></li>
			<li class="toc-h2"><a href="#api4">[API]查询单个订单</a></li>
			<li class="toc-h2"><a href="#api5">[API]批量查询订单</a></li>
			<hr/>
			<li class="toc-h2"><a href="#sdk0">SDK下载</a></li>
			<hr/>
		</ul>
	</div>
   </div>

    <div class="col-md-9">
      <article class="post page">
      	<section class="post-content">
		<hr/>
<?php include INDEX_ROOT.'doc.inc.php';?>

          </section>
      </article>
    </div>
  </div>

</div>

<?php require INDEX_ROOT.'foot.php';?>