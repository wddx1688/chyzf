<?php
if (version_compare(PHP_VERSION, '7.1.0', '<')) {
    die('require PHP >= 7.1 !');
}
include("./includes/common.php");

$mod = isset($_GET['mod'])?$_GET['mod']:'index';

if($mod=='index'){
    if($conf['homepage']==2){
        echo '<html><frameset framespacing="0" border="0" rows="0" frameborder="0">
        <frame name="main" src="'.$conf['homepage_url'].'" scrolling="auto" noresize>
    </frameset></html>';
        exit;
    }elseif($conf['homepage']==1){
        exit("<script language='javascript'>window.location.href='/user/';</script>");
    }
}

$loadfile = \lib\Template::load($mod);
include $loadfile;