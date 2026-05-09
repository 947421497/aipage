# 数据库文档

## 目录

- [数据库概述](#数据库概述)
- [表结构](#表结构)
- [字段说明](#字段说明)
- [索引设计](#索引设计)
- [初始化数据](#初始化数据)

---

## 数据库概述

### 数据库信息

| 属性 | 值 |
|------|-----|
| 数据库类型 | MySQL |
| 字符集 | utf8mb4 |
| 表前缀 | xphp_ |
| 排序规则 | utf8mb4_general_ci |

### 支持版本

- MySQL 5.6 及以上
- MySQL 5.7
- MySQL 8.0

### 设计原则

本框架数据库设计遵循以下原则，确保系统的可扩展性和数据完整性：

1. **存储引擎统一**：所有表统一使用 InnoDB 存储引擎，支持事务处理和外键约束
2. **字符集统一**：所有表统一使用 utf8mb4 字符集，支持完整的 Unicode 字符，包括 emoji 表情符号
3. **主键设计**：主键统一使用无符号整数自增 ID，保证查询效率
4. **时间存储**：时间字段统一使用 Unix 时间戳格式存储，便于跨时区处理和计算
5. **状态字段**：状态字段统一使用 tinyint 无符号整数，节省存储空间

---

## 表结构

### 1. 用户表（xphp_user）

用户表是系统的核心表之一，用于存储所有用户的基本信息，包括前台注册用户和后台管理员。表结构设计考虑了用户资料的完整性和扩展性，支持头像、等级等常用功能。

```sql
CREATE TABLE `xphp_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(32) NOT NULL DEFAULT '' COMMENT '密码(MD5)',
  `nickname` varchar(20) NOT NULL DEFAULT '' COMMENT '昵称',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `mobile` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `qq` varchar(20) NOT NULL DEFAULT '' COMMENT 'QQ号',
  `bio` varchar(120) NOT NULL DEFAULT '' COMMENT '个人简介',
  `avatar` varchar(200) NOT NULL DEFAULT '' COMMENT '头像',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '等级',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态(0正常/1禁用)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='用户表';
```

### 2. 菜单表（xphp_menu）

菜单表用于存储后台管理系统的多级菜单结构，支持最多三级菜单。通过 `parent_id` 字段建立父子关系，`parent_id=0` 表示顶级菜单。通过 `href`、`sign`、`icon` 等字段，可以灵活配置菜单的显示和跳转行为。`is_sys` 字段用于区分系统内置菜单和用户自定义菜单，防止误删系统菜单。

```sql
CREATE TABLE `xphp_menu` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID(0为顶级)',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '菜单标题',
  `href` varchar(100) NOT NULL DEFAULT '' COMMENT '链接地址',
  `sign` varchar(20) NOT NULL DEFAULT '' COMMENT '菜单标识',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单图标',
  `is_sys` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '系统菜单(0可删/1禁删)',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态(0正常/1禁用)',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='菜单表';
```

### 3. 配置表（xphp_config）

配置表采用键值对形式存储系统配置信息，支持动态添加、修改、删除配置项。这种设计使得系统配置可以在后台界面进行管理，无需修改代码文件。config_type 字段可以用于区分不同类型的配置（如文本、开关、下拉选择等）。

```sql
CREATE TABLE `xphp_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '配置名称',
  `config_key` varchar(20) NOT NULL DEFAULT '' COMMENT '配置键名',
  `config_value` varchar(255) NOT NULL DEFAULT '' COMMENT '配置键值',
  `config_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '配置类型',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态(0正常/1禁用)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='配置表';
```

---

## 字段说明

### 用户表字段详解

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) | AUTO_INCREMENT | 用户唯一标识符，自增主键 |
| username | varchar(20) | '' | 用户名，用于登录认证，长度限制20字符 |
| password | varchar(32) | '' | 密码，使用MD5加密存储，32位十六进制字符串 |
| nickname | varchar(20) | '' | 昵称，用于前台显示，可由用户自行修改 |
| email | varchar(100) | '' | 邮箱地址，用于找回密码和接收通知 |
| mobile | char(11) | '' | 手机号码，固定11位中国手机号格式 |
| qq | varchar(20) | '' | QQ号码，可选字段 |
| bio | varchar(120) | '' | 个人简介/格言，最大120字符 |
| avatar | varchar(200) | '' | 头像URL地址，存储头像图片路径 |
| level | tinyint(1) | 0 | 用户等级，用于会员等级体系 |
| create_time | int(10) | 0 | 注册时间，Unix时间戳格式 |
| status | tinyint(1) | 0 | 账号状态：0=正常，1=禁用 |

### 菜单表字段详解

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | smallint(5) | AUTO_INCREMENT | 菜单唯一标识符 |
| parent_id | smallint(5) | 0 | 父级菜单ID，0表示顶级菜单 |
| title | varchar(50) | '' | 菜单在后台界面显示的标题 |
| href | varchar(100) | '' | 点击菜单后跳转的链接地址，格式为控制器/方法 |
| sign | varchar(20) | '' | 菜单唯一标识符，用于权限验证和菜单定位 |
| icon | varchar(100) | '' | 菜单图标类名，使用Material Design Icons |
| is_sys | tinyint(1) | 0 | 系统菜单标识：0=可删除，1=禁止删除 |
| sort | int(11) | 0 | 排序权重，数字越大排序越靠前 |
| update_time | int(10) | 0 | 最后更新时间，Unix时间戳格式 |
| status | tinyint(1) | 0 | 菜单状态：0=停用，1=启用 |

### 配置表字段详解

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) | AUTO_INCREMENT | 配置项唯一标识符 |
| name | varchar(20) | '' | 配置项的中文名称，用于后台显示 |
| config_key | varchar(20) | '' | 配置项的键名，程序中通过此键名获取值 |
| config_value | varchar(255) | '' | 配置项的键值，存储实际的配置内容 |
| config_type | tinyint(1) | 0 | 配置类型：0=文本，1=开关等 |
| status | tinyint(1) | 0 | 配置状态：0=启用，1=禁用 |

---

## 索引设计

### 主键索引

所有表都设计了主键索引，确保每条记录的唯一性和快速定位。

| 表名 | 索引名 | 字段 | 类型 |
|------|--------|------|------|
| xphp_user | PRIMARY | id | 主键 |
| xphp_menu | PRIMARY | id | 主键 |
| xphp_menu | idx_parent_id | parent_id | 普通索引 |
| xphp_config | PRIMARY | id | 主键 |

### 业务索引建议

当前基础表结构较为简单，未预设额外索引。在实际项目开发中，建议根据业务查询需求添加以下业务索引，以提升查询性能：

```sql
-- 用户表：用户名唯一索引，防止重复注册
ALTER TABLE `xphp_user` ADD UNIQUE INDEX `idx_username` (`username`);

-- 用户表：邮箱唯一索引，用于邮箱登录和验证
ALTER TABLE `xphp_user` ADD UNIQUE INDEX `idx_email` (`email`);

-- 用户表：手机号索引，用于手机号登录
ALTER TABLE `xphp_user` ADD INDEX `idx_mobile` (`mobile`);

-- 菜单表：标识唯一索引，确保菜单标识唯一性
ALTER TABLE `xphp_menu` ADD UNIQUE INDEX `idx_sign` (`sign`);

-- 菜单表：状态索引，用于筛选正常状态的菜单
ALTER TABLE `xphp_menu` ADD INDEX `idx_status` (`status`);

-- 配置表：键名唯一索引，防止配置键名重复
ALTER TABLE `xphp_config` ADD UNIQUE INDEX `idx_config_key` (`config_key`);
```

---

## 初始化数据

### 初始化管理员账号

安装系统时，会自动创建默认管理员账户。初始密码可通过配置文件或安装向导设置。

```sql
INSERT INTO `xphp_user` VALUES 
(1, 'admin', '0192023a7bbd73250516f069df18b500', '管理员', 'admin@example.com', '', '', '这个人很懒，什么都没写', '', 3, UNIX_TIMESTAMP(), 0);
```

> **重要提示**：密码 `0192023a7bbd73250516f069df18b500` 是 `admin123` 的MD5加密值。在生产环境中，请务必在首次登录后立即修改默认密码，并使用强密码策略。

### 初始化菜单数据

系统会预置常用的后台管理菜单，包括用户管理、菜单管理、网站配置、数据备份等模块。初始化的菜单均为一级菜单（parent_id=0）。

```sql
INSERT INTO `xphp_menu` VALUES 
(1, 0, '用户管理', 'user/index', 'user', 'mdi mdi-account', 1, 1070, UNIX_TIMESTAMP(), 1),
(2, 0, '网站配置', 'config/index', 'config', 'mdi mdi-cog', 1, 1080, UNIX_TIMESTAMP(), 1),
(3, 0, '菜单管理', 'menu/index', 'menu', 'mdi mdi-menu', 1, 1090, UNIX_TIMESTAMP(), 1),
(4, 0, '数据备份', 'backup/index', 'backup', 'mdi mdi-content-save', 1, 1100, UNIX_TIMESTAMP(), 1);
```

### 初始化配置数据

系统预置了网站基本配置项，包括网站标题、关键词、描述、版权信息、公告等。

```sql
INSERT INTO `xphp_config` VALUES 
(1, '网站标题', 'site_title', '一鱼快速构架 · XStart', 0, 1),
(2, '关键词', 'site_kw', '一鱼PHP框架,无念的编程圈,xphp,PHP框架,MVC框架', 0, 1),
(3, '网站描述', 'site_desc', '一个超轻量级MVC开发PHP框架', 0, 1),
(4, '网站版权', 'site_copy', 'XStart_v1.0', 0, 1),
(5, '版权链接', 'site_link', 'http://xstart.xphp.net', 0, 1),
(6, '网站公告', 'site_notice', '一鱼框架官网：新域名xphp.net QQ群325825297', 0, 1),
(7, '首页h1', 'site_h1', 'XStart', 0, 1),
(8, '首页h2', 'site_h2', '基于XPHP框架开发，包含用户模块和简易后台。', 0, 1);
```

---

## 数据字典

### 状态字段说明

#### 用户状态（status）

| 值 | 说明 | 使用场景 |
|----|------|----------|
| 0 | 正常 | 用户可正常登录和使用所有功能 |
| 1 | 禁用 | 账号被禁用，无法登录 |

#### 菜单系统标识（is_sys）

| 值 | 说明 | 注意事项 |
|----|------|----------|
| 0 | 可删除 | 用户可自行添加的菜单，可自由删除 |
| 1 | 禁止删除 | 系统内置菜单，删除可能导致功能异常 |

#### 菜单状态（status）

| 值 | 说明 |
|----|------|
| 0 | 正常 |
| 1 | 禁用 |

---

## ER关系图

```
┌──────────────────────────────────────────────────────────────────────────┐
│                              xphp_user                                    │
├──────────────────────────────────────────────────────────────────────────┤
│ id (PK)           │ int(11)      │ 用户唯一标识                          │
│ username          │ varchar(20)  │ 用户名，用于登录认证                    │
│ password          │ varchar(32)  │ 密码，MD5加密存储                       │
│ nickname          │ varchar(20)  │ 昵称，显示名称                          │
│ email             │ varchar(100) │ 邮箱地址                               │
│ mobile            │ char(11)     │ 手机号码                               │
│ qq                │ varchar(20)  │ QQ号码                                │
│ bio               │ varchar(120) │ 个人简介                              │
│ avatar            │ varchar(200) │ 头像URL                               │
│ level             │ tinyint(1)   │ 用户等级                              │
│ create_time       │ int(10)      │ 注册时间                              │
│ status            │ tinyint(1)   │ 账号状态                              │
└──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 1:N (未来扩展)
                                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                              xphp_config                                  │
├──────────────────────────────────────────────────────────────────────────┤
│ id (PK)           │ int(11)      │ 配置唯一标识                          │
│ name              │ varchar(20)  │ 配置中文名称                          │
│ config_key        │ varchar(20)  │ 配置键名                              │
│ config_value      │ varchar(255) │ 配置键值                              │
│ config_type       │ tinyint(1)   │ 配置类型                              │
│ status            │ tinyint(1)   │ 配置状态                              │
└──────────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────────┐
│                              xphp_menu                                    │
├──────────────────────────────────────────────────────────────────────────┤
│ id (PK)           │ smallint(5)  │ 菜单唯一标识                          │
│ parent_id         │ smallint(5)  │ 父级ID(0为顶级)                       │
│ title             │ varchar(50)   │ 菜单标题                              │
│ href              │ varchar(100)  │ 链接地址                              │
│ sign              │ varchar(20)   │ 菜单标识                              │
│ icon              │ varchar(100)  │ 菜单图标                              │
│ is_sys            │ tinyint(1)   │ 系统菜单标识                          │
│ sort              │ int(11)      │ 排序权重                              │
│ update_time       │ int(10)      │ 更新时间                              │
│ status            │ tinyint(1)   │ 菜单状态                              │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 扩展建议

### 建议添加的功能表

随着业务发展，可能需要扩展以下数据表来满足功能需求：

1. **操作日志表（xphp_log）** - 记录用户的关键操作行为，用于安全审计
2. **附件表（xphp_attachment）** - 统一管理所有上传的文件
3. **权限表（xphp_auth）** - 实现细粒度的权限控制
4. **角色表（xphp_role）** - 用户角色管理
5. **消息表（xphp_message）** - 用户站内消息通知
6. **分类表（xphp_category）** - 内容分类管理
7. **文章表（xphp_article）** - 内容发布和管理
8. **标签表（xphp_tag）** - 内容标签管理

### 操作日志表设计示例

```sql
CREATE TABLE `xphp_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `type` varchar(20) NOT NULL DEFAULT '' COMMENT '日志类型',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '日志内容',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(255) NOT NULL DEFAULT '' COMMENT '浏览器信息',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_type` (`type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';
```

### 附件表设计示例

```sql
CREATE TABLE `xphp_attachment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传用户ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '原始文件名',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '存储路径',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '访问URL',
  `mime` varchar(50) NOT NULL DEFAULT '' COMMENT '文件MIME类型',
  `size` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `ext` varchar(10) NOT NULL DEFAULT '' COMMENT '文件扩展名',
  `md5` varchar(32) NOT NULL DEFAULT '' COMMENT '文件MD5值',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上传时间',
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_md5` (`md5`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='附件表';
```

---

## 备份与恢复

### 数据库备份

建议使用以下命令进行数据库备份：

```bash
# 全量备份
mysqldump -h localhost -u root -p xphp_db > backup_$(date +%Y%m%d).sql

# 只备份表结构
mysqldump -h localhost -u root -p --no-data xphp_db > structure.sql

# 只备份数据
mysqldump -h localhost -u root -p --no-create-info xphp_db > data.sql
```

### 数据库恢复

```bash
mysql -h localhost -u root -p xphp_db < backup_20240101.sql
```

---

## 安全建议

1. **密码安全**：生产环境中务必修改默认管理员密码，建议使用强密码（包含大小写字母、数字、特殊字符，长度不少于12位）
2. **数据库权限**：为应用程序创建专用数据库用户，遵循最小权限原则，不要使用root用户
3. **敏感信息**：数据库连接信息不要直接写在代码中，建议使用环境变量或专门的配置文件
4. **定期备份**：建立完善的数据库备份机制，定期进行备份恢复测试
5. **SQL注入防护**：框架内置SQL注入防护，但仍需开发者在编写查询时注意参数绑定
