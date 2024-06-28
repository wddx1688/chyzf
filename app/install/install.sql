DROP TABLE IF EXISTS `pre_config`;
create table `pre_config` (
`k` varchar(32) NOT NULL,
`v` text NULL,
PRIMARY KEY  (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `pre_config` VALUES ('version', '2020');
INSERT INTO `pre_config` VALUES ('admin_user', 'admin');
INSERT INTO `pre_config` VALUES ('admin_pwd', '123456');
INSERT INTO `pre_config` VALUES ('admin_paypwd', '123456');
INSERT INTO `pre_config` VALUES ('homepage', '0');
INSERT INTO `pre_config` VALUES ('sitename', '聚合易支付');
INSERT INTO `pre_config` VALUES ('title', '聚合易支付 - 行业领先的免签约支付平台');
INSERT INTO `pre_config` VALUES ('keywords', '聚合易支付,支付宝免签约即时到账,财付通免签约,微信免签约支付,QQ钱包免签约,免签约支付');
INSERT INTO `pre_config` VALUES ('description', '聚合易支付是XX公司旗下的免签约支付产品，完美解决支付难题，一站式接入支付宝，微信，财付通，QQ钱包,微信wap，帮助开发者快速集成到自己相应产品，效率高，见效快，费率低！');
INSERT INTO `pre_config` VALUES ('orgname', 'XX公司');
INSERT INTO `pre_config` VALUES ('kfqq', '123456789');
INSERT INTO `pre_config` VALUES ('template', 'index1');
INSERT INTO `pre_config` VALUES ('pre_maxmoney', '1000');
INSERT INTO `pre_config` VALUES ('blockname', '云盘|网盘|Q币');
INSERT INTO `pre_config` VALUES ('blockalert', '温馨提醒该商品禁止出售，如有疑问请联系网站客服！');
INSERT INTO `pre_config` VALUES ('settle_open', '1');
INSERT INTO `pre_config` VALUES ('settle_type', '1');
INSERT INTO `pre_config` VALUES ('settle_money', '30');
INSERT INTO `pre_config` VALUES ('settle_rate', '0.5');
INSERT INTO `pre_config` VALUES ('settle_fee_min', '0.1');
INSERT INTO `pre_config` VALUES ('settle_fee_max', '20');
INSERT INTO `pre_config` VALUES ('settle_alipay', '1');
INSERT INTO `pre_config` VALUES ('settle_wxpay', '1');
INSERT INTO `pre_config` VALUES ('settle_qqpay', '1');
INSERT INTO `pre_config` VALUES ('settle_bank', '0');
INSERT INTO `pre_config` VALUES ('transfer_alipay', '0');
INSERT INTO `pre_config` VALUES ('transfer_wxpay', '0');
INSERT INTO `pre_config` VALUES ('transfer_qqpay', '0');
INSERT INTO `pre_config` VALUES ('transfer_name', '聚合易支付');
INSERT INTO `pre_config` VALUES ('transfer_desc', '聚合易支付自动结算');
INSERT INTO `pre_config` VALUES ('login_qq', '0');
INSERT INTO `pre_config` VALUES ('login_alipay', '0');
INSERT INTO `pre_config` VALUES ('login_alipay_channel', '0');
INSERT INTO `pre_config` VALUES ('login_wx', '0');
INSERT INTO `pre_config` VALUES ('login_wx_channel', '0');
INSERT INTO `pre_config` VALUES ('reg_open', '1');
INSERT INTO `pre_config` VALUES ('reg_pay', '1');
INSERT INTO `pre_config` VALUES ('reg_pre_uid', '1000');
INSERT INTO `pre_config` VALUES ('reg_pre_price', '5');
INSERT INTO `pre_config` VALUES ('verifytype', '1');
INSERT INTO `pre_config` VALUES ('test_open', '1');
INSERT INTO `pre_config` VALUES ('test_pre_uid', '1000');
INSERT INTO `pre_config` VALUES ('mail_cloud', '0');
INSERT INTO `pre_config` VALUES ('mail_smtp', 'smtp.qq.com');
INSERT INTO `pre_config` VALUES ('mail_port', '465');
INSERT INTO `pre_config` VALUES ('mail_name', '');
INSERT INTO `pre_config` VALUES ('mail_pwd', '');
INSERT INTO `pre_config` VALUES ('sms_api', '0');
INSERT INTO `pre_config` VALUES ('captcha_open', '1');
INSERT INTO `pre_config` VALUES ('captcha_id', '');
INSERT INTO `pre_config` VALUES ('captcha_key', '');
INSERT INTO `pre_config` VALUES ('onecode', '1');
INSERT INTO `pre_config` VALUES ('recharge', '1');
INSERT INTO `pre_config` VALUES ('pageordername', '1');
INSERT INTO `pre_config` VALUES ('notifyordername', '1');


DROP TABLE IF EXISTS `pre_cache`;
create table `pre_cache` (
  `k` varchar(32) NOT NULL,
  `v` longtext NULL,
  `expire` int(11) NOT NULL DEFAULT '0',
PRIMARY KEY  (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_anounce`;
create table `pre_anounce` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `content` text DEFAULT NULL,
  `color` varchar(10) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT '1',
  `addtime` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_type`;
CREATE TABLE `pre_type` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(30) NOT NULL,
  `device` int(1) unsigned NOT NULL DEFAULT '0',
  `showname` varchar(30) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`),
 KEY name (`name`,`device`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `pre_type` VALUES (1, 'alipay', 0, '支付宝', 1);
INSERT INTO `pre_type` VALUES (2, 'wxpay', 0, '微信支付', 1);
INSERT INTO `pre_type` VALUES (3, 'qqpay', 0, 'QQ钱包', 1);
INSERT INTO `pre_type` VALUES (4, 'bank', 0, '网银支付', 0);
INSERT INTO `pre_type` VALUES (5, 'jdpay', 0, '京东支付', 0);
INSERT INTO `pre_type` VALUES (6, 'paypal', 0, 'PayPal', 0);

DROP TABLE IF EXISTS `pre_plugin`;
CREATE TABLE `pre_plugin` (
  `name` varchar(30) NOT NULL,
  `showname` varchar(60) DEFAULT NULL,
  `author` varchar(60) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `types` varchar(50) DEFAULT NULL,
 PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_channel`;
CREATE TABLE `pre_channel` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `mode` int(1) DEFAULT 0,
  `type` int(11) unsigned NOT NULL,
  `plugin` varchar(30) NOT NULL,
  `name` varchar(30) NOT NULL,
  `rate` decimal(5,2) NOT NULL DEFAULT '100.00',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `appid` varchar(255) DEFAULT NULL,
  `appkey` text DEFAULT NULL,
  `appsecret` text DEFAULT NULL,
  `appurl` varchar(255) DEFAULT NULL,
  `appmchid` varchar(255) DEFAULT NULL,
  `apptype` varchar(50) DEFAULT NULL,
  `daytop` int(10) DEFAULT 0,
  `daystatus` int(1) DEFAULT 0,
  `paymin` varchar(10) DEFAULT NULL,
  `paymax` varchar(10) DEFAULT NULL,
  `appwxmp` int(11) DEFAULT NULL,
  `appwxa` int(11) DEFAULT NULL,
  `appswitch` tinyint(4) DEFAULT NULL,
 PRIMARY KEY (`id`),
 KEY type (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_roll`;
CREATE TABLE `pre_roll` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `type` int(11) unsigned NOT NULL,
  `name` varchar(30) NOT NULL,
  `kind` int(1) unsigned NOT NULL DEFAULT '0',
  `info` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `index` int(11) NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=101;

DROP TABLE IF EXISTS `pre_weixin`;
CREATE TABLE `pre_weixin` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `type` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `name` varchar(30) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `appid` varchar(150) DEFAULT NULL,
  `appsecret` varchar(250) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_order`;
CREATE TABLE `pre_order` (
  `trade_no` char(19) NOT NULL,
  `out_trade_no` varchar(150) NOT NULL,
  `api_trade_no` varchar(150) DEFAULT NULL,
  `uid` int(11) unsigned NOT NULL,
  `tid` tinyint(11) unsigned NOT NULL DEFAULT '0',
  `type` int(10) unsigned NOT NULL,
  `channel` int(10) unsigned NOT NULL,
  `name` varchar(64) NOT NULL,
  `money` decimal(10,2) NOT NULL,
  `realmoney` decimal(10,2) DEFAULT NULL,
  `getmoney` decimal(10,2) DEFAULT NULL,
  `notify_url` varchar(255) DEFAULT NULL,
  `return_url` varchar(255) DEFAULT NULL,
  `param` varchar(255) DEFAULT NULL,
  `addtime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `date` date DEFAULT NULL,
  `domain` varchar(64) DEFAULT NULL,
  `domain2` varchar(64) DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `buyer` varchar(30) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `notify` int(5) NOT NULL DEFAULT '0',
  `notifytime` datetime DEFAULT NULL,
  `invite` int(11) unsigned NOT NULL DEFAULT '0',
  `invitemoney` decimal(10,2) DEFAULT NULL,
 PRIMARY KEY (`trade_no`),
 KEY uid (`uid`),
 KEY out_trade_no (`out_trade_no`,`uid`),
 KEY api_trade_no (`api_trade_no`),
 KEY invite (`invite`),
 KEY date (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_group`;
CREATE TABLE `pre_group` (
  `gid` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(30) NOT NULL,
  `info` varchar(1024) DEFAULT NULL,
  `isbuy` tinyint(1) NOT NULL DEFAULT 0,
  `price` decimal(10,2) DEFAULT NULL,
  `sort` int(10) NOT NULL DEFAULT 0,
  `expire` int(10) NOT NULL DEFAULT 0,
  `settle_open` int(1) DEFAULT 0,
  `settle_type` int(1) DEFAULT 0,
  `settings` text DEFAULT NULL,
 PRIMARY KEY (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `pre_group` (`gid`, `name`, `info`) VALUES
(0, '默认用户组', '{"1":{"type":"","channel":"-1","rate":""},"2":{"type":"","channel":"-1","rate":""},"3":{"type":"","channel":"-1","rate":""}}');
UPDATE `pre_group` SET `gid` = '0' WHERE `gid` = 1;

DROP TABLE IF EXISTS `pre_user`;
CREATE TABLE `pre_user` (
  `uid` int(11) unsigned NOT NULL auto_increment,
  `gid` int(11) unsigned NOT NULL DEFAULT 0,
  `upid` int(11) unsigned NOT NULL DEFAULT 0,
  `key` varchar(32) NOT NULL,
  `pwd` varchar(32) DEFAULT NULL,
  `account` varchar(128) DEFAULT NULL,
  `username` varchar(128) DEFAULT NULL,
  `codename` varchar(32) DEFAULT NULL,
  `settle_id` tinyint(4) NOT NULL DEFAULT '1',
  `alipay_uid` varchar(32) DEFAULT NULL,
  `qq_uid` varchar(32) DEFAULT NULL,
  `wx_uid` varchar(32) DEFAULT NULL,
  `money` decimal(10,2) NOT NULL,
  `email` varchar(32) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `qq` varchar(20) DEFAULT NULL,
  `url` varchar(64) DEFAULT NULL,
  `cert` tinyint(4) NOT NULL DEFAULT '0',
  `certtype` tinyint(4) NOT NULL DEFAULT '0',
  `certmethod` tinyint(4) NOT NULL DEFAULT '0',
  `certno` varchar(18) DEFAULT NULL,
  `certname` varchar(32) DEFAULT NULL,
  `certtime` datetime DEFAULT NULL,
  `certtoken` varchar(64) DEFAULT NULL,
  `certcorpno` varchar(30) DEFAULT NULL,
  `certcorpname` varchar(80) DEFAULT NULL,
  `addtime` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `level` tinyint(1) NOT NULL DEFAULT '1',
  `pay` tinyint(1) NOT NULL DEFAULT '1',
  `settle` tinyint(1) NOT NULL DEFAULT '1',
  `keylogin` tinyint(1) NOT NULL DEFAULT '1',
  `apply` tinyint(1) NOT NULL DEFAULT '0',
  `mode` tinyint(4) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `channelinfo` text DEFAULT NULL,
  `ordername` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`uid`),
 KEY email (`email`),
 KEY phone (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000;

DROP TABLE IF EXISTS `pre_settle`;
CREATE TABLE `pre_settle` (
  `id` int(11) NOT NULL auto_increment,
  `uid` int(11) NOT NULL,
  `batch` varchar(20) DEFAULT NULL,
  `auto` int(1) NOT NULL DEFAULT '1',
  `type` int(1) NOT NULL DEFAULT '1',
  `account` varchar(128) NOT NULL,
  `username` varchar(128) NOT NULL,
  `money` decimal(10,2) NOT NULL,
  `realmoney` decimal(10,2) NOT NULL,
  `addtime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  `transfer_status` int(1) NOT NULL DEFAULT '0',
  `transfer_result` varchar(64) DEFAULT NULL,
  `transfer_date` datetime DEFAULT NULL,
  `result` varchar(64) DEFAULT NULL,
 PRIMARY KEY (`id`),
 KEY uid (`uid`),
 KEY batch (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_log`;
CREATE TABLE `pre_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `type` varchar(20) NULL,
  `date` datetime NOT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `city` varchar(20) DEFAULT NULL,
  `data` text NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_record`;
CREATE TABLE `pre_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `action` int(1) NOT NULL DEFAULT '0',
  `money` decimal(10,2) NOT NULL,
  `oldmoney` decimal(10,2) NOT NULL,
  `newmoney` decimal(10,2) NOT NULL,
  `type` varchar(20) DEFAULT NULL,
  `trade_no` varchar(64) DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY uid (`uid`),
  KEY trade_no (`trade_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_batch`;
CREATE TABLE `pre_batch` (
  `batch` varchar(20) NOT NULL,
  `allmoney` decimal(10,2) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '0',
  `time` datetime DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_regcode`;
CREATE TABLE `pre_regcode` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `type` int(1) NOT NULL DEFAULT '0',
  `code` varchar(32) NOT NULL,
  `to` varchar(32) DEFAULT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  `errcount` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY code (`to`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_risk`;
CREATE TABLE `pre_risk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `type` int(1) NOT NULL DEFAULT '0',
  `url` varchar(64) DEFAULT NULL,
  `content` varchar(64) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY uid (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_alipayrisk`;
CREATE TABLE `pre_alipayrisk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel` int(10) unsigned NOT NULL,
  `pid` varchar(40) NOT NULL,
  `smid` varchar(40) DEFAULT NULL,
  `tradeNos` varchar(40) DEFAULT NULL,
  `risktype` varchar(40) DEFAULT NULL,
  `risklevel` varchar(60) DEFAULT NULL,
  `riskDesc` varchar(500) DEFAULT NULL,
  `complainTime` varchar(128) DEFAULT NULL,
  `complainText` varchar(500) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `process_code` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
