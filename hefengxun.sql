/*
Navicat MySQL Data Transfer

Source Server         : local
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : hefengxun

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2019-10-11 10:04:15
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `dc_admin`
-- ----------------------------
DROP TABLE IF EXISTS `dc_admin`;
CREATE TABLE `dc_admin` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `gid` int(10) NOT NULL DEFAULT '1',
  `user` varchar(20) NOT NULL,
  `password` varchar(32) NOT NULL,
  `nicename` varchar(20) DEFAULT NULL,
  `regtime` int(10) DEFAULT NULL,
  `logintime` int(10) DEFAULT NULL,
  `ip` varchar(15) DEFAULT '未知',
  `status` int(1) unsigned DEFAULT '1',
  `loginnum` int(10) DEFAULT '1',
  `keep` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='管理员信息表';

-- ----------------------------
-- Records of dc_admin
-- ----------------------------
INSERT INTO `dc_admin` VALUES ('1', '1', 'admin', '202cb962ac59075b964b07152d234b70', '海拔网络', '1350138971', '1570687834', '127.0.0.1', '1', '6', '1');

-- ----------------------------
-- Table structure for `dc_admin_group`
-- ----------------------------
DROP TABLE IF EXISTS `dc_admin_group`;
CREATE TABLE `dc_admin_group` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT NULL,
  `menu_power` text,
  `model_power` text,
  `class_power` text,
  `form_power` text,
  `grade` tinyint(1) DEFAULT '3',
  `keep` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `power_value` (`model_power`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_admin_group
-- ----------------------------
INSERT INTO `dc_admin_group` VALUES ('1', '超级管理组', null, '', '', '', '1', '1');
INSERT INTO `dc_admin_group` VALUES ('3', '管理员', 'a:2:{i:0;s:1:\"1\";i:1;s:3:\"100\";}', 'a:5:{i:3;a:2:{i:0;s:5:\"visit\";i:1;s:4:\"edit\";}i:101;a:2:{i:0;s:5:\"visit\";i:1;s:4:\"edit\";}i:102;a:4:{i:0;s:5:\"visit\";i:1;s:4:\"edit\";i:2;s:3:\"add\";i:3;s:3:\"del\";}i:103;a:5:{i:0;s:5:\"visit\";i:1;s:4:\"edit\";i:2;s:3:\"add\";i:3;s:3:\"del\";i:4;s:4:\"send\";}i:104;a:2:{i:0;s:5:\"visit\";i:1;s:4:\"edit\";}}', '', 'N;', '2', '0');

-- ----------------------------
-- Table structure for `dc_admin_log`
-- ----------------------------
DROP TABLE IF EXISTS `dc_admin_log`;
CREATE TABLE `dc_admin_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT NULL,
  `time` int(10) DEFAULT NULL,
  `ip` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_admin_log
-- ----------------------------
INSERT INTO `dc_admin_log` VALUES ('1', '1', '1476769909', '127.0.0.1');
INSERT INTO `dc_admin_log` VALUES ('2', '1', '1477641806', '127.0.0.1');
INSERT INTO `dc_admin_log` VALUES ('3', '1', '1477644131', '127.0.0.1');
INSERT INTO `dc_admin_log` VALUES ('4', '1', '1478072684', '127.0.0.1');
INSERT INTO `dc_admin_log` VALUES ('5', '1', '1478237155', '127.0.0.1');
INSERT INTO `dc_admin_log` VALUES ('6', '1', '1566266177', '127.0.0.1');
INSERT INTO `dc_admin_log` VALUES ('7', '1', '1566271940', '127.0.0.1');
INSERT INTO `dc_admin_log` VALUES ('8', '1', '1568164949', '127.0.0.1');
INSERT INTO `dc_admin_log` VALUES ('9', '1', '1568700496', '127.0.0.1');
INSERT INTO `dc_admin_log` VALUES ('10', '1', '1568797944', '127.0.0.1');
INSERT INTO `dc_admin_log` VALUES ('11', '1', '1570687834', '127.0.0.1');

-- ----------------------------
-- Table structure for `dc_admin_menu`
-- ----------------------------
DROP TABLE IF EXISTS `dc_admin_menu`;
CREATE TABLE `dc_admin_menu` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `pid` int(10) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `module` varchar(250) DEFAULT NULL,
  `status` int(10) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `module` (`module`)
) ENGINE=MyISAM AUTO_INCREMENT=130 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_admin_menu
-- ----------------------------
INSERT INTO `dc_admin_menu` VALUES ('1', '0', '首页', null, '1');
INSERT INTO `dc_admin_menu` VALUES ('3', '1', '系统设置', 'setting', '1');
INSERT INTO `dc_admin_menu` VALUES ('4', '1', '模型管理', 'model_manage', '1');
INSERT INTO `dc_admin_menu` VALUES ('10', '0', '扩展', null, '1');
INSERT INTO `dc_admin_menu` VALUES ('11', '10', '扩展模型', 'expand_model', '1');
INSERT INTO `dc_admin_menu` VALUES ('12', '10', '自定义变量', 'fragment', '1');
INSERT INTO `dc_admin_menu` VALUES ('13', '10', '内容替换', 'replace', '1');
INSERT INTO `dc_admin_menu` VALUES ('14', '10', 'TAG管理', 'tags', '1');
INSERT INTO `dc_admin_menu` VALUES ('15', '10', '推荐位管理', 'position', '1');
INSERT INTO `dc_admin_menu` VALUES ('16', '10', '附件管理', 'upload_file', '1');
INSERT INTO `dc_admin_menu` VALUES ('20', '0', '用户', null, '1');
INSERT INTO `dc_admin_menu` VALUES ('21', '20', '管理组管理', 'user_group', '1');
INSERT INTO `dc_admin_menu` VALUES ('22', '20', '管理员管理', 'user', '1');
INSERT INTO `dc_admin_menu` VALUES ('23', '20', '后台登录记录', 'log', '1');
INSERT INTO `dc_admin_menu` VALUES ('24', '1', '插件管理', 'plugin', '1');
INSERT INTO `dc_admin_menu` VALUES ('25', '1', '程序升级', 'upgrade', '1');
INSERT INTO `dc_admin_menu` VALUES ('26', '1', '语言管理', 'lang', '1');
INSERT INTO `dc_admin_menu` VALUES ('30', '0', '栏目', null, '1');
INSERT INTO `dc_admin_menu` VALUES ('31', '30', '栏目管理', 'category', '1');
INSERT INTO `dc_admin_menu` VALUES ('40', '0', '内容', '', '1');
INSERT INTO `dc_admin_menu` VALUES ('41', '40', '内容管理', 'content', '1');
INSERT INTO `dc_admin_menu` VALUES ('50', '0', '表单', '', '1');
INSERT INTO `dc_admin_menu` VALUES ('51', '50', '表单设置', 'form', '1');
INSERT INTO `dc_admin_menu` VALUES ('100', '0', '微信', null, '1');
INSERT INTO `dc_admin_menu` VALUES ('101', '100', '关注回复', 'wx_text', '1');
INSERT INTO `dc_admin_menu` VALUES ('102', '100', '图文回复', 'wx_pic', '1');
INSERT INTO `dc_admin_menu` VALUES ('103', '100', '自定义菜单', 'wx_menu', '1');
INSERT INTO `dc_admin_menu` VALUES ('104', '100', '关注粉丝', 'member', '1');

-- ----------------------------
-- Table structure for `dc_admin_power`
-- ----------------------------
DROP TABLE IF EXISTS `dc_admin_power`;
CREATE TABLE `dc_admin_power` (
  `sequence` int(10) DEFAULT NULL,
  `action` varchar(250) DEFAULT NULL,
  `pid` int(10) DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_admin_power
-- ----------------------------
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '3', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'edit', '3', '保存');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '4', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'in', '4', '导入');
INSERT INTO `dc_admin_power` VALUES ('3', 'out', '4', '导出');
INSERT INTO `dc_admin_power` VALUES ('4', 'config', '4', '配置');
INSERT INTO `dc_admin_power` VALUES ('5', 'del', '4', '删除');
INSERT INTO `dc_admin_power` VALUES ('3', 'status', '24', '状态');
INSERT INTO `dc_admin_power` VALUES ('2', 'install', '24', '安装');
INSERT INTO `dc_admin_power` VALUES ('4', 'out', '24', '导出');
INSERT INTO `dc_admin_power` VALUES ('5', 'uninstall', '24', '卸载');
INSERT INTO `dc_admin_power` VALUES ('2', 'upgrade', '25', '升级');
INSERT INTO `dc_admin_power` VALUES ('1', 'add', '26', '添加');
INSERT INTO `dc_admin_power` VALUES ('2', 'edit', '26', '修改');
INSERT INTO `dc_admin_power` VALUES ('3', 'del', '26', '删除');
INSERT INTO `dc_admin_power` VALUES ('2', 'add', '31', '添加');
INSERT INTO `dc_admin_power` VALUES ('3', 'edit', '31', '修改');
INSERT INTO `dc_admin_power` VALUES ('4', 'del', '31', '删除');
INSERT INTO `dc_admin_power` VALUES ('2', 'add', '41', '添加');
INSERT INTO `dc_admin_power` VALUES ('3', 'edit', '41', '编辑');
INSERT INTO `dc_admin_power` VALUES ('4', 'del', '41', '删除');
INSERT INTO `dc_admin_power` VALUES ('5', 'past', '41', '审核通过');
INSERT INTO `dc_admin_power` VALUES ('6', 'cancel', '41', '取消审核');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '11', '浏览');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '24', '浏览');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '25', '浏览');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '26', '浏览');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '31', '浏览');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '41', '浏览');
INSERT INTO `dc_admin_power` VALUES ('6', 'in', '11', '导入');
INSERT INTO `dc_admin_power` VALUES ('7', 'out', '11', '导出');
INSERT INTO `dc_admin_power` VALUES ('3', 'add', '11', '添加');
INSERT INTO `dc_admin_power` VALUES ('4', 'edit', '11', '编辑');
INSERT INTO `dc_admin_power` VALUES ('5', 'del', '11', '删除');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '12', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'add', '12', '添加');
INSERT INTO `dc_admin_power` VALUES ('3', 'edit', '12', '编辑');
INSERT INTO `dc_admin_power` VALUES ('4', 'del', '12', '删除');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '13', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'add', '13', '添加');
INSERT INTO `dc_admin_power` VALUES ('3', 'edit', '13', '编辑');
INSERT INTO `dc_admin_power` VALUES ('4', 'del', '13', '删除');
INSERT INTO `dc_admin_power` VALUES ('2', 'del', '14', '删除');
INSERT INTO `dc_admin_power` VALUES ('3', 'class', '14', '分组');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '15', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'add', '15', '添加');
INSERT INTO `dc_admin_power` VALUES ('3', 'edit', '15', '编辑');
INSERT INTO `dc_admin_power` VALUES ('4', 'del', '15', '删除');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '14', '浏览');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '16', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'del', '16', '删除');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '21', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'add', '21', '添加');
INSERT INTO `dc_admin_power` VALUES ('3', 'edit', '21', '编辑');
INSERT INTO `dc_admin_power` VALUES ('4', 'del', '21', '删除');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '22', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'add', '22', '添加');
INSERT INTO `dc_admin_power` VALUES ('3', 'edit', '22', '编辑');
INSERT INTO `dc_admin_power` VALUES ('4', 'current', '22', '只显示自己');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '23', '浏览');
INSERT INTO `dc_admin_power` VALUES ('4', 'class_config', '14', '分组管理');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '51', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'add', '51', '添加');
INSERT INTO `dc_admin_power` VALUES ('3', 'edit', '51', '编辑');
INSERT INTO `dc_admin_power` VALUES ('4', 'del', '51', '删除');
INSERT INTO `dc_admin_power` VALUES ('5', 'in', '51', '导入');
INSERT INTO `dc_admin_power` VALUES ('6', 'out', '51', '导出');
INSERT INTO `dc_admin_power` VALUES ('7', 'field', '51', '字段管理');
INSERT INTO `dc_admin_power` VALUES ('5', 'info', '22', '资料修改');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '101', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'edit', '101', '编辑');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '102', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'edit', '102', '编辑');
INSERT INTO `dc_admin_power` VALUES ('3', 'add', '102', '添加');
INSERT INTO `dc_admin_power` VALUES ('4', 'del', '102', '删除');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '103', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'edit', '103', '编辑');
INSERT INTO `dc_admin_power` VALUES ('3', 'add', '103', '添加');
INSERT INTO `dc_admin_power` VALUES ('4', 'del', '103', '删除');
INSERT INTO `dc_admin_power` VALUES ('1', 'visit', '104', '浏览');
INSERT INTO `dc_admin_power` VALUES ('2', 'edit', '104', '更新');
INSERT INTO `dc_admin_power` VALUES ('5', 'send', '103', '生成菜单');

-- ----------------------------
-- Table structure for `dc_areply`
-- ----------------------------
DROP TABLE IF EXISTS `dc_areply`;
CREATE TABLE `dc_areply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `createtime` varchar(13) NOT NULL,
  `updatetime` varchar(13) NOT NULL,
  `home` varchar(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_areply
-- ----------------------------
INSERT INTO `dc_areply` VALUES ('11', '', '您好，欢迎关注公众号！', '1407161690', '1453369088', '0');

-- ----------------------------
-- Table structure for `dc_attention`
-- ----------------------------
DROP TABLE IF EXISTS `dc_attention`;
CREATE TABLE `dc_attention` (
  `gid` mediumint(8) NOT NULL AUTO_INCREMENT,
  `cid` int(4) NOT NULL,
  `g_id` int(4) DEFAULT NULL,
  `avatar` varchar(256) DEFAULT NULL,
  `nickname` varchar(256) DEFAULT NULL,
  `sex` varchar(10) DEFAULT '0',
  `city` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `oppenid` varchar(512) NOT NULL,
  `atttime` int(10) NOT NULL,
  `status` int(1) DEFAULT '0',
  `untime` int(10) DEFAULT '0',
  PRIMARY KEY (`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_attention
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_category`
-- ----------------------------
DROP TABLE IF EXISTS `dc_category`;
CREATE TABLE `dc_category` (
  `cid` int(10) NOT NULL AUTO_INCREMENT,
  `pid` int(10) NOT NULL DEFAULT '0',
  `mid` int(10) NOT NULL DEFAULT '1',
  `sequence` int(10) NOT NULL DEFAULT '0',
  `show` int(10) NOT NULL DEFAULT '1',
  `type` int(11) NOT NULL DEFAULT '1',
  `name` varchar(250) DEFAULT NULL,
  `urlname` varchar(250) DEFAULT NULL,
  `subname` varchar(250) DEFAULT NULL,
  `image` varchar(250) DEFAULT NULL,
  `class_tpl` varchar(250) DEFAULT NULL,
  `content_tpl` varchar(250) DEFAULT NULL,
  `page` int(10) DEFAULT NULL,
  `keywords` varchar(250) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `seo_content` text,
  `content_order` varchar(250) DEFAULT NULL,
  `lang` int(10) NOT NULL DEFAULT '1',
  `expand` int(10) DEFAULT NULL,
  PRIMARY KEY (`cid`),
  UNIQUE KEY `urlname` (`urlname`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_category
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_category_jump`
-- ----------------------------
DROP TABLE IF EXISTS `dc_category_jump`;
CREATE TABLE `dc_category_jump` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `url` varchar(250) DEFAULT NULL COMMENT '内容',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文章栏目分类';

-- ----------------------------
-- Records of dc_category_jump
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_category_page`
-- ----------------------------
DROP TABLE IF EXISTS `dc_category_page`;
CREATE TABLE `dc_category_page` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `content` mediumtext COMMENT '内容',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文章栏目分类';

-- ----------------------------
-- Records of dc_category_page
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_comment`
-- ----------------------------
DROP TABLE IF EXISTS `dc_comment`;
CREATE TABLE `dc_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `circle_id` int(10) DEFAULT '0',
  `user_id` int(10) DEFAULT '0',
  `reply_user_id` int(10) DEFAULT '0' COMMENT '回复评论人的id',
  `created_at` int(10) DEFAULT '0',
  `content` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_comment
-- ----------------------------
INSERT INTO `dc_comment` VALUES ('1', '3', '1', '0', '1562222154', '很好的想法！');
INSERT INTO `dc_comment` VALUES ('2', '3', '1', '1', '1568193255', '嗯嗯 我也感觉似的！~');
INSERT INTO `dc_comment` VALUES ('3', '3', '1', '1', '1568685360', '嗯嗯 我也感觉似的！~');
INSERT INTO `dc_comment` VALUES ('4', '1', '1', '1', '1568690957', '嗯嗯 我也感觉似的！~');
INSERT INTO `dc_comment` VALUES ('5', '3', '1', '1', '1568776024', '嗯嗯 我也感觉似的！~');
INSERT INTO `dc_comment` VALUES ('6', '3', '1', '1', '1568776027', '嗯嗯 我也感觉似的！~');
INSERT INTO `dc_comment` VALUES ('7', '3', '1', '1', '1568776029', '嗯嗯 我也感觉似的！~');
INSERT INTO `dc_comment` VALUES ('8', '3', '1', '1', '1570688386', '嗯嗯 我也感觉似的！~');
INSERT INTO `dc_comment` VALUES ('9', '3', '1', '1', '1570688418', '嗯嗯 我也感觉似的！~');

-- ----------------------------
-- Table structure for `dc_content`
-- ----------------------------
DROP TABLE IF EXISTS `dc_content`;
CREATE TABLE `dc_content` (
  `aid` int(10) NOT NULL AUTO_INCREMENT COMMENT '文章ID',
  `cid` int(10) DEFAULT NULL COMMENT '栏目ID',
  `title` varchar(250) DEFAULT NULL COMMENT '标题',
  `urltitle` varchar(250) DEFAULT NULL COMMENT 'URL路径',
  `subtitle` varchar(250) DEFAULT NULL COMMENT '短标题',
  `font_color` varchar(250) DEFAULT NULL COMMENT '颜色',
  `font_bold` int(1) DEFAULT NULL COMMENT '加粗',
  `keywords` varchar(250) DEFAULT NULL COMMENT '关键词',
  `description` varchar(250) DEFAULT NULL COMMENT '描述',
  `updatetime` int(10) DEFAULT NULL COMMENT '更新时间',
  `inputtime` int(10) DEFAULT NULL COMMENT '发布时间',
  `image` varchar(250) DEFAULT NULL COMMENT '封面图',
  `url` varchar(250) DEFAULT NULL COMMENT '跳转',
  `sequence` int(10) DEFAULT NULL COMMENT '排序',
  `tpl` varchar(250) DEFAULT NULL COMMENT '模板',
  `status` int(10) DEFAULT NULL COMMENT '状态',
  `copyfrom` varchar(250) DEFAULT NULL COMMENT '来源',
  `views` int(10) NOT NULL DEFAULT '0' COMMENT '浏览数',
  `share` int(10) NOT NULL DEFAULT '0',
  `position` varchar(250) DEFAULT NULL,
  `taglink` int(10) NOT NULL DEFAULT '0' COMMENT 'TAG链接',
  PRIMARY KEY (`aid`),
  UNIQUE KEY `urltitle` (`urltitle`) USING BTREE,
  KEY `title` (`title`) USING BTREE,
  KEY `description` (`copyfrom`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_content
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_content_data`
-- ----------------------------
DROP TABLE IF EXISTS `dc_content_data`;
CREATE TABLE `dc_content_data` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `aid` int(10) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_content_data
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_dictation_log`
-- ----------------------------
DROP TABLE IF EXISTS `dc_dictation_log`;
CREATE TABLE `dc_dictation_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(10) DEFAULT '0',
  `source_period_id` int(10) DEFAULT '0',
  `content` text COLLATE utf8mb4_unicode_ci,
  `created_at` int(10) DEFAULT '0',
  `time` int(10) DEFAULT '0',
  `user_id` int(10) DEFAULT '0',
  `type` tinyint(3) DEFAULT '0' COMMENT '1平台 2个人 3',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_dictation_log
-- ----------------------------
INSERT INTO `dc_dictation_log` VALUES ('1', '1', '1', null, '1568272862', '0', '1', '1');
INSERT INTO `dc_dictation_log` VALUES ('3', '3', '3', '测试123123', '1570693567', '10', '1', '1');

-- ----------------------------
-- Table structure for `dc_diymen_class`
-- ----------------------------
DROP TABLE IF EXISTS `dc_diymen_class`;
CREATE TABLE `dc_diymen_class` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `title` varchar(30) NOT NULL,
  `keyword` varchar(30) NOT NULL,
  `url` varchar(512) NOT NULL,
  `is_show` tinyint(1) NOT NULL,
  `sort` tinyint(3) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_diymen_class
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_expand_model`
-- ----------------------------
DROP TABLE IF EXISTS `dc_expand_model`;
CREATE TABLE `dc_expand_model` (
  `mid` int(10) NOT NULL AUTO_INCREMENT,
  `table` varchar(250) DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`mid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_expand_model
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_expand_model_field`
-- ----------------------------
DROP TABLE IF EXISTS `dc_expand_model_field`;
CREATE TABLE `dc_expand_model_field` (
  `fid` int(10) NOT NULL AUTO_INCREMENT,
  `mid` int(10) DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  `field` varchar(250) DEFAULT NULL,
  `type` int(10) DEFAULT '1',
  `property` int(10) DEFAULT NULL,
  `len` int(10) DEFAULT NULL,
  `decimal` int(10) DEFAULT NULL,
  `default` varchar(250) DEFAULT NULL,
  `sequence` int(10) DEFAULT '0',
  `tip` varchar(250) DEFAULT NULL,
  `must` int(10) DEFAULT '0',
  `config` text,
  PRIMARY KEY (`fid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_expand_model_field
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_form`
-- ----------------------------
DROP TABLE IF EXISTS `dc_form`;
CREATE TABLE `dc_form` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT NULL,
  `table` varchar(20) DEFAULT NULL,
  `display` int(10) NOT NULL DEFAULT '0',
  `page` int(10) NOT NULL DEFAULT '10',
  `tpl` varchar(250) DEFAULT NULL,
  `alone_tpl` int(10) NOT NULL DEFAULT '0',
  `order` varchar(20) DEFAULT NULL,
  `where` varchar(250) DEFAULT NULL,
  `return_type` int(10) NOT NULL DEFAULT '0',
  `return_msg` varchar(250) DEFAULT NULL,
  `return_url` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_form
-- ----------------------------
INSERT INTO `dc_form` VALUES ('1', 'banner图', 'banner', '1', '10', '', '1', 'id desc', '', '0', '表单提交成功', '');
INSERT INTO `dc_form` VALUES ('2', 'logo图', 'logo', '1', '10', '', '1', 'id desc', '', '0', '表单提交成功', '');
INSERT INTO `dc_form` VALUES ('3', '关于我们内容', 'aboutUs', '1', '10', '', '1', 'id desc', '', '0', '表单提交成功', '');
INSERT INTO `dc_form` VALUES ('4', '系统介绍', 'introduce', '1', '10', '', '1', 'id desc', '', '0', '表单提交成功', '');
INSERT INTO `dc_form` VALUES ('5', '新闻资讯表', 'news', '1', '10', '', '1', 'id desc', '', '0', '表单提交成功', '');
INSERT INTO `dc_form` VALUES ('6', '用户注册协议', 'agreement', '1', '10', '', '1', 'id desc', '', '0', '表单提交成功', '');
INSERT INTO `dc_form` VALUES ('7', '个人中心用户协议', 'user_agreement', '1', '10', '', '1', 'id desc', '', '0', '表单提交成功', '');
INSERT INTO `dc_form` VALUES ('8', '日语等级', 'japanese', '1', '10', '', '1', 'id desc', '', '0', '表单提交成功', '');
INSERT INTO `dc_form` VALUES ('9', '英语等级', 'english', '1', '10', '', '1', 'id desc', '', '0', '表单提交成功', '');

-- ----------------------------
-- Table structure for `dc_form_data_aboutus`
-- ----------------------------
DROP TABLE IF EXISTS `dc_form_data_aboutus`;
CREATE TABLE `dc_form_data_aboutus` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lang` int(10) DEFAULT '1',
  `description` text,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_form_data_aboutus
-- ----------------------------
INSERT INTO `dc_form_data_aboutus` VALUES ('1', '1', '测试的描述上', '&lt;p&gt;\n	关于我们的内容：\n&lt;/p&gt;\n&lt;p&gt;\n	&lt;img src=&quot;/upload/2019-09/11/imgbanner02-b6d5d.png&quot; title=&quot;img／banner02&quot; alt=&quot;img／banner02&quot; /&gt;\n&lt;/p&gt;');

-- ----------------------------
-- Table structure for `dc_form_data_agreement`
-- ----------------------------
DROP TABLE IF EXISTS `dc_form_data_agreement`;
CREATE TABLE `dc_form_data_agreement` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lang` int(10) DEFAULT '1',
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_form_data_agreement
-- ----------------------------
INSERT INTO `dc_form_data_agreement` VALUES ('1', '1', '&lt;p&gt;\n	用户注册协议测试\n&lt;/p&gt;\n&lt;p&gt;\n	&lt;img src=&quot;/upload/2019-09/11/imgbanner02-ba673.png&quot; title=&quot;img／banner02&quot; alt=&quot;img／banner02&quot; /&gt;\n&lt;/p&gt;');

-- ----------------------------
-- Table structure for `dc_form_data_banner`
-- ----------------------------
DROP TABLE IF EXISTS `dc_form_data_banner`;
CREATE TABLE `dc_form_data_banner` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lang` int(10) DEFAULT '1',
  `image` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_form_data_banner
-- ----------------------------
INSERT INTO `dc_form_data_banner` VALUES ('1', '1', '/upload/2019-09/11/imgbanner02.png');

-- ----------------------------
-- Table structure for `dc_form_data_english`
-- ----------------------------
DROP TABLE IF EXISTS `dc_form_data_english`;
CREATE TABLE `dc_form_data_english` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lang` int(10) DEFAULT '1',
  `name` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_form_data_english
-- ----------------------------
INSERT INTO `dc_form_data_english` VALUES ('1', '1', 'CET-4');
INSERT INTO `dc_form_data_english` VALUES ('2', '1', 'CET-6');

-- ----------------------------
-- Table structure for `dc_form_data_introduce`
-- ----------------------------
DROP TABLE IF EXISTS `dc_form_data_introduce`;
CREATE TABLE `dc_form_data_introduce` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lang` int(10) DEFAULT '1',
  `name` varchar(250) DEFAULT NULL,
  `description` text,
  `image` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_form_data_introduce
-- ----------------------------
INSERT INTO `dc_form_data_introduce` VALUES ('1', '1', '听写', '听写听写听写听写听写听写听写听写听写', '/upload/2019-09/18/imgnews01.png');
INSERT INTO `dc_form_data_introduce` VALUES ('2', '1', '朗读', '朗读朗读朗读朗读朗读朗读朗读朗读朗读', '/upload/2019-09/18/imgnews01-c460c.png');

-- ----------------------------
-- Table structure for `dc_form_data_japanese`
-- ----------------------------
DROP TABLE IF EXISTS `dc_form_data_japanese`;
CREATE TABLE `dc_form_data_japanese` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lang` int(10) DEFAULT '1',
  `name` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_form_data_japanese
-- ----------------------------
INSERT INTO `dc_form_data_japanese` VALUES ('1', '1', 'N1');
INSERT INTO `dc_form_data_japanese` VALUES ('2', '1', 'N2');
INSERT INTO `dc_form_data_japanese` VALUES ('3', '1', 'N3');

-- ----------------------------
-- Table structure for `dc_form_data_logo`
-- ----------------------------
DROP TABLE IF EXISTS `dc_form_data_logo`;
CREATE TABLE `dc_form_data_logo` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lang` int(10) DEFAULT '1',
  `image` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_form_data_logo
-- ----------------------------
INSERT INTO `dc_form_data_logo` VALUES ('1', '1', '/upload/2019-09/11/iconlogo1.png');

-- ----------------------------
-- Table structure for `dc_form_data_news`
-- ----------------------------
DROP TABLE IF EXISTS `dc_form_data_news`;
CREATE TABLE `dc_form_data_news` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lang` int(10) DEFAULT '1',
  `title` varchar(250) DEFAULT NULL,
  `content` text,
  `image` varchar(250) DEFAULT NULL,
  `sort` int(10) DEFAULT NULL,
  `created_at` int(10) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_form_data_news
-- ----------------------------
INSERT INTO `dc_form_data_news` VALUES ('1', '1', '【测试标题】海拔测试专用标题', '&lt;p&gt;\n	测试内容\n&lt;/p&gt;\n&lt;p&gt;\n	&lt;img src=&quot;/upload/2019-09/11/imgbanner02-408da.png&quot; title=&quot;img／banner02&quot; alt=&quot;img／banner02&quot; /&gt;\n&lt;/p&gt;', '/upload/2019-09/11/imgnews01.png', '1', '1568185831');

-- ----------------------------
-- Table structure for `dc_form_data_user_agreement`
-- ----------------------------
DROP TABLE IF EXISTS `dc_form_data_user_agreement`;
CREATE TABLE `dc_form_data_user_agreement` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lang` int(10) DEFAULT '1',
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_form_data_user_agreement
-- ----------------------------
INSERT INTO `dc_form_data_user_agreement` VALUES ('1', '1', '二维的翁多翁测试的和速度的我从我的错v&amp;nbsp;');

-- ----------------------------
-- Table structure for `dc_form_field`
-- ----------------------------
DROP TABLE IF EXISTS `dc_form_field`;
CREATE TABLE `dc_form_field` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `fid` int(10) DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  `field` varchar(250) DEFAULT NULL,
  `type` int(10) DEFAULT '1',
  `property` int(10) DEFAULT NULL,
  `len` int(10) DEFAULT NULL,
  `decimal` int(10) DEFAULT NULL,
  `default` varchar(250) DEFAULT NULL,
  `sequence` int(10) DEFAULT '0',
  `tip` varchar(250) DEFAULT NULL,
  `config` text,
  `must` int(10) DEFAULT '0',
  `admin_display` int(10) DEFAULT NULL,
  `admin_html` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_form_field
-- ----------------------------
INSERT INTO `dc_form_field` VALUES ('1', '1', 'banner图', 'image', '10', '1', '250', '0', '', '0', '大小：335 * 170 px', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('2', '2', 'logo图', 'image', '10', '1', '250', '0', '', '0', '大小：84 * 28 px', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('3', '3', '描述', 'description', '2', '3', '0', '0', '', '0', '', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('4', '3', '内容', 'content', '3', '3', '0', '0', '', '0', '', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('12', '4', '名字', 'name', '1', '1', '250', '0', '', '0', '', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('6', '5', '标题', 'title', '1', '1', '250', '0', '', '0', '', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('7', '5', '内容', 'content', '3', '3', '0', '0', '', '0', '', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('8', '5', '图片', 'image', '10', '1', '250', '0', '', '0', '大小：120 * 80 px', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('9', '5', '排序', 'sort', '1', '2', '10', '0', '', '0', '填写数字即可 数字越大越靠前', '', '0', '1', '');
INSERT INTO `dc_form_field` VALUES ('10', '6', '用户协议', 'content', '3', '3', '0', '0', '', '0', '', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('11', '7', '内容', 'content', '3', '3', '0', '0', '', '0', '', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('13', '4', '描述', 'description', '2', '3', '0', '0', '', '0', '', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('14', '4', '图片', 'image', '10', '1', '250', '0', '', '0', '', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('15', '8', '日语等级名称', 'name', '1', '1', '250', '0', '', '0', '', '', '1', '1', '');
INSERT INTO `dc_form_field` VALUES ('16', '9', '英语等级名称', 'name', '1', '1', '250', '0', '', '0', '', '', '1', '1', '');

-- ----------------------------
-- Table structure for `dc_fragment`
-- ----------------------------
DROP TABLE IF EXISTS `dc_fragment`;
CREATE TABLE `dc_fragment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '文章id',
  `content` text,
  `title` varchar(250) DEFAULT NULL,
  `sign` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sign` (`sign`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文章信息表';

-- ----------------------------
-- Records of dc_fragment
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_friend_circle`
-- ----------------------------
DROP TABLE IF EXISTS `dc_friend_circle`;
CREATE TABLE `dc_friend_circle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `sort_id` int(10) DEFAULT '0',
  `images` text CHARACTER SET utf8mb4 COMMENT '朋友圈的照片 ',
  `created_at` int(10) DEFAULT '0',
  `user_id` int(10) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_friend_circle
-- ----------------------------
INSERT INTO `dc_friend_circle` VALUES ('1', '测试朋友圈的信息是怎么样？', '1', '[\"\\/upload\\/0.36170600 1568191286_1\\/20190911\\/1568191286img\\uff0fbanner02.png\"]', '1568191286', '1');
INSERT INTO `dc_friend_circle` VALUES ('3', '测试朋友圈的信息是怎么样？', '1', '[\"\\/upload\\/0.18161500 1568191372_1\\/20190911\\/1568191372img\\uff0fbanner02.png\"]', '1568191372', '2');
INSERT INTO `dc_friend_circle` VALUES ('4', '测试朋友圈的信息是怎么样？', '1', '[]', '1568685389', '1');
INSERT INTO `dc_friend_circle` VALUES ('5', '测试朋友圈的信息是怎么样？', '1', '[]', '1570689113', '1');
INSERT INTO `dc_friend_circle` VALUES ('6', '测试朋友圈的信息是怎么样？', '1', '[]', '1570689161', '1');

-- ----------------------------
-- Table structure for `dc_friend_circle_sort`
-- ----------------------------
DROP TABLE IF EXISTS `dc_friend_circle_sort`;
CREATE TABLE `dc_friend_circle_sort` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` int(10) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_friend_circle_sort
-- ----------------------------
INSERT INTO `dc_friend_circle_sort` VALUES ('1', '日常生活', '1562541547');
INSERT INTO `dc_friend_circle_sort` VALUES ('2', '生活趣事', '1562221454');
INSERT INTO `dc_friend_circle_sort` VALUES ('3', '日语学习', '1564215478');
INSERT INTO `dc_friend_circle_sort` VALUES ('4', '每日新闻', '1565517145');

-- ----------------------------
-- Table structure for `dc_img`
-- ----------------------------
DROP TABLE IF EXISTS `dc_img`;
CREATE TABLE `dc_img` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` char(255) NOT NULL,
  `type` varchar(1) NOT NULL COMMENT '关键词匹配类型',
  `text` text NOT NULL COMMENT '简介',
  `pic` char(255) NOT NULL COMMENT '封面图片',
  `showpic` varchar(1) NOT NULL COMMENT '图片是否显示封面',
  `info` text NOT NULL COMMENT '图文详细内容',
  `url` char(255) NOT NULL COMMENT '图文外链地址',
  `createtime` varchar(13) NOT NULL,
  `uptatetime` varchar(13) NOT NULL,
  `click` int(11) NOT NULL DEFAULT '0',
  `title` varchar(60) NOT NULL,
  `sort` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_img
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_keyword`
-- ----------------------------
DROP TABLE IF EXISTS `dc_keyword`;
CREATE TABLE `dc_keyword` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` char(255) NOT NULL,
  `pid` int(11) NOT NULL,
  `module` varchar(15) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_keyword
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_lang`
-- ----------------------------
DROP TABLE IF EXISTS `dc_lang`;
CREATE TABLE `dc_lang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `lang` varchar(255) DEFAULT NULL,
  `protection` int(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `lang` (`lang`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_lang
-- ----------------------------
INSERT INTO `dc_lang` VALUES ('1', '中文', 'zh', '1');
INSERT INTO `dc_lang` VALUES ('2', 'english', 'en', '0');

-- ----------------------------
-- Table structure for `dc_member`
-- ----------------------------
DROP TABLE IF EXISTS `dc_member`;
CREATE TABLE `dc_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  `openid` varchar(30) NOT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `wx_sn` varchar(30) DEFAULT NULL,
  `is_show` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `avatar` varchar(256) DEFAULT NULL,
  `city` varchar(10) DEFAULT NULL,
  `province` varchar(10) DEFAULT NULL,
  `att_date` int(11) DEFAULT NULL COMMENT '关注时间',
  `un_date` int(11) DEFAULT NULL COMMENT '取消关注的时间',
  `oauth_date` int(11) DEFAULT NULL,
  `status` tinyint(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_member
-- ----------------------------
INSERT INTO `dc_member` VALUES ('1', 'Jeff', 'oHi08wETErNcYViBA-I_MhYSMp7E', '18955188888', '', '', '1', 'http://wx.qlogo.cn/mmopen/VTFciayaQRrd5B4iaUrVQebOiaR4NiaEoLlPAg0enn0K0DKnBWw4sZ8msd29fbq8kZMCdyGiaRlOUUiasfJSDXphA9qw/0', '浦东新区', '上海', '1453698977', '1453698963', null, '1');

-- ----------------------------
-- Table structure for `dc_model`
-- ----------------------------
DROP TABLE IF EXISTS `dc_model`;
CREATE TABLE `dc_model` (
  `mid` int(10) NOT NULL AUTO_INCREMENT,
  `model` varchar(250) DEFAULT NULL,
  `name` varchar(250) DEFAULT NULL,
  `admin_category` varchar(250) DEFAULT NULL,
  `admin_content` varchar(250) DEFAULT NULL,
  `module_category` varchar(250) DEFAULT NULL,
  `module_content` varchar(250) DEFAULT NULL,
  `url_category` varchar(250) DEFAULT NULL,
  `url_category_page` varchar(250) DEFAULT NULL,
  `url_content` varchar(250) DEFAULT NULL,
  `url_content_page` varchar(250) DEFAULT NULL,
  `table` text,
  `file` text,
  `config` text,
  `befrom` text,
  PRIMARY KEY (`mid`),
  KEY `model` (`model`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_model
-- ----------------------------
INSERT INTO `dc_model` VALUES ('1', 'content', '新闻', 'content_category', 'content', 'category', 'content', '{CDIR}/', '{CDIR}/index-{P}{EXT}', '{CDIR}/{YYYY}/{M}-{D}/{AID}{EXT}', '{CDIR}/{YYYY}/{M}-{D}/{AID}-{P}{EXT}', null, null, null, 'HAIBA');
INSERT INTO `dc_model` VALUES ('3', 'jump', '跳转', 'jump_category', null, 'jump', null, '{CDIR}/', '{CDIR}/index-{P}{EXT}', '{CDIR}/{YYYY}/{M}-{D}/{AID}{EXT}', '{CDIR}/{YYYY}/{M}-{D}/{AID}{EXT}', null, null, null, 'HAIBA');
INSERT INTO `dc_model` VALUES ('2', 'pages', '页面', 'pages_category', null, 'pages', null, '{CDIR}/', '{CDIR}/index-{P}{EXT}', '{CDIR}/{YYYY}/{M}-{D}/{AID}{EXT}', '{CDIR}/{YYYY}/{M}-{D}/{AID}{EXT}', null, null, null, 'HAIBA');

-- ----------------------------
-- Table structure for `dc_platform`
-- ----------------------------
DROP TABLE IF EXISTS `dc_platform`;
CREATE TABLE `dc_platform` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `platform` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '登陆类型 qq  微信  谷歌',
  `openid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '登录的唯一身份标识',
  `user_id` int(10) DEFAULT '0' COMMENT '用户的id',
  `created_at` int(10) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of dc_platform
-- ----------------------------
INSERT INTO `dc_platform` VALUES ('2', 'qq', 'qqqqqqqqqqqq', '4', '1568168995');
INSERT INTO `dc_platform` VALUES ('3', 'wechat', 'WEIXIweixin', '1', '1568169847');
INSERT INTO `dc_platform` VALUES ('4', 'wechat', 'WEIXIweixin123', '2', '1568189830');
INSERT INTO `dc_platform` VALUES ('5', 'google', 'google12312313', '7', '1568190075');

-- ----------------------------
-- Table structure for `dc_plugin`
-- ----------------------------
DROP TABLE IF EXISTS `dc_plugin`;
CREATE TABLE `dc_plugin` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL,
  `file` varchar(250) DEFAULT NULL,
  `status` int(1) DEFAULT NULL,
  `mid` int(10) DEFAULT NULL,
  `ver` int(11) DEFAULT NULL,
  `author` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_plugin
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_position`
-- ----------------------------
DROP TABLE IF EXISTS `dc_position`;
CREATE TABLE `dc_position` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `sequence` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_position
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_position_relation`
-- ----------------------------
DROP TABLE IF EXISTS `dc_position_relation`;
CREATE TABLE `dc_position_relation` (
  `aid` int(10) NOT NULL,
  `pid` int(10) NOT NULL,
  KEY `aid` (`aid`),
  KEY `pid` (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_position_relation
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_praise`
-- ----------------------------
DROP TABLE IF EXISTS `dc_praise`;
CREATE TABLE `dc_praise` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `translation_id` int(10) DEFAULT '0',
  `created_at` int(10) DEFAULT '0',
  `user_id` int(10) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_praise
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_replace`
-- ----------------------------
DROP TABLE IF EXISTS `dc_replace`;
CREATE TABLE `dc_replace` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(250) DEFAULT NULL,
  `content` varchar(250) DEFAULT NULL,
  `num` int(5) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_replace
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_sendcode`
-- ----------------------------
DROP TABLE IF EXISTS `dc_sendcode`;
CREATE TABLE `dc_sendcode` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `expiretime` int(10) DEFAULT '0',
  `mobile` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `style` varchar(255) DEFAULT NULL,
  `created_at` int(10) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of dc_sendcode
-- ----------------------------
INSERT INTO `dc_sendcode` VALUES ('23', '1568796120', '18955555555', '123456', 'bind', '1568795520');
INSERT INTO `dc_sendcode` VALUES ('24', '1570684700', '18855555555', '123456', 'reset', '1570684100');

-- ----------------------------
-- Table structure for `dc_source`
-- ----------------------------
DROP TABLE IF EXISTS `dc_source`;
CREATE TABLE `dc_source` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` int(10) DEFAULT '0',
  `type` tinyint(3) DEFAULT '0' COMMENT '1音频 2视频 3文本 4音频文本 5视频文本',
  `description` text COMMENT '描述',
  `position` varchar(255) DEFAULT '' COMMENT '格式 ，1，2，3，4， 首页推荐	',
  `path` varchar(255) DEFAULT '' COMMENT '视频的路径 或者是朗读之后合成的视频',
  `category_one_id` int(10) DEFAULT '0' COMMENT '分类一级id',
  `category_two_id` int(10) DEFAULT '0' COMMENT '分类二级id',
  `category_three_id` int(10) DEFAULT '0' COMMENT '三级分类id',
  `source_path` varchar(255) DEFAULT NULL COMMENT '资源的原来链接',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of dc_source
-- ----------------------------
INSERT INTO `dc_source` VALUES ('1', '【测试标题】海拔平台', '/upload/2019-09/11/imgnews01.png', '1554444447', '1', '', ',1,', '', '9', null, null, null);
INSERT INTO `dc_source` VALUES ('2', '【测试文本】海拔文本的测试', '/upload/2019-09/11/imgnews01.png', '1564784587', '3', null, ',1,', '', '9', null, null, null);
INSERT INTO `dc_source` VALUES ('3', '【测试音频字幕文本】海拔专用音频字幕文本测试', '/upload/2019-09/11/imgnews01.png', '1564545454', '4', null, ',1,', '', '9', null, null, null);

-- ----------------------------
-- Table structure for `dc_source_category`
-- ----------------------------
DROP TABLE IF EXISTS `dc_source_category`;
CREATE TABLE `dc_source_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) DEFAULT '0' COMMENT '上级的id',
  `name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '名称',
  `created_at` int(10) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_source_category
-- ----------------------------
INSERT INTO `dc_source_category` VALUES ('1', '0', '一级测试一', '1555555555');
INSERT INTO `dc_source_category` VALUES ('2', '0', '一级测试二', '1889562447');
INSERT INTO `dc_source_category` VALUES ('3', '0', '一级测试三', '0');
INSERT INTO `dc_source_category` VALUES ('4', '0', '一级测试四', '0');
INSERT INTO `dc_source_category` VALUES ('5', '1', '2一级测试一', '0');
INSERT INTO `dc_source_category` VALUES ('9', '5', '3一级测试一', '0');

-- ----------------------------
-- Table structure for `dc_source_dictation`
-- ----------------------------
DROP TABLE IF EXISTS `dc_source_dictation`;
CREATE TABLE `dc_source_dictation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(10) DEFAULT '0',
  `source_period_id` int(10) DEFAULT '0',
  `content` text COLLATE utf8mb4_unicode_ci,
  `created_at` int(10) DEFAULT '0',
  `time` int(10) DEFAULT '0',
  `user_id` int(10) DEFAULT '0',
  `type` tinyint(3) DEFAULT '0' COMMENT '1平台  2 个人  3合成录音的听写 4精品课程',
  `pid` tinyint(3) DEFAULT '0' COMMENT '针对于合成的音频的听写  1平台的合成  2 个人素材的合成 3精品课程的听写',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_source_dictation
-- ----------------------------
INSERT INTO `dc_source_dictation` VALUES ('2', '3', '3', '测试的听写内容！', '1570692991', '10', '1', '1', null);
INSERT INTO `dc_source_dictation` VALUES ('3', '1', '0', '测试听写内容啊', '1569316916', '10', '7', '3', '1');
INSERT INTO `dc_source_dictation` VALUES ('4', '1', '0', '测试的听写内容', '1570696416', '10', '1', '2', '0');
INSERT INTO `dc_source_dictation` VALUES ('5', '4', '0', '测试的听写内容', '1570698920', '10', '1', '2', '0');
INSERT INTO `dc_source_dictation` VALUES ('6', '1', '0', '测试听写内容啊', '1570702293', '10', '1', '3', '1');

-- ----------------------------
-- Table structure for `dc_source_info`
-- ----------------------------
DROP TABLE IF EXISTS `dc_source_info`;
CREATE TABLE `dc_source_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(10) DEFAULT '0' COMMENT '素材主表id',
  `path` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `notice` text CHARACTER SET utf8mb4 COMMENT '提示词',
  `words` text CHARACTER SET utf8mb4 COMMENT '生词汇总',
  `answer` text CHARACTER SET utf8mb4 COMMENT '标准答案',
  `subtitles` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '字幕文件',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=swe7;

-- ----------------------------
-- Records of dc_source_info
-- ----------------------------
INSERT INTO `dc_source_info` VALUES ('1', '1', null, '测试', '测试', '测试', null);
INSERT INTO `dc_source_info` VALUES ('2', '1', null, '测试1', '测试1', '测试1', null);
INSERT INTO `dc_source_info` VALUES ('3', '3', null, '测试2', '测试2', '测试2', null);
INSERT INTO `dc_source_info` VALUES ('4', '3', null, '测试3', '测试3', '测试3', null);

-- ----------------------------
-- Table structure for `dc_source_log`
-- ----------------------------
DROP TABLE IF EXISTS `dc_source_log`;
CREATE TABLE `dc_source_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) DEFAULT '0',
  `source_id` int(10) DEFAULT '0',
  `created_at` int(10) DEFAULT '0',
  `source_type` int(10) DEFAULT '0' COMMENT '1平台 2个人',
  `do_type` int(10) DEFAULT '0' COMMENT '1听写  2朗读 3翻译 4字幕',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_source_log
-- ----------------------------
INSERT INTO `dc_source_log` VALUES ('2', '1', '3', '1568270568', '1', '1');
INSERT INTO `dc_source_log` VALUES ('3', '1', '3', '1568685581', '1', '3');
INSERT INTO `dc_source_log` VALUES ('4', '1', '3', '1568778503', '1', '4');
INSERT INTO `dc_source_log` VALUES ('5', '1', '3', '1570693901', '1', '2');
INSERT INTO `dc_source_log` VALUES ('6', '1', '1', '1570696416', '2', '1');
INSERT INTO `dc_source_log` VALUES ('7', '1', '4', '1570696422', '2', '1');
INSERT INTO `dc_source_log` VALUES ('8', '1', '1', '1570698971', '2', '4');
INSERT INTO `dc_source_log` VALUES ('9', '1', '4', '1570699099', '2', '2');
INSERT INTO `dc_source_log` VALUES ('10', '1', '4', '1570699338', '2', '3');

-- ----------------------------
-- Table structure for `dc_source_read`
-- ----------------------------
DROP TABLE IF EXISTS `dc_source_read`;
CREATE TABLE `dc_source_read` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(10) DEFAULT '0' COMMENT '资源的id',
  `source_period_id` int(10) DEFAULT '0' COMMENT '附表的id',
  `path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '朗读之后的路径',
  `created_at` int(10) DEFAULT '0',
  `user_id` int(10) DEFAULT '0',
  `type` tinyint(3) DEFAULT '0' COMMENT '1平台 2 个人 3',
  `pid` int(10) DEFAULT '0' COMMENT '听写记录的id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_source_read
-- ----------------------------
INSERT INTO `dc_source_read` VALUES ('1', '3', '3', 'https://www.baidu.com', '1570693901', '1', '1', '0');
INSERT INTO `dc_source_read` VALUES ('2', '4', '1', 'https://www.baidu.com', '1570699099', '1', '2', '0');

-- ----------------------------
-- Table structure for `dc_source_subtitles`
-- ----------------------------
DROP TABLE IF EXISTS `dc_source_subtitles`;
CREATE TABLE `dc_source_subtitles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(10) DEFAULT '0',
  `source_period_id` int(10) DEFAULT '0',
  `content` text,
  `created_at` int(10) DEFAULT '0' COMMENT '创建时间',
  `user_id` int(10) DEFAULT '0',
  `type` tinyint(3) DEFAULT '0' COMMENT '1平台上传 2个人上传 3合成录音的只做弹幕 4 精品课程',
  `pid` tinyint(3) DEFAULT '0' COMMENT '1平台素材 2个人素材 3 精品课程',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of dc_source_subtitles
-- ----------------------------
INSERT INTO `dc_source_subtitles` VALUES ('1', '3', '3', '{\\\"11\\\":{\\\"time\\\":\\\"00:11\\\",\\\"content\\\":\\\" \\\\u6d4b\\\\u8bd5\\\\u6570\\\\u636e\\\\u662f1 \\\\u6d4b\\\\u8bd5\\\\u7684\\\\u5185\\\\u5bb9\\\\u662f\\\\u4ec0\\\\u4e48\\\\uff1f\\\"}}', '1568778503', '1', '1', null);
INSERT INTO `dc_source_subtitles` VALUES ('2', '1', '0', '{\\\"61\\\":{\\\"time\\\":\\\"01:01\\\",\\\"content\\\":\\\" zhangm,i\\\"}}', '1569317133', '0', '3', '1');
INSERT INTO `dc_source_subtitles` VALUES ('3', '1', '0', '{\\\"61\\\":{\\\"time\\\":\\\"01:01\\\",\\\"content\\\":\\\" zhangm,i\\\"}}', '1569317225', '0', '3', '1');
INSERT INTO `dc_source_subtitles` VALUES ('4', '1', '0', '{\\\"61\\\":{\\\"time\\\":\\\"01:01\\\",\\\"content\\\":\\\" zhangm,i\\\"}}', '1569317293', '7', '3', '1');
INSERT INTO `dc_source_subtitles` VALUES ('5', '1', '0', '{\\\"10\\\":{\\\"time\\\":\\\"00:10\\\",\\\"content\\\":\\\" 123\\\\u6d4b\\\\u8bd5\\\\u7684\\\\u6570\\\\u636e\\\\u5c31\\\\u662f\\\\u8fd9\\\\u6837\\\\u7684\\\\uff01\\\"}}', '1570699069', '1', '2', '0');
INSERT INTO `dc_source_subtitles` VALUES ('6', '1', '0', '{\\\"61\\\":{\\\"time\\\":\\\"01:01\\\",\\\"content\\\":\\\" zhangm,i\\\"}}', '1570703091', '1', '3', '1');

-- ----------------------------
-- Table structure for `dc_source_text`
-- ----------------------------
DROP TABLE IF EXISTS `dc_source_text`;
CREATE TABLE `dc_source_text` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(10) DEFAULT '0',
  `title` varchar(255) DEFAULT NULL COMMENT '标题',
  `content` text COMMENT '文本内容',
  `created_at` int(11) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of dc_source_text
-- ----------------------------
INSERT INTO `dc_source_text` VALUES ('1', '2', '分段文本的标题', '文章的内容文章的内容文章的内容文章的内容文章的内容文章的内容文章的内容文章的内容文章的内容文章的内容文章的内容文章的内容去文章的内容文章的内容文章的内容文章的内容文章的内容', '1871546204');
INSERT INTO `dc_source_text` VALUES ('2', '3', '分段文本11', '测试2测试2测试2测试2测试2测试2测试2测试2', '1565545445');
INSERT INTO `dc_source_text` VALUES ('3', '3', '分选文本22', '测试3测试3测试3测试3测试3 ', '1564847854');

-- ----------------------------
-- Table structure for `dc_source_translation`
-- ----------------------------
DROP TABLE IF EXISTS `dc_source_translation`;
CREATE TABLE `dc_source_translation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(10) DEFAULT '0' COMMENT '主表的id',
  `source_period_id` int(10) DEFAULT '0',
  `content` text CHARACTER SET utf8mb4 COMMENT '翻译内容',
  `grammar` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '语法',
  `words` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '单词',
  `created_at` int(10) DEFAULT '0' COMMENT '创建时间',
  `user_id` int(10) DEFAULT '0' COMMENT '会员的id',
  `type` tinyint(4) DEFAULT '0' COMMENT '1平台 2个人 3  针对音视频的听写记录 4 精品课程',
  `pid` int(10) DEFAULT '0' COMMENT '1 平台素材 2个人素材 3精品课程',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_source_translation
-- ----------------------------
INSERT INTO `dc_source_translation` VALUES ('1', '3', '3', '测试翻译内容', '中文语法', '测试单词', '1568685583', '2', '1', '0');
INSERT INTO `dc_source_translation` VALUES ('2', '1', '1', '测试翻译内容', '测试语法', '单词', '1569319713', '7', '3', '1');
INSERT INTO `dc_source_translation` VALUES ('3', '3', '3', '测试翻译内容', '中文语法', '测试单词', '1570694043', '1', '1', '0');
INSERT INTO `dc_source_translation` VALUES ('4', '4', '1', '个人素材文本翻译内容!', '测试语法', '测试单词', '1570699338', '1', '2', '0');
INSERT INTO `dc_source_translation` VALUES ('5', '1', '1', '测试翻译内容', '测试语法', '单词', '1570759391', '1', '3', '1');

-- ----------------------------
-- Table structure for `dc_source_words`
-- ----------------------------
DROP TABLE IF EXISTS `dc_source_words`;
CREATE TABLE `dc_source_words` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '生词名称',
  `sort_id` int(10) DEFAULT '0' COMMENT '分类的id',
  `paraphrase` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '释义',
  `pronunciation` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '读音',
  `pronunciation_words` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '读音拼写',
  `sentences` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '例句',
  `associate` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '联想',
  `user_id` int(10) DEFAULT '0',
  `type` tinyint(4) DEFAULT '0' COMMENT '1平台 2个人 3',
  `source_id` int(10) DEFAULT '0' COMMENT '资源id  ',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_source_words
-- ----------------------------
INSERT INTO `dc_source_words` VALUES ('1', '测试生词名称', '2', '测试释义', 'http://www.baidu.com', '/$dov/', '测试例句', '测试联想', '1', '3', '1');
INSERT INTO `dc_source_words` VALUES ('2', '饕餮', '2', '释义测试', '测试读音的七牛云链接', null, '测试例句', '111', '1', '1', '3');
INSERT INTO `dc_source_words` VALUES ('3', '哈哈哈', '2', '标识什么东西11111111', 'http://www.baidu.com', 'hahaha', 'hahah   测试的时候不', '嘿嘿  嘻嘻 呼呼', '1', '2', '1');

-- ----------------------------
-- Table structure for `dc_source_word_sort`
-- ----------------------------
DROP TABLE IF EXISTS `dc_source_word_sort`;
CREATE TABLE `dc_source_word_sort` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL COMMENT '分类id',
  `created_at` int(10) DEFAULT '0',
  `user_id` int(10) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of dc_source_word_sort
-- ----------------------------
INSERT INTO `dc_source_word_sort` VALUES ('1', '0', '日语分类', '0', '1');
INSERT INTO `dc_source_word_sort` VALUES ('2', '0', '英语生词', '1568690566', '1');
INSERT INTO `dc_source_word_sort` VALUES ('3', '1', '日常英语生词', '1568690587', '1');
INSERT INTO `dc_source_word_sort` VALUES ('4', '1', '行业英语生词', '1568690596', '1');
INSERT INTO `dc_source_word_sort` VALUES ('5', '1', '行业英语生词1', '1570700417', '1');
INSERT INTO `dc_source_word_sort` VALUES ('6', '0', '行业英语生词1', '1570700423', '1');

-- ----------------------------
-- Table structure for `dc_suggest`
-- ----------------------------
DROP TABLE IF EXISTS `dc_suggest`;
CREATE TABLE `dc_suggest` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content` text COLLATE utf8mb4_unicode_ci,
  `created_at` int(10) DEFAULT '0',
  `user_id` int(10) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_suggest
-- ----------------------------
INSERT INTO `dc_suggest` VALUES ('1', '测试的反馈内容！', '1568699922', '1');
INSERT INTO `dc_suggest` VALUES ('2', '测试的反馈内容！', '1568699940', '1');
INSERT INTO `dc_suggest` VALUES ('3', '测试的反馈内容！', '1570701514', '1');
INSERT INTO `dc_suggest` VALUES ('4', '测试的反馈内容！', '1570701536', '1');
INSERT INTO `dc_suggest` VALUES ('5', '测试的反馈内容！', '1570701539', '1');

-- ----------------------------
-- Table structure for `dc_tags`
-- ----------------------------
DROP TABLE IF EXISTS `dc_tags`;
CREATE TABLE `dc_tags` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `cid` int(10) DEFAULT '0',
  `name` varchar(20) NOT NULL,
  `click` int(10) DEFAULT '1',
  `lang` int(10) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_tags
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_tags_category`
-- ----------------------------
DROP TABLE IF EXISTS `dc_tags_category`;
CREATE TABLE `dc_tags_category` (
  `cid` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL,
  `lang` int(10) DEFAULT '1',
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_tags_category
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_tags_relation`
-- ----------------------------
DROP TABLE IF EXISTS `dc_tags_relation`;
CREATE TABLE `dc_tags_relation` (
  `aid` int(10) DEFAULT NULL,
  `tid` int(10) DEFAULT NULL,
  KEY `aid` (`aid`),
  KEY `tid` (`tid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_tags_relation
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_upload`
-- ----------------------------
DROP TABLE IF EXISTS `dc_upload`;
CREATE TABLE `dc_upload` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `file` varchar(250) DEFAULT NULL,
  `title` varchar(250) DEFAULT NULL,
  `folder` varchar(250) DEFAULT NULL,
  `ext` varchar(20) DEFAULT NULL,
  `size` int(10) DEFAULT NULL,
  `type` varchar(250) DEFAULT NULL,
  `time` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `type` (`type`) USING BTREE,
  KEY `ext` (`ext`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_upload
-- ----------------------------
INSERT INTO `dc_upload` VALUES ('1', '/upload/2019-09/11/imgbanner02.png', 'img／banner02', '2019-09-11', 'png', '141788', 'form', '1568183385');
INSERT INTO `dc_upload` VALUES ('2', '/upload/2019-09/11/iconlogo1.png', 'icon／logo (1)', '2019-09-11', 'png', '3540', 'form', '1568183650');
INSERT INTO `dc_upload` VALUES ('3', '/upload/2019-09/11/imgbanner02-b6d5d.png', 'img／banner02', '2019-09-11', 'png', '141788', 'form', '1568184084');
INSERT INTO `dc_upload` VALUES ('4', '/upload/2019-09/11/imgbanner02-47adf.png', 'img／banner02', '2019-09-11', 'png', '141788', 'form', '1568184412');
INSERT INTO `dc_upload` VALUES ('5', '/upload/2019-09/11/imgbanner02-76e25.png', 'img／banner02', '2019-09-11', 'png', '141788', 'form', '1568184454');
INSERT INTO `dc_upload` VALUES ('6', '/upload/2019-09/11/imgbanner02-ecf66.png', 'img／banner02', '2019-09-11', 'png', '141788', 'form', '1568184495');
INSERT INTO `dc_upload` VALUES ('7', '/upload/2019-09/11/imgbanner02-408da.png', 'img／banner02', '2019-09-11', 'png', '141788', 'form', '1568185809');
INSERT INTO `dc_upload` VALUES ('8', '/upload/2019-09/11/imgnews01.png', 'img／news／01', '2019-09-11', 'png', '26355', 'form', '1568185819');
INSERT INTO `dc_upload` VALUES ('9', '/upload/2019-09/11/imgbanner02-ba673.png', 'img／banner02', '2019-09-11', 'png', '141788', 'form', '1568188751');
INSERT INTO `dc_upload` VALUES ('10', '/upload/2019-09/18/imgnews01.png', 'img／news／01', '2019-09-18', 'png', '26355', 'form', '1568795865');
INSERT INTO `dc_upload` VALUES ('11', '/upload/2019-09/18/imgnews01-c460c.png', 'img／news／01', '2019-09-18', 'png', '26355', 'form', '1568795943');

-- ----------------------------
-- Table structure for `dc_upload_category`
-- ----------------------------
DROP TABLE IF EXISTS `dc_upload_category`;
CREATE TABLE `dc_upload_category` (
  `id` int(10) DEFAULT NULL,
  `file_id` int(10) DEFAULT NULL,
  KEY `id` (`id`),
  KEY `file_id` (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_upload_category
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_upload_content`
-- ----------------------------
DROP TABLE IF EXISTS `dc_upload_content`;
CREATE TABLE `dc_upload_content` (
  `id` int(10) DEFAULT NULL,
  `file_id` int(10) DEFAULT NULL,
  KEY `id` (`id`),
  KEY `file_id` (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_upload_content
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_upload_form`
-- ----------------------------
DROP TABLE IF EXISTS `dc_upload_form`;
CREATE TABLE `dc_upload_form` (
  `id` int(10) DEFAULT NULL,
  `file_id` int(10) DEFAULT NULL,
  KEY `id` (`id`),
  KEY `file_id` (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_upload_form
-- ----------------------------
INSERT INTO `dc_upload_form` VALUES ('1', '9');
INSERT INTO `dc_upload_form` VALUES ('1', '10');
INSERT INTO `dc_upload_form` VALUES ('2', '11');

-- ----------------------------
-- Table structure for `dc_upload_plus`
-- ----------------------------
DROP TABLE IF EXISTS `dc_upload_plus`;
CREATE TABLE `dc_upload_plus` (
  `id` int(10) DEFAULT NULL,
  `file_id` int(10) DEFAULT NULL,
  KEY `id` (`id`),
  KEY `file_id` (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of dc_upload_plus
-- ----------------------------

-- ----------------------------
-- Table structure for `dc_user`
-- ----------------------------
DROP TABLE IF EXISTS `dc_user`;
CREATE TABLE `dc_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mobile` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '手机号',
  `password` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '密码',
  `created_at` int(255) DEFAULT '0',
  `avatar` varchar(255) CHARACTER SET utf8mb4 DEFAULT '',
  `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '昵称',
  `birthday` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '生日',
  `sex` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '性别',
  `sign` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '签名',
  `english_level` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '英语等级',
  `japanese_level` varchar(255) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '日语等级',
  `num` int(10) DEFAULT '0' COMMENT '听写次数',
  `endtime` int(10) DEFAULT '0' COMMENT '会员结束时间',
  `type` tinyint(3) DEFAULT '1' COMMENT '1普通会员  2猩听译',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_user
-- ----------------------------
INSERT INTO `dc_user` VALUES ('1', '18733333333', '4297f44b13955235245b2497399d7a93', '1568167365', '/upload/1/20191010/1570701460graph.png', '测试昵称', '1994-0521', '1', '测试签名越来越还见', '1', '1', '0', '0', '3');
INSERT INTO `dc_user` VALUES ('2', '18755555555', '4297f44b13955235245b2497399d7a93', '1568169473', '/upload/2019-09/11/iconlogo1.png', '张敏', '', '', '', '', '', '0', '0', '3');
INSERT INTO `dc_user` VALUES ('3', '18855555555', '4297f44b13955235245b2497399d7a93', '1568770073', '', '', '', '', '', '', '', '0', '0', '1');
INSERT INTO `dc_user` VALUES ('4', '1396676462', 'a20b9094413df1478404b5a4337ab6b1', '1568773376', '', '', '', '', '', '', '', '0', '0', '1');
INSERT INTO `dc_user` VALUES ('5', '18733333333', '4297f44b13955235245b2497399d7a93', '1568774575', '', '', '', '', '', '', '', '0', '0', '1');
INSERT INTO `dc_user` VALUES ('6', '13966706462', 'a20b9094413df1478404b5a4337ab6b1', '1568783093', '/upload/6/20190918/1568799023home_img04.png', '', '', '', '', '', '', '0', '0', '1');
INSERT INTO `dc_user` VALUES ('7', '18955555555', '4297f44b13955235245b2497399d7a93', '1568795523', '/upload/7/20190919/1568857977icon／logo (1).png', '张敏', '2018-09-21', '1', '爱自己 爱生活', '1', '1', '0', '0', '3');

-- ----------------------------
-- Table structure for `dc_user_source`
-- ----------------------------
DROP TABLE IF EXISTS `dc_user_source`;
CREATE TABLE `dc_user_source` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `created_at` int(10) DEFAULT '0',
  `type` tinyint(4) DEFAULT '0' COMMENT '1平台 2个人 3',
  `path` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '文本合成路径',
  `user_id` int(10) DEFAULT '0' COMMENT '上传素材的',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_user_source
-- ----------------------------
INSERT INTO `dc_user_source` VALUES ('1', '测试标题', '/upload/15706948181/20191010/1570694818pictorialbar.png', '1570694818', '3', '', '1');
INSERT INTO `dc_user_source` VALUES ('2', '测试标题', '/upload/15706951541/20191010/1570695154pictorialbar.png', '1570695154', '3', '', '1');
INSERT INTO `dc_user_source` VALUES ('3', '测试标题', '/upload/15706955831/20191010/1570695583pictorialbar.png', '1570695583', '3', '', '1');
INSERT INTO `dc_user_source` VALUES ('4', '测试标题', '/upload/15706956281/20191010/1570695628pictorialbar.png', '1570695628', '3', '', '1');
INSERT INTO `dc_user_source` VALUES ('5', '测试标题', '/upload/15706956451/20191010/1570695645pictorialbar.png', '1570695645', '3', '', '1');
INSERT INTO `dc_user_source` VALUES ('6', '测试标题', '/upload/15706999811/20191010/1570699981pictorialbar.png', '1570699981', '3', '', '1');
INSERT INTO `dc_user_source` VALUES ('7', '测试标题', '/upload/15706999921/20191010/1570699992pictorialbar.png', '1570699992', '3', '', '1');
INSERT INTO `dc_user_source` VALUES ('8', '测试标题', '/upload/15707000671/20191010/1570700067pictorialbar.png', '1570700067', '3', '', '1');

-- ----------------------------
-- Table structure for `dc_user_source_text`
-- ----------------------------
DROP TABLE IF EXISTS `dc_user_source_text`;
CREATE TABLE `dc_user_source_text` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_period_id` int(10) DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `content` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `created_at` int(10) DEFAULT '0',
  `path` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '路径',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of dc_user_source_text
-- ----------------------------
INSERT INTO `dc_user_source_text` VALUES ('11', '6', '测试1', '测试内容1', '1570699981', null);
INSERT INTO `dc_user_source_text` VALUES ('12', '6', '测试2', '测试内容2', '1570699981', null);
INSERT INTO `dc_user_source_text` VALUES ('13', '7', '测试1', '测试内容1', '1570699992', null);
INSERT INTO `dc_user_source_text` VALUES ('14', '7', '测试2', '测试内容2', '1570699992', null);
INSERT INTO `dc_user_source_text` VALUES ('15', '8', '测试1', '测试内容1', '1570700067', null);
INSERT INTO `dc_user_source_text` VALUES ('16', '8', '测试2', '测试内容2', '1570700067', null);
