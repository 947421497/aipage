-- 表结构: xphp_config --
CREATE TABLE `xphp_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '名称',
  `config_key` varchar(20) NOT NULL DEFAULT '' COMMENT '键名',
  `config_value` varchar(255) NOT NULL DEFAULT '' COMMENT '键值',
  `config_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='配置表';
-- <fen> --
-- 表结构: xphp_menu --
CREATE TABLE `xphp_menu` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
  `href` varchar(100) NOT NULL DEFAULT '' COMMENT '链接',
  `sign` varchar(20) NOT NULL DEFAULT '' COMMENT '标识',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '图标',
  `is_sys` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0可删1禁删',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='菜单表';
-- <fen> --
-- 表结构: xphp_user --
CREATE TABLE `xphp_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(20) NOT NULL DEFAULT '' COMMENT '用户',
  `password` varchar(32) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(20) NOT NULL DEFAULT '' COMMENT '昵称',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `mobile` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `qq` varchar(20) NOT NULL DEFAULT '' COMMENT 'QQ号',
  `bio` varchar(120) NOT NULL DEFAULT '' COMMENT '格言',
  `avatar` varchar(200) NOT NULL DEFAULT '' COMMENT '头像',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '等级',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='用户表';
-- <fen> --
