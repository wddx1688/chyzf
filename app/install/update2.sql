ALTER TABLE `pre_channel`
ADD COLUMN `mode` int(1) DEFAULT 0;

ALTER TABLE `pre_channel`
ADD COLUMN `daytop` int(10) DEFAULT 0,
ADD COLUMN `daystatus` int(1) DEFAULT 0;

ALTER TABLE `pre_user`
ADD COLUMN `channelinfo` text DEFAULT NULL;

ALTER TABLE `pre_group`
ADD COLUMN `settle_open` int(1) DEFAULT 0,
ADD COLUMN `settle_type` int(1) DEFAULT 0,
ADD COLUMN `settings` text DEFAULT NULL;

ALTER TABLE `pre_channel`
ADD COLUMN `paymin` varchar(10) DEFAULT NULL,
ADD COLUMN `paymax` varchar(10) DEFAULT NULL;

ALTER TABLE `pre_order`
ADD COLUMN `notifytime` datetime DEFAULT NULL;

ALTER TABLE `pre_order`
ADD COLUMN `param` varchar(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `pre_alipayrisk` (
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

ALTER TABLE `pre_user`
ADD COLUMN `certtype` tinyint(4) NOT NULL DEFAULT '0',
ADD COLUMN `certtoken` varchar(64) DEFAULT NULL;

ALTER TABLE `pre_order`
ADD COLUMN `domain2` varchar(64) DEFAULT NULL;

ALTER TABLE `pre_user`
CHANGE COLUMN `wxid` `wx_uid` varchar(32) DEFAULT NULL;

ALTER TABLE `pre_user`
ADD COLUMN `certmethod` tinyint(4) NOT NULL DEFAULT '0',
ADD COLUMN `certcorpno` varchar(30) DEFAULT NULL,
ADD COLUMN `certcorpname` varchar(80) DEFAULT NULL,
ADD COLUMN `ordername` varchar(255) DEFAULT NULL;

ALTER TABLE `pre_regcode`
ADD COLUMN `errcount` int(11) NOT NULL DEFAULT '0';

ALTER TABLE `pre_channel`
ADD COLUMN `appwxmp` int(11) DEFAULT NULL,
ADD COLUMN `appwxa` int(11) DEFAULT NULL,
ADD COLUMN `appswitch` tinyint(4) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `pre_weixin` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `type` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `name` varchar(30) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `appid` varchar(150) DEFAULT NULL,
  `appsecret` varchar(250) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `pre_type` VALUES (6, 'paypal', 0, 'PayPal', 0);

ALTER TABLE `pre_group`
MODIFY COLUMN `info` varchar(1024) DEFAULT NULL;