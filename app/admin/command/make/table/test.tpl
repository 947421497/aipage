CREATE TABLE `aphp_test` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
`title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
`content` text NOT NULL COMMENT '内容',
`sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
`update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
`status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='测试表';