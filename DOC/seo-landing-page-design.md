# SEO 自动化落地页系统 — AI 实现规格

> ⚠️ **完全未实现**。本文档为 AI 编码工具提供精确实现指令，所有涉及的控制器、模型、视图、数据库表、路由和公共函数均不存在。

框架：XPHP v6.1.1 | PHP >= 8.1

---

## 实现顺序

1. 数据库备份文件（`backup/bak_all_initialize/` 下新增/修改 SQL 文件）
2. 公共函数（`app/common.php` 追加4个函数）
3. 模型（6个：4个common + 2个admin）
4. 后台控制器（4个：3个extends Cp + 1个use Jump）
5. 前台控制器（2个：Site + Cron）
6. CLI 命令（1个：Cron）
7. 路由配置（`route/index.php`）
8. 视图模板（8个新增）
9. 修改现有文件（6个）

---

## 一、系统要求

| 依赖项 | 用途 |
|--------|------|
| PHP >= 8.1 | 框架运行基础 |
| ext-curl | AI API 调用 |
| ext-openssl | AI API SSL 验证 |
| ext-intl | `Transliterator` 类，`to_pinyin()` 中文→拼音 |
| ext-mbstring | 框架核心依赖 |

### 首次使用步骤

安装完成后需按以下顺序操作：

1. 后台 → AI引擎 → 添加 AI 配置（至少1个，填写 API 地址/Key/模型后测试连接）
2. 后台 → AI引擎 → 提示词模板（5条默认模板已内置，可按需编辑内容，激活所需模板）
3. 后台 → 网站配置 → 填写百度站点域名和推送 Token（如需百度推送）
4. 后台 → 关键词管理 → 添加关键词 → 批量生成页面
5. 后台 → 定时任务 → 启用所需任务 → 配置 crontab（`* * * * * cd /path && php xphpcli index.cron`）

---

## 二、数据库

表前缀 `xphp_`，新增 6 张表。所有数据库操作通过修改 `backup/bak_all_initialize/` 下的 SQL 备份文件实现，安装器会按文件名排序读取并执行。

**备份文件格式**：
- 文件名按数字前缀排序执行：`1_drop_table.sql` → `2_create_table.sql` → `3_insert_xxx_partN.sql`
- 每条 SQL 语句之间用 `-- <fen> --` 分隔（安装器按此分割逐条执行）
- 每条 INSERT 单独一行，末尾加 `-- <fen> --`

### 2.1 修改 `1_drop_table.sql`

在现有内容末尾追加 6 张新表的 DROP 语句：

```sql
-- 清空表: xphp_ai_config --
DROP TABLE IF EXISTS `xphp_ai_config`;
-- <fen> --
-- 清空表: xphp_prompt --
DROP TABLE IF EXISTS `xphp_prompt`;
-- <fen> --
-- 清空表: xphp_keyword --
DROP TABLE IF EXISTS `xphp_keyword`;
-- <fen> --
-- 清空表: xphp_page --
DROP TABLE IF EXISTS `xphp_page`;
-- <fen> --
-- 清空表: xphp_task --
DROP TABLE IF EXISTS `xphp_task`;
-- <fen> --
-- 清空表: xphp_task_log --
DROP TABLE IF EXISTS `xphp_task_log`;
-- <fen> --
```

### 2.2 修改 `2_create_table.sql`

在现有内容末尾追加 6 张新表的 CREATE 语句：

**约束**：
- UNIQUE 字段使用 `NOT NULL DEFAULT ''`（应用层与数据库层双重约束，保持一致性）
- 时间字段 `int unsigned NOT NULL DEFAULT 0`（Model 基类自动写入 `create_time`/`update_time`，缺少则 SQL 错误）
- 索引命名：唯一索引 `uk_字段名`，普通索引 `idx_字段名`，复合索引 `idx_字段1_字段2`

```sql
-- 表结构: xphp_ai_config --
CREATE TABLE `xphp_ai_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '配置名称',
  `api_type` varchar(20) NOT NULL DEFAULT 'openai' COMMENT '接口类型：openai/anthropic/ollama',
  `api_url` varchar(500) NOT NULL DEFAULT '' COMMENT 'API完整URL',
  `api_key` varchar(1000) NOT NULL DEFAULT '' COMMENT 'API Key（加密存储）',
  `model` varchar(100) NOT NULL DEFAULT '' COMMENT '模型名称',
  `max_tokens` int(11) unsigned NOT NULL DEFAULT '2000' COMMENT '最大token数',
  `temperature` decimal(4,2) NOT NULL DEFAULT '0.70' COMMENT '温度0.00-2.00',
  `max_retries` tinyint(1) unsigned NOT NULL DEFAULT '3' COMMENT '重试次数',
  `retry_interval` int(11) unsigned NOT NULL DEFAULT '2' COMMENT '重试间隔秒',
  `verify_ssl` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '0不验证1验证',
  `call_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '累计调用次数',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '0禁用1启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI接入配置';
-- <fen> --
-- 表结构: xphp_prompt --
CREATE TABLE `xphp_prompt` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '模板名称',
  `type` varchar(20) NOT NULL DEFAULT 'page' COMMENT '模板类型：page/expand',
  `direction` varchar(20) NOT NULL DEFAULT '' COMMENT '拓词方向：related/question/longtail/commercial，落地页为空',
  `content` text COMMENT '提示词内容',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0未启用1当前启用',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '通用状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_type_direction` (`type`, `direction`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提示词模板';
-- <fen> --
-- 表结构: xphp_keyword --
CREATE TABLE `xphp_keyword` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `word` varchar(100) NOT NULL DEFAULT '' COMMENT '关键词文本',
  `pinyin` varchar(200) NOT NULL DEFAULT '' COMMENT '拼音',
  `source` varchar(20) NOT NULL DEFAULT 'manual' COMMENT '来源：manual/ai/csv',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '0停用1启用',
  `has_page` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0无页面1已有页面',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_word` (`word`),
  KEY `idx_pinyin` (`pinyin`),
  KEY `idx_status` (`status`),
  KEY `idx_has_page` (`has_page`),
  KEY `idx_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='关键词';
-- <fen> --
-- 表结构: xphp_page --
CREATE TABLE `xphp_page` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `keyword_id` int(11) unsigned DEFAULT NULL COMMENT '关联关键词ID',
  `url_path` varchar(200) DEFAULT NULL COMMENT 'URL路径',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '页面标题',
  `keywords` varchar(500) NOT NULL DEFAULT '' COMMENT 'meta keywords',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT 'meta description',
  `content` mediumtext COMMENT '完整HTML落地页内容',
  `ai_config_id` int(11) unsigned DEFAULT NULL COMMENT '使用的AI配置ID',
  `prompt_id` int(11) unsigned DEFAULT NULL COMMENT '使用的提示词ID',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0草稿1已发布',
  `view_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '访问量',
  `is_pushed` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0未推送1已推送',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_url_path` (`url_path`),
  UNIQUE KEY `uk_keyword_id` (`keyword_id`),
  KEY `idx_status` (`status`),
  KEY `idx_status_is_pushed` (`status`, `is_pushed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='页面';
-- <fen> --
-- 表结构: xphp_task --
CREATE TABLE `xphp_task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '任务名称',
  `type` varchar(30) DEFAULT NULL COMMENT '任务类型（UNIQUE，每种全局仅一条）',
  `cron_desc` varchar(50) NOT NULL DEFAULT '' COMMENT '执行频率描述（仅展示）',
  `last_run_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后执行时间',
  `last_run_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0未执行1成功2失败',
  `last_run_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '最后执行结果消息',
  `total_run` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '累计执行次数',
  `total_fail` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '累计失败次数',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0禁用1启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='定时任务';
-- <fen> --
-- 表结构: xphp_task_log --
CREATE TABLE `xphp_task_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联任务ID',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0执行中1成功2失败',
  `result` text COMMENT '执行结果详情JSON',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `duration` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '耗时毫秒',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_task_id_start_time` (`task_id`, `start_time`),
  KEY `idx_start_time` (`start_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务执行日志';
-- <fen> --
```

### 2.3 新增 `3_insert_xphp_ai_config_part1.sql`

```sql
-- 表数据: xphp_ai_config(1/1) 每页: 1000 --
-- （初始无数据，AI配置通过后台手动添加）
```

### 2.4 新增 `3_insert_xphp_prompt_part1.sql`

落地页模板内容与 Keyword Model `getDefaultPagePrompt()` 一致。拓词模板内容与 `getDefaultExpandPrompt()` 一致。

```sql
-- 表数据: xphp_prompt(1/1) 每页: 1000 --
INSERT INTO `xphp_prompt` (`name`, `type`, `direction`, `content`, `is_active`, `status`, `create_time`, `update_time`) VALUES ('默认落地页模板', 'page', '', '你是一个专业的SEO落地页生成器。请根据以下关键词生成一个完整的、独立的HTML落地页。\n\n关键词：<keyword>{keyword}</keyword>\n网站名称：{site_name}\n网站URL：{site_url}\n日期：{date}\n时间：{time}\n\n要求：\n1. 生成完整的HTML文档结构（DOCTYPE、html、head、body）\n2. 所有CSS样式必须通过<style>标签内联实现，禁止使用外部CSS文件或@import引入外部资源\n3. 页面必须响应式设计，适配移动端和桌面端\n4. 包含完整的SEO meta标签（title、description、keywords）\n5. 使用语义化HTML5标签（header、main、section、article、footer等）\n6. 包含明确的CTA（行动号召）元素\n7. 使用<!--seo:title-->标题内容<!--/seo:title-->、<!--seo:keywords-->关键词内容<!--/seo:keywords-->、<!--seo:description-->描述内容<!--/seo:description-->标注SEO元数据\n8. 禁止生成任何JavaScript代码\n9. 禁止使用任何事件处理器属性（onclick、onload等）\n10. 禁止使用javascript:协议\n11. CSS中禁止使用position:fixed或position:absolute配合z-index覆盖全屏\n12. CSS中禁止使用@import引入外部资源\n13. 内容要丰富、专业、有吸引力，围绕关键词展开\n14. 页面风格要美观大方，配色协调', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());-- <fen> --
INSERT INTO `xphp_prompt` (`name`, `type`, `direction`, `content`, `is_active`, `status`, `create_time`, `update_time`) VALUES ('相关词拓词', 'expand', 'related', '你是一个SEO关键词专家。请根据以下关键词生成10个语义相关的关键词。\n\n关键词：<keyword>{keyword}</keyword>\n\n要求：\n1. 每行一个关键词，不要编号\n2. 关键词要与目标关键词语义相关\n3. 关键词要有实际搜索价值\n4. 不要重复目标关键词本身\n5. 关键词长度不超过100个字符', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());-- <fen> --
INSERT INTO `xphp_prompt` (`name`, `type`, `direction`, `content`, `is_active`, `status`, `create_time`, `update_time`) VALUES ('问答型拓词', 'expand', 'question', '你是一个SEO关键词专家。请根据以下关键词生成10个用户搜索时常用的疑问句式关键词。\n\n关键词：<keyword>{keyword}</keyword>\n\n要求：\n1. 每行一个关键词，不要编号\n2. 以疑问句式为主（如何、怎么、为什么、哪里、什么等）\n3. 关键词要有实际搜索价值\n4. 不要重复目标关键词本身\n5. 关键词长度不超过100个字符', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());-- <fen> --
INSERT INTO `xphp_prompt` (`name`, `type`, `direction`, `content`, `is_active`, `status`, `create_time`, `update_time`) VALUES ('长尾词拓词', 'expand', 'longtail', '你是一个SEO关键词专家。请根据以下关键词生成10个包含目标关键词的长尾组合关键词。\n\n关键词：<keyword>{keyword}</keyword>\n\n要求：\n1. 每行一个关键词，不要编号\n2. 每个关键词必须包含目标关键词\n3. 关键词要有实际搜索价值\n4. 长尾组合要自然、符合搜索习惯\n5. 关键词长度不超过100个字符', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());-- <fen> --
INSERT INTO `xphp_prompt` (`name`, `type`, `direction`, `content`, `is_active`, `status`, `create_time`, `update_time`) VALUES ('商业词拓词', 'expand', 'commercial', '你是一个SEO关键词专家。请根据以下关键词生成10个具有商业/交易意图的关键词。\n\n关键词：<keyword>{keyword}</keyword>\n\n要求：\n1. 每行一个关键词，不要编号\n2. 关键词要体现购买、比价、评测、推荐等商业意图\n3. 关键词要有实际搜索价值\n4. 不要重复目标关键词本身\n5. 关键词长度不超过100个字符', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());-- <fen> --
```

### 2.5 新增 `3_insert_xphp_task_part1.sql`

```sql
-- 表数据: xphp_task(1/1) 每页: 1000 --
INSERT INTO `xphp_task` (`name`, `type`, `cron_desc`, `status`, `create_time`, `update_time`) VALUES ('批量生成页面', 'generate_page', '每天8点', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());-- <fen> --
INSERT INTO `xphp_task` (`name`, `type`, `cron_desc`, `status`, `create_time`, `update_time`) VALUES ('百度普通收录推送', 'push_baidu', '每天10点', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());-- <fen> --
INSERT INTO `xphp_task` (`name`, `type`, `cron_desc`, `status`, `create_time`, `update_time`) VALUES ('百度快速收录推送', 'push_baidu_fast', '每天10点', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());-- <fen> --
INSERT INTO `xphp_task` (`name`, `type`, `cron_desc`, `status`, `create_time`, `update_time`) VALUES ('Sitemap生成', 'sitemap', '每天6点', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());-- <fen> --
INSERT INTO `xphp_task` (`name`, `type`, `cron_desc`, `status`, `create_time`, `update_time`) VALUES ('系统清理', 'clear_cache', '每天0点', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());-- <fen> --
```

### 2.6 修改 `3_insert_xphp_config_part1.sql`

在现有 8 条 config 记录末尾追加 7 条新记录（站点配置2条 + 推送配置3条 + Cron安全配置2条）：

```sql
INSERT INTO `xphp_config` (`name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('网站名称', 'site_name', '', 0, 1);-- <fen> --
INSERT INTO `xphp_config` (`name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('网站URL', 'site_url', '', 0, 1);-- <fen> --
INSERT INTO `xphp_config` (`name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('百度站点域名', 'baidu_site', '', 0, 1);-- <fen> --
INSERT INTO `xphp_config` (`name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('百度普通收录Token', 'baidu_token', '', 0, 1);-- <fen> --
INSERT INTO `xphp_config` (`name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('百度快速收录Token', 'baidu_fast_token', '', 0, 1);-- <fen> --
INSERT INTO `xphp_config` (`name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('Cron安全密钥', 'cron_key', '', 0, 1);-- <fen> --
INSERT INTO `xphp_config` (`name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('Cron允许IP列表', 'cron_allowed_ips', '', 0, 1);-- <fen> --
```

`cron_key` 初始为空，在 `app/admin/controller/Index.php` 的 `index()` 方法中自动检测并生成：

```php
$cronKey = db('config')->where('config_key', 'cron_key')->value('config_value');
if (empty($cronKey)) {
    db('config')->where('config_key', 'cron_key')->update(['config_value' => bin2hex(random_bytes(32))]);
}
```

### 2.7 修改 `3_insert_xphp_menu_part1.sql`

将现有 4 条菜单记录的 `icon` 字段更新为新图标，并追加 4 条 SEO 模块菜单。替换整个文件内容：

```sql
-- 表数据: xphp_menu(1/1) 每页: 1000 --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('1', '0', '用户管理', 'user/index', 'user', 'mdi mdi-account-group', '1', '1070', UNIX_TIMESTAMP(), '1');-- <fen> --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('2', '0', '网站配置', 'config/index', 'config', 'mdi mdi-cog', '1', '1080', UNIX_TIMESTAMP(), '1');-- <fen> --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('3', '0', '菜单管理', 'menu/index', 'menu', 'mdi mdi-menu', '1', '1090', UNIX_TIMESTAMP(), '1');-- <fen> --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('4', '0', '数据备份', 'backup/index', 'backup', 'mdi mdi-database', '1', '1100', UNIX_TIMESTAMP(), '1');-- <fen> --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('5', '0', '关键词管理', 'keyword/index', 'keyword', 'mdi mdi-key-variant', '0', '1010', UNIX_TIMESTAMP(), '1');-- <fen> --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('6', '0', '页面管理', 'page/index', 'page', 'mdi mdi-file-document-outline', '0', '1020', UNIX_TIMESTAMP(), '1');-- <fen> --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('7', '0', 'AI引擎', 'ai_config/index', 'ai_engine', 'mdi mdi-robot-outline', '0', '1030', UNIX_TIMESTAMP(), '1');-- <fen> --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('8', '0', '定时任务', 'task/index', 'task', 'mdi mdi-clock-outline', '0', '1040', UNIX_TIMESTAMP(), '1');-- <fen> --
```

安装完成后执行 `widget_reload('menu')` 刷新侧边栏缓存。

**最终菜单结构**（sort 决定显示顺序，值越小越靠前）：

```
侧边栏
├── 🗝️ 关键词管理   id=5  sort=1010  顶级  href=keyword/index
├── 📄 页面管理     id=6  sort=1020  顶级  href=page/index
├── 🤖 AI引擎       id=7  sort=1030  顶级  href=ai_config/index  (选项卡切换AI配置/提示词模板)
├── 🕐 定时任务     id=8  sort=1040  顶级  href=task/index
├── 👥 用户管理     id=1  sort=1070  顶级  href=user/index        (系统)
├── ⚙️ 网站配置     id=2  sort=1080  顶级  href=config/index      (系统)
├── 📋 菜单管理     id=3  sort=1090  顶级  href=menu/index        (系统)
└── 💾 数据备份     id=4  sort=1100  顶级  href=backup/index      (系统)
```

**表关系**：
```
keyword (1) ──── (0..1) page
page ────→ (0..1) ai_config  （ai_config_id 允许 NULL）
page ────→ (0..1) prompt     （prompt_id 允许 NULL）
task (1) ──── (0..n) task_log （task_id NOT NULL）
```

**关联完整性**：控制器 `del()` 方法检查关联记录，阻止不安全的删除（因 `Model::del()` 为 final 不检查 errors，关联检查须在控制器层执行）。

---

## 三、公共函数

文件：`app/common.php`，在现有函数末尾追加。

### 3.1 ai_chat

```php
function ai_chat(string $prompt, ?array $config = null): array
```

自行封装 cURL（框架 `get_curl()` 不支持 JSON 请求体和 SSL 验证控制）。

**cURL 配置**：`CURLOPT_CONNECTTIMEOUT` = 10，`CURLOPT_TIMEOUT` = 60

**按 api_type 分发**：

| api_type | 认证 | 请求体 | 响应提取 |
|----------|------|--------|----------|
| openai | `Authorization: Bearer {api_key}` | `{model, messages[{role:"user",content}], max_tokens, temperature}` | `choices[0].message.content` |
| anthropic | `x-api-key: {api_key}` + `anthropic-version: 2023-06-01` | `{model, messages[{role:"user",content}], max_tokens, temperature, system:""}` | `content[0].text` |
| ollama | 无 | `{model, messages[{role:"user",content}], stream:false}` | `message.content` |

**重试策略**：
- 可重试：超时(CURLE_OPERATION_TIMEDOUT)、连接失败(CURLE_COULDNT_CONNECT)、HTTP 5xx、HTTP 429
- 不可重试：HTTP 401/403/400/404、cURL SSL 错误(CURLE_SSL_*)——直接返回，不消耗重试次数
- 429 指数退避：从 `retry_interval` 起，每次翻倍，上限 30s
- 其他可重试：固定间隔 `retry_interval`

**调用方式**：
- 不传 `$config`：调用 `model('admin.ai_config')->getActiveConfigs()` 获取所有 status=1 配置（按 ID ASC），从 `cache('ai_config_last_id')` 记录的上次 ID 之后开始轮询（若缓存不存在则从第一个开始），依次尝试每个配置，成功后 `cache('ai_config_last_id', $configId, 86400)` 记录本次使用的 ID，失败自动切换下一个；全部失败则返回最后一个错误
- 传入 `$config`：使用指定配置（api_key 应为已解密的明文，ai_chat 不再调用 decrypt），失败不切换

**返回**：
- 成功：`['ok' => true, 'content' => 'AI生成内容', 'config_id' => 5]`
- 失败：`['ok' => false, 'error' => '错误描述', 'code' => HTTP状态码或cURL错误码]`

**后处理**：
1. 去除 markdown 代码块包裹（仅当整个响应被单个代码块包裹时）：`preg_replace('/^```(?:\w+)?\s*\n(.*?)\n```\s*$/s', '$1', $content)`
2. 调用 `filter_landing_html($content)` 过滤
3. 成功后 `db('ai_config')->where('id', $configId)->setInc('call_count')` 原子递增

**速率限制**：同一 AI 配置 3 秒内不允许重复调用（`cache('ai_rate_' . $configId, 1, 3)`）

**提示注入防护**：Prompt 模板中使用 `<keyword>{keyword}</keyword>` 标签标记关键词边界，系统提示中明确指示 AI 不要执行关键词中的指令。关键词文本长度限制 100 字符。

### 3.2 filter_landing_html

```php
function filter_landing_html(string $html): string
```

框架 `remove_xss()` 会移除 `<style>` 标签且未覆盖 form/svg/math/details，故自行实现。

**处理步骤**：
1. 循环 `html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8')` 直到输出不再变化，消除多重实体编码
2. 移除危险标签及内容：script / iframe / embed / object / form / svg / math / details / applet / meta / link / base
3. 保留 `<style>` 标签，移除其中 `@import` 规则和 `expression()` / `url(javascript:)` 危险 CSS 函数
4. 移除所有 `on*` 事件属性（大小写不敏感）
5. 移除 `javascript:` / `vbscript:` / `data:` 协议
6. 移除 `<img>` 的 `onerror` / `onload` 属性（保留 `<img>` 标签本身）

### 3.3 to_pinyin

```php
function to_pinyin(string $text): string
```

使用 `Transliterator::create('Any-Latin; Latin-ASCII; Lower()')` 转换。中文→拼音，空格替换为连字符 `-`，最终输出小写。Transliterator 创建失败时 fallback：仅保留 a-z0-9，空格转连字符。

### 3.4 baidu_push

```php
function baidu_push(string $type, array $urls): array
```

- `$type`：`'normal'`（普通收录）或 `'fast'`（快速收录）
- 从 `xphp_config` 表读取 `baidu_site` / `baidu_token` / `baidu_fast_token`
- 普通收录：`POST https://data.zz.baidu.com/urls?site={site}&token={token}`
- 快速收录：`POST https://data.zz.baidu.com/urls?site={site}&token={token}&type=daily`
- Body：`implode("\n", $urls)`
- `baidu_site` 校验为合法域名格式（正则 `/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/`，防 SSRF，不允许 IP 地址或内网域名）
- 返回：`['success' => 数量, 'fail' => 数量, 'detail' => API返回详情]`

---

## 四、模型

### 4.1 AiConfig

文件：`app/admin/model/AiConfig.php`（仅后台使用）

```php
<?php
declare(strict_types=1);
namespace app\admin\model;
use xphp\core\Model;

class AiConfig extends Model
{
    protected string $table = 'ai_config';
    protected string $pk = 'id';

    protected array $validate = [
        ['name', 'required', '配置名称必填', FV_MUST, AC_BOTH],
        ['api_type', '/^(openai|anthropic|ollama)$/', '接口类型无效', FV_MUST, AC_BOTH],
        ['api_url', 'required', 'API地址必填', FV_MUST, AC_BOTH],
        ['api_key', 'apiKeyRequired', '非Ollama类型必须填写API密钥', FV_MUST, AC_INSERT],
        ['model', 'required', '模型名称必填', FV_MUST, AC_BOTH],
    ];

    protected array $auto = [
        ['status', '1', 'string', FV_MUST, AC_INSERT],
    ];

    protected array $filter = [
        ['api_key', FV_EMPTY, AC_UPDATE],
    ];

    public function apiKeyRequired(string $val, array $data): bool
    {
        return ($data['api_type'] ?? '') === 'ollama' || !empty($val);
    }

    public function getActiveConfigs(): array
    {
        return db('ai_config')->where('status', 1)->order('id ASC')->select();
    }

    protected function _before_insert(array &$data): void
    {
        if (!empty($data['api_key'])) {
            $data['api_key'] = encrypt($data['api_key']);
        }
    }

    protected function _before_update(array &$data): void
    {
        if (!empty($data['api_key'])) {
            $data['api_key'] = encrypt($data['api_key']);
        }
    }
}
```

引用方式：`model('admin.ai_config')`

### 4.2 Prompt

文件：`app/admin/model/Prompt.php`（仅后台使用）

```php
<?php
declare(strict_types=1);
namespace app\admin\model;
use xphp\core\Model;

class Prompt extends Model
{
    protected string $table = 'prompt';
    protected string $pk = 'id';

    protected array $validate = [
        ['name', 'required', '模板名称必填', FV_MUST, AC_BOTH],
        ['type', '/^(page|expand)$/', '类型无效', FV_MUST, AC_BOTH],
    ];

    protected array $auto = [
        ['status', '1', 'string', FV_MUST, AC_INSERT],
    ];

    public function activate(int $id): bool
    {
        $prompt = db('prompt')->find($id);
        if (!$prompt) {
            $this->errors[] = '提示词不存在';
            return false;
        }
        $r = pdo()->trans(function () use ($prompt, $id) {
            db('prompt')
                ->where('type', $prompt['type'])
                ->where('direction', $prompt['direction'] ?? '')
                ->setField('is_active', 0);
            $res = db('prompt')->where('id', $id)->setField('is_active', 1);
            if (!$res) {
                throw new \Exception('激活失败');
            }
        });
        return (bool)$r;
    }
}
```

引用方式：`model('admin.prompt')`

### 4.3 Keyword

文件：`app/common/model/Keyword.php`（前后台共用）

```php
<?php
declare(strict_types=1);
namespace app\common\model;
use xphp\core\Model;

class Keyword extends Model
{
    protected string $table = 'keyword';
    protected string $pk = 'id';

    protected array $validate = [
        ['word', 'required', '关键词必填', FV_MUST, AC_BOTH],
        ['word', 'unique', '关键词已存在', FV_MUST, AC_BOTH],
        ['source', '/^(manual|ai|csv)$/', '来源无效', FV_VALUE, AC_BOTH],
    ];

    protected array $auto = [
        ['status', '1', 'string', FV_MUST, AC_INSERT],
        ['has_page', '0', 'string', FV_MUST, AC_INSERT],
    ];

    protected function _before_insert(array &$data): void
    {
        if (empty(trim($data['word'] ?? ''))) {
            $this->errors[] = '关键词不能为空';
            return;
        }
        if (empty($data['pinyin'])) {
            $data['pinyin'] = to_pinyin($data['word']);
        }
    }

    public function generatePage(int $keywordId): bool
    {
        $keyword = db('keyword')->find($keywordId);
        if (!$keyword || $keyword['has_page']) {
            $this->errors[] = $keyword ? '该关键词已生成页面' : '关键词不存在';
            return false;
        }

        $promptRow = db('prompt')->where('type', 'page')->where('is_active', 1)->find();
        $promptText = $promptRow ? $promptRow['content'] : $this->getDefaultPagePrompt();
        $promptText = str_replace(
            ['{keyword}', '{site_name}', '{site_url}', '{date}', '{time}'],
            [$keyword['word'], site('site_name'), site('site_url') ?: __HOST__, date('Y-m-d'), date('H:i:s')],
            $promptText
        );

        $result = ai_chat($promptText);
        if (!$result['ok']) {
            $this->errors[] = 'AI生成失败: ' . $result['error'];
            return false;
        }

        $content = $result['content'];
        if (mb_strlen($content) < 500) {
            $this->errors[] = '生成内容过短，可能生成失败';
            return false;
        }

        $seoMeta = $this->parseSeoMeta($content);
        $pinyin = $keyword['pinyin'] ?: to_pinyin($keyword['word']);
        $urlPath = '/keyword/' . $pinyin;
        $urlPath = $this->ensureUniqueUrlPath($urlPath);

        $title = $seoMeta['title'] ?: $keyword['word'];
        $r = pdo()->trans(function () use ($keywordId, $urlPath, $title, $seoMeta, $content, $result, $promptRow) {
            $res = model('common.page')->save([
                'keyword_id' => $keywordId,
                'url_path' => $urlPath,
                'title' => $title,
                'keywords' => $seoMeta['keywords'],
                'description' => $seoMeta['description'],
                'content' => $content,
                'ai_config_id' => $result['config_id'] ?? null,
                'prompt_id' => $promptRow ? $promptRow['id'] : null,
                'status' => 1,
            ]);
            if (!$res) {
                throw new \Exception('页面保存失败');
            }
        });
        if ($r) {
            model('common.task')->execSitemap();
        }
        return (bool)$r;
    }

    public function expandByAi(int $keywordId, string $direction): array
    {
        if (!in_array($direction, ['related', 'question', 'longtail', 'commercial'])) {
            $this->errors[] = '拓词方向无效';
            return [];
        }
        $keyword = db('keyword')->find($keywordId);
        if (!$keyword) {
            $this->errors[] = '关键词不存在';
            return [];
        }
        $promptRow = db('prompt')->where('type', 'expand')->where('direction', $direction)->where('is_active', 1)->find();
        $promptText = $promptRow ? $promptRow['content'] : $this->getDefaultExpandPrompt($direction);
        $promptText = str_replace('{keyword}', $keyword['word'], $promptText);

        $result = ai_chat($promptText);
        if (!$result['ok']) {
            $this->errors[] = 'AI拓词失败: ' . $result['error'];
            return [];
        }

        $lines = array_filter(array_map('trim', explode("\n", $result['content'])));
        $words = [];
        foreach ($lines as $line) {
            $line = preg_replace('/^\d+[.、)\]]\s*/', '', $line);
            $line = trim($line);
            if (!empty($line) && mb_strlen($line) <= 100) {
                $words[] = $line;
            }
        }
        return $words;
    }

    public function parseSeoMeta(string $html): array
    {
        $meta = ['title' => '', 'keywords' => '', 'description' => ''];
        if (preg_match('/<!--seo:title-->(.*?)<!--\/seo:title-->/s', $html, $m)) {
            $meta['title'] = trim($m[1]);
        } elseif (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
            $meta['title'] = trim($m[1]);
        }
        if (preg_match('/<!--seo:keywords-->(.*?)<!--\/seo:keywords-->/s', $html, $m)) {
            $meta['keywords'] = trim($m[1]);
        } elseif (preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\'](.*?)["\']/is', $html, $m)) {
            $meta['keywords'] = trim($m[1]);
        }
        if (preg_match('/<!--seo:description-->(.*?)<!--\/seo:description-->/s', $html, $m)) {
            $meta['description'] = trim($m[1]);
        } elseif (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $html, $m)) {
            $meta['description'] = trim($m[1]);
        } elseif (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $m)) {
            $text = strip_tags($m[1]);
            $meta['description'] = mb_substr(trim(preg_replace('/\s+/', ' ', $text)), 0, 150);
        }
        return $meta;
    }

    private function ensureUniqueUrlPath(string $path): string
    {
        $original = $path;
        $i = 2;
        while (db('page')->where('url_path', $path)->count() > 0) {
            $path = $original . '_' . $i;
            $i++;
        }
        return $path;
    }

    public function getDefaultPagePrompt(): string
    {
        return '你是一个专业的SEO落地页生成器。请根据以下关键词生成一个完整的、独立的HTML落地页。

关键词：<keyword>{keyword}</keyword>
网站名称：{site_name}
网站URL：{site_url}
日期：{date}
时间：{time}

要求：
1. 生成完整的HTML文档结构（DOCTYPE、html、head、body）
2. 所有CSS样式必须通过<style>标签内联实现，禁止使用外部CSS文件或@import引入外部资源
3. 页面必须响应式设计，适配移动端和桌面端
4. 包含完整的SEO meta标签（title、description、keywords）
5. 使用语义化HTML5标签（header、main、section、article、footer等）
6. 包含明确的CTA（行动号召）元素
7. 使用<!--seo:title-->标题内容<!--/seo:title-->、<!--seo:keywords-->关键词内容<!--/seo:keywords-->、<!--seo:description-->描述内容<!--/seo:description-->标注SEO元数据
8. 禁止生成任何JavaScript代码
9. 禁止使用任何事件处理器属性（onclick、onload等）
10. 禁止使用javascript:协议
11. CSS中禁止使用position:fixed或position:absolute配合z-index覆盖全屏
12. CSS中禁止使用@import引入外部资源
13. 内容要丰富、专业、有吸引力，围绕关键词展开
14. 页面风格要美观大方，配色协调';
    }

    public function getDefaultExpandPrompt(string $direction): string
    {
        $prompts = [
            'related' => '请根据以下关键词生成10个语义相关的关键词。关键词：<keyword>{keyword}</keyword>。每行一个，不要编号，不超过100字符。',
            'question' => '请根据以下关键词生成10个用户搜索时常用的疑问句式关键词。关键词：<keyword>{keyword}</keyword>。每行一个，不要编号，以疑问句式为主，不超过100字符。',
            'longtail' => '请根据以下关键词生成10个包含目标关键词的长尾组合关键词。关键词：<keyword>{keyword}</keyword>。每行一个，不要编号，必须包含目标关键词，不超过100字符。',
            'commercial' => '请根据以下关键词生成10个具有商业/交易意图的关键词。关键词：<keyword>{keyword}</keyword>。每行一个，不要编号，体现购买、比价、评测等商业意图，不超过100字符。',
        ];
        return $prompts[$direction] ?? $prompts['related'];
    }
}
```

引用方式：`model('common.keyword')`

### 4.4 Page

文件：`app/common/model/Page.php`（前后台共用）

```php
<?php
declare(strict_types=1);
namespace app\common\model;
use xphp\core\Model;

class Page extends Model
{
    protected string $table = 'page';
    protected string $pk = 'id';

    protected array $validate = [
        ['title', 'required', '标题必填', FV_MUST, AC_BOTH],
    ];

    protected array $auto = [
        ['status', '0', 'string', FV_MUST, AC_INSERT],
    ];

    protected function _after_insert(array $data): void
    {
        $this->_clearViewCache($data['url_path'] ?? '');
        if (!empty($data['keyword_id'])) {
            db('keyword')->where('id', $data['keyword_id'])->setField('has_page', 1);
        }
    }

    protected function _after_update(array $before, array $after): void
    {
        $this->_clearViewCache($after['url_path'] ?? '');
    }

    protected function _before_delete(array $data): void
    {
        if (!empty($data['keyword_id'])) {
            $exists = db('page')->where('keyword_id', $data['keyword_id'])->where('id', '<>', $data['id'])->count();
            if ($exists === 0) {
                db('keyword')->where('id', $data['keyword_id'])->setField('has_page', 0);
            }
        }
    }

    protected function _after_delete(array $data): void
    {
        $this->_clearViewCache($data['url_path'] ?? '');
    }

    public function findByPath(string $path): ?array
    {
        return db('page')->where('url_path', $path)->where('status', 1)->find();
    }

    public function rewriteByAi(int $pageId): bool
    {
        $page = db('page')->find($pageId);
        if (!$page) {
            $this->errors[] = '页面不存在';
            return false;
        }

        $keyword = !empty($page['keyword_id']) ? db('keyword')->find($page['keyword_id']) : null;
        $promptRow = db('prompt')->where('type', 'page')->where('is_active', 1)->find();
        $promptText = $promptRow ? $promptRow['content'] : model('common.keyword')->getDefaultPagePrompt();
        $promptText = str_replace(
            ['{keyword}', '{site_name}', '{site_url}', '{date}', '{time}'],
            [$keyword ? $keyword['word'] : $page['title'], site('site_name'), site('site_url') ?: __HOST__, date('Y-m-d'), date('H:i:s')],
            $promptText
        );
        $promptText .= "\n\n请重新生成一个不同风格的版本。";

        $result = ai_chat($promptText);
        if (!$result['ok']) {
            $this->errors[] = 'AI重写失败: ' . $result['error'];
            return false;
        }

        $content = $result['content'];
        if (mb_strlen($content) < 500) {
            $this->errors[] = '生成内容过短，可能生成失败';
            return false;
        }

        $seoMeta = model('common.keyword')->parseSeoMeta($content);
        $pageModel = model('common.page')->find($pageId);
        if ($pageModel) {
            $pageModel->save([
                'title' => $seoMeta['title'] ?: $page['title'],
                'keywords' => $seoMeta['keywords'],
                'description' => $seoMeta['description'],
                'content' => $content,
                'ai_config_id' => $result['config_id'] ?? null,
                'prompt_id' => $promptRow ? $promptRow['id'] : null,
                'status' => 0,
                'is_pushed' => 0,
            ]);
        }
        $this->_clearViewCache($page['url_path']);
        return true;
    }

    public function _clearViewCache(string $urlPath): void
    {
        if (!empty($urlPath)) {
            $path = ltrim($urlPath, '/');
            cache('view/' . md5('index/site/dispatch?path=' . $path), null);
        }
        cache('index_pages', null);
    }
}
```

引用方式：`model('common.page')`

`_clearViewCache` 为 public，Page 控制器 `state()` 方法需调用。缓存键格式需与框架 View 缓存机制一致。

### 4.5 Task

文件：`app/common/model/Task.php`（前后台共用）

```php
<?php
declare(strict_types=1);
namespace app\common\model;
use xphp\core\Model;

class Task extends Model
{
    protected string $table = 'task';
    protected string $pk = 'id';

    protected array $validate = [
        ['name', 'required', '任务名称必填', FV_MUST, AC_BOTH],
        ['type', 'required', '任务类型必填', FV_MUST, AC_INSERT],
        ['type', 'unique', '任务类型已存在', FV_MUST, AC_INSERT],
    ];

    protected array $auto = [
        ['status', '0', 'string', FV_MUST, AC_INSERT],
    ];

    public function execute(int $taskId): bool
    {
        $task = db('task')->find($taskId);
        if (!$task) return false;

        $runningLog = db('task_log')->where('task_id', $taskId)->where('status', 0)->count();
        if ($runningLog > 0) return false;

        $startTime = time();
        $logId = db('task_log')->insertGetId([
            'task_id' => $taskId,
            'status' => 0,
            'start_time' => $startTime,
        ]);

        try {
            set_time_limit(300);
            $method = 'exec' . str_replace(' ', '', ucwords(str_replace('_', ' ', $task['type'])));
            if (!method_exists($this, $method)) {
                throw new \Exception("任务方法{$method}不存在");
            }
            $result = $this->$method();
            $duration = (int)((microtime(true) - $startTime) * 1000);

            db('task_log')->where('id', $logId)->update([
                'status' => 1,
                'end_time' => time(),
                'duration' => $duration,
                'result' => is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE),
            ]);
            db('task')->where('id', $taskId)->update([
                'last_run_time' => time(),
                'last_run_status' => 1,
                'last_run_msg' => '执行成功',
            ]);
            db('task')->where('id', $taskId)->setInc('total_run');
            return true;
        } catch (\Throwable $e) {
            $duration = (int)((microtime(true) - $startTime) * 1000);
            db('task_log')->where('id', $logId)->update([
                'status' => 2,
                'end_time' => time(),
                'duration' => $duration,
                'result' => $e->getMessage(),
            ]);
            db('task')->where('id', $taskId)->update([
                'last_run_time' => time(),
                'last_run_status' => 2,
                'last_run_msg' => $e->getMessage(),
            ]);
            db('task')->where('id', $taskId)->setInc('total_run');
            db('task')->where('id', $taskId)->setInc('total_fail');
            return false;
        }
    }

    public function recoverTimeout(): void
    {
        $timeoutLogs = db('task_log')
            ->where('status', 0)
            ->where('start_time', '<', time() - 600)
            ->select();
        foreach ($timeoutLogs as $log) {
            db('task_log')->where('id', $log['id'])->update([
                'status' => 2,
                'end_time' => time(),
                'result' => '执行超时，可能因进程崩溃未正常结束',
            ]);
            db('task')->where('id', $log['task_id'])->update([
                'last_run_status' => 2,
                'last_run_msg' => '执行超时',
            ]);
            db('task')->where('id', $log['task_id'])->setInc('total_fail');
        }
    }

    public function execGeneratePage(): string
    {
        $keywords = db('keyword')->where('has_page', 0)->where('status', 1)->limit(3)->select();
        $count = 0;
        $fail = 0;
        foreach ($keywords as $kw) {
            $r = model('common.keyword')->generatePage($kw['id']);
            $r ? $count++ : $fail++;
        }
        return "生成{$count}个页面，失败{$fail}个";
    }

    public function execPushBaidu(): string
    {
        $pages = db('page')->where('status', 1)->where('is_pushed', 0)->select();
        if (empty($pages)) return '无待推送页面';
        $urls = array_map(fn($p) => __HOST__ . $p['url_path'] . '.html', $pages);
        $result = baidu_push('normal', $urls);
        if (($result['success'] ?? 0) > 0) {
            db('page')->where('status', 1)->where('is_pushed', 0)->setField('is_pushed', 1);
        }
        return "推送" . count($pages) . "个页面，成功{$result['success']}个";
    }

    public function execPushBaiduFast(): string
    {
        $pages = db('page')->where('status', 1)->where('is_pushed', 0)->select();
        if (empty($pages)) return '无待推送页面';
        $urls = array_map(fn($p) => __HOST__ . $p['url_path'] . '.html', $pages);
        $result = baidu_push('fast', $urls);
        if (($result['success'] ?? 0) > 0) {
            db('page')->where('status', 1)->where('is_pushed', 0)->setField('is_pushed', 1);
        }
        return "快速推送" . count($pages) . "个页面，成功{$result['success']}个";
    }

    public function execSitemap(): string
    {
        $pages = db('page')->where('status', 1)->order('update_time DESC')->field('url_path,update_time')->select();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= "  <url>\n    <loc>" . __HOST__ . "/</loc>\n    <changefreq>daily</changefreq>\n    <priority>1.0</priority>\n  </url>\n";
        foreach ($pages as $page) {
            if (empty($page['url_path']) || !preg_match('/^\/[a-zA-Z0-9\x7f-\xff\-_\/]+$/', $page['url_path'])) continue;
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . __HOST__ . $page['url_path'] . ".html</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d', $page['update_time']) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        $tmpFile = PUBLIC_PATH . 'sitemap.xml.tmp';
        file_put_contents($tmpFile, $xml);
        rename($tmpFile, PUBLIC_PATH . 'sitemap.xml');

        $robotsContent = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /cron/\n\nSitemap: " . __HOST__ . "/sitemap.xml\n";
        file_put_contents(PUBLIC_PATH . 'robots.txt', $robotsContent);

        return "生成sitemap，共" . count($pages) . "条";
    }

    public function execClearCache(): string
    {
        cache_clear();
        $expire = time() - 86400 * 30;
        db('task_log')->where('create_time', '<', $expire)->delete();

        $keywords = db('keyword')->where('status', 1)->select();
        $fixed = 0;
        foreach ($keywords as $kw) {
            $hasPage = db('page')->where('keyword_id', $kw['id'])->count();
            $expected = $hasPage > 0 ? 1 : 0;
            if ($kw['has_page'] != $expected) {
                db('keyword')->where('id', $kw['id'])->setField('has_page', $expected);
                $fixed++;
            }
        }
        return "缓存已清理，修复{$fixed}条has_page不一致";
    }
}
```

引用方式：`model('common.task')`

### 4.6 TaskLog

文件：`app/common/model/TaskLog.php`（前后台共用）

```php
<?php
declare(strict_types=1);
namespace app\common\model;
use xphp\core\Model;

class TaskLog extends Model
{
    protected string $table = 'task_log';
    protected string $pk = 'id';
}
```

引用方式：`model('common.task_log')`

---

## 五、后台控制器

### 5.1 AiConfig

文件：`app/admin/controller/AiConfig.php`（extends Cp）

AI引擎菜单的入口控制器，同时管理 AI配置 和 提示词模板（选项卡切换）。

```php
<?php
declare(strict_types=1);
namespace app\admin\controller;

class AiConfig extends Cp
{
    protected string $model = 'admin.ai_config';

    public function index()
    {
        $tab = input('tab', 'config', 'clear_html');
        if ($tab === 'prompt') {
            return $this->promptIndex();
        }
        return parent::index();
    }

    public function test()
    {
        $id = input('id', 0, 'intval');
        if (!$id) {
            $this->_json(400, '参数错误');
        }
        $lockKey = 'ai_test_' . $id;
        if (cache('?' . $lockKey)) {
            $this->_json(429, '请30秒后再试');
        }
        cache($lockKey, 1, 30);

        $config = db('ai_config')->find($id);
        if (!$config) {
            $this->_json(400, '配置不存在');
        }
        if (!empty($config['api_key'])) {
            $config['api_key'] = decrypt($config['api_key']);
        }
        $config['max_tokens'] = 5;
        $startTime = microtime(true);
        $result = ai_chat('Hi', $config);
        $time = round((microtime(true) - $startTime) * 1000);

        if ($result['ok']) {
            $this->_json(200, '连接成功，响应时间: ' . $time . 'ms', ['time' => $time]);
        } else {
            $this->_json(400, '连接失败: ' . $result['error']);
        }
    }

    public function promptSave()
    {
        $id = input('id', 0, 'intval');
        $data = [
            'name' => input('name', '', 'clear_html'),
            'type' => input('type', 'page', 'clear_html'),
            'direction' => input('direction', '', 'clear_html'),
            'content' => input('content', '', ''),
            'status' => input('status', 1, 'intval'),
        ];
        if ($id) {
            $prompt = model('admin.prompt')->find($id);
            if (!$prompt) {
                $this->_jump([null, '提示词不存在'], false, url('index') . '?tab=prompt');
            }
            $r = $prompt->save($data);
        } else {
            $r = model('admin.prompt')->save($data);
        }
        $this->_jump(['保存成功', model('admin.prompt')->errors ? current(model('admin.prompt')->errors) : '保存失败'], $r, url('index') . '?tab=prompt');
    }

    public function promptActivate()
    {
        $id = input('id', 0, 'intval');
        if (!$id) {
            $this->_json(400, '参数错误');
        }
        $r = model('admin.prompt')->activate($id);
        $this->_json($r ? 200 : 400, $r ? '激活成功' : (model('admin.prompt')->errors ? current(model('admin.prompt')->errors) : '激活失败'));
    }

    public function promptDel()
    {
        $ids = input('ids', '', 'clear_html');
        $idArr = ids_filter($ids, true);
        if (empty($idArr)) {
            $this->_json(400, '请选择要删除的记录');
        }
        $count = 0;
        foreach ($idArr as $id) {
            $r = model('admin.prompt')->find($id);
            if ($r) {
                $r->del();
                $count++;
            }
        }
        $this->_json(200, "删除{$count}条记录");
    }

    private function promptIndex()
    {
        $type = input('type', '', 'clear_html');
        $where = [];
        if (!empty($type)) {
            $where['type'] = $type;
        }
        $list = db('prompt')->where($where)->order('id ASC')->paginate(20);
        return view('ai_config/prompt')->with('list', $list)->with('tab', 'prompt');
    }
}
```

**说明**：
- `index()` 根据 `tab` 参数分发：默认显示 AI配置列表，`tab=prompt` 显示提示词模板列表
- 提示词增删改激活通过 `promptSave()`/`promptDel()`/`promptActivate()` 处理
- 提示词列表使用独立视图 `ai_config/prompt.html`，与 AI配置列表共享布局和选项卡

### 5.2 Keyword

文件：`app/admin/controller/Keyword.php`（extends Cp）

```php
<?php
declare(strict_types=1);
namespace app\admin\controller;

class Keyword extends Cp
{
    protected string $model = 'common.keyword';

    public function del(string $ids)
    {
        $ids = ids_filter($ids, true);
        if (!$ids) $this->error('请选择ID');
        $count = 0;
        foreach ($ids as $id) {
            $hasPage = db('page')->where('keyword_id', $id)->count();
            if ($hasPage > 0) continue;
            $tmp = model($this->model)->where('status', 0)->find($id);
            if ($tmp) {
                $ok = pdo()->trans(function () use ($tmp) {
                    $res = $tmp->del();
                    if (!$res) {
                        throw new \Exception('删除失败');
                    }
                });
                if ($ok) $count++;
            }
        }
        $this->_jump(['删除成功', '删除失败，已生成页面或未停用'], $count, $this->jumpUrl);
    }

    protected function _where(): array
    {
        $where = [];
        $word = input('word', '', 'clear_html');
        if (!empty($word)) {
            $where[] = ['word', 'like', '%' . $word . '%'];
        }
        $source = input('source', '', 'clear_html');
        if (!empty($source)) {
            $where['source'] = $source;
        }
        $hasPage = input('has_page', -1, 'intval');
        if ($hasPage >= 0) {
            $where['has_page'] = $hasPage;
        }
        return $where;
    }

    public function expand()
    {
        $id = input('id', 0, 'intval');
        $direction = input('direction', '', 'clear_html');
        if (!in_array($direction, ['related', 'question', 'longtail', 'commercial'])) {
            $this->_json(400, '拓词方向无效');
        }
        $words = model($this->model)->expandByAi($id, $direction);
        if (empty($words) && !empty(model($this->model)->errors)) {
            $this->_json(400, current(model($this->model)->errors));
        }
        $existing = db('keyword')->column('word');
        $existingLower = array_map('mb_strtolower', $existing);
        $result = [];
        foreach ($words as $w) {
            $result[] = ['word' => $w, 'exists' => in_array(mb_strtolower(trim($w)), $existingLower)];
        }
        $this->_json(200, 'ok', $result);
    }

    public function expandSave()
    {
        $words = input('words/a', []);
        $count = 0;
        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word) || mb_strlen($word) > 100) continue;
            $exists = db('keyword')->where('word', $word)->count();
            if ($exists) continue;
            $r = model($this->model)->save(['word' => $word, 'source' => 'ai']);
            if ($r) $count++;
        }
        $this->_json(200, "成功导入{$count}个关键词");
    }

    public function import()
    {
        if ($this->isPost()) {
            $file = input('csv_file', '', 'clear_html');
            if (empty($file) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
                $this->_json(400, '文件名无效');
            }
            $filePath = PUBLIC_PATH . 'uploads/' . $file;
            if (!is_file($filePath)) {
                $this->_json(400, '文件不存在');
            }
            if (filesize($filePath) > 2 * 1024 * 1024) {
                $this->_json(400, '文件大小不能超过2MB');
            }
            $handle = fopen($filePath, 'r');
            $count = 0;
            $line = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if ($line > 1000) break;
                $word = trim($row[0] ?? '');
                if (empty($word)) continue;
                $exists = db('keyword')->where('word', $word)->count();
                if ($exists) continue;
                $r = model($this->model)->save(['word' => $word, 'source' => 'csv']);
                if ($r) $count++;
            }
            fclose($handle);
            $this->_json(200, "成功导入{$count}个关键词");
        }
        return view();
    }

    public function export()
    {
        $list = db('keyword')->order('id DESC')->select();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=keywords_' . date('YmdHis') . '.csv');
        $fp = fopen('php://output', 'w');
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, ['ID', '关键词', '拼音', '来源', '状态', '是否生成页面']);
        foreach ($list as $row) {
            fputcsv($fp, [$row['id'], $row['word'], $row['pinyin'], $row['source'], $row['status'], $row['has_page']]);
        }
        fclose($fp);
        exit;
    }

    public function generate()
    {
        $ids = input('ids/a', []);
        if (empty($ids)) {
            $this->_json(400, '请选择关键词');
        }
        $lockFile = RUNTIME_PATH . 'seo_generate.lock';
        $fp = fopen($lockFile, 'w+');
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            $this->_json(409, '生成任务进行中，请稍后');
        }

        $batchId = bin2hex(random_bytes(16));
        $keywords = db('keyword')->where('id', 'in', $ids)->where('has_page', 0)->where('status', 1)->column('id');
        cache('seo_generate_queue_' . $batchId, $keywords, 1800);
        cache('seo_generate_progress_' . $batchId, ['total' => count($keywords), 'done' => 0, 'failed' => 0, 'status' => 'running'], 1800);

        try {
            foreach ($keywords as $kid) {
                $r = model($this->model)->generatePage((int)$kid);
                $progress = cache('seo_generate_progress_' . $batchId);
                if ($r) {
                    $progress['done']++;
                } else {
                    $progress['failed']++;
                }
                $progress['status'] = ($progress['done'] + $progress['failed'] >= $progress['total']) ? 'completed' : 'running';
                cache('seo_generate_progress_' . $batchId, $progress, 1800);
            }
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        $this->_json(200, '生成完成', ['batch_id' => $batchId]);
    }

    public function progress()
    {
        $batchId = input('batch_id', '', 'clear_html');
        if (empty($batchId)) {
            $this->_json(400, '参数错误');
        }
        $progress = cache('seo_generate_progress_' . $batchId);
        if (!$progress) {
            $this->_json(400, '任务不存在');
        }
        $this->_json(200, 'ok', $progress);
    }
}
```

### 5.3 Page

文件：`app/admin/controller/Page.php`（extends Cp）

```php
<?php
declare(strict_types=1);
namespace app\admin\controller;

class Page extends Cp
{
    protected string $model = 'common.page';
    protected array $stateList = ['status' => ['草稿', '已发布']];

    protected function _where(): array
    {
        $where = [];
        $keyword = input('keyword', '', 'clear_html');
        if (!empty($keyword)) {
            $where[] = ['title', 'like', '%' . $keyword . '%'];
        }
        $status = input('status', -1, 'intval');
        if ($status >= 0) {
            $where['page.status'] = $status;
        }
        return $where;
    }

    public function edit(int $id, array $req)
    {
        $page = db('page')->find($id);
        if (!$page) $this->error('页面不存在');
        if ($this->isPost()) {
            if ($page['status'] == 1 && isset($req['url_path']) && $req['url_path'] !== $page['url_path']) {
                $this->_jump([null, '已发布页面不可修改URL路径'], false, $this->jumpUrl);
            }
            $r = model($this->model)->find($id)->save($req);
            $this->_jump(['修改成功', model($this->model)->errors ? current(model($this->model)->errors) : '修改失败'], $r, $this->jumpUrl);
        }
        return view()->with('vo', $page);
    }

    public function index()
    {
        $where = $this->_where();
        $list = db('page')
            ->alias('p')
            ->join('keyword k', 'p.keyword_id=k.id', 'LEFT')
            ->field('p.*,k.word as keyword_word')
            ->where($where)
            ->order('p.id DESC')
            ->paginate($this->limit);
        return view()->with('list', $list);
    }

    public function state(string $ids, string $params)
    {
        $idArr = ids_filter($ids, true);
        foreach ($idArr as $id) {
            $page = db('page')->find($id);
            if ($page) {
                model('common.page')->_clearViewCache($page['url_path']);
            }
        }
        parent::state($ids, $params);
        [$field, $value] = name_parse($params, 'status', '-');
        if ($field === 'status' && $value == 1) {
            db('page')->where('id', 'in', $idArr)->update(['is_pushed' => 0]);
        }
    }

    public function rewrite()
    {
        $id = input('id', 0, 'intval');
        if (!$id) {
            $this->_json(400, '参数错误');
        }
        $r = model($this->model)->rewriteByAi($id);
        if ($this->isAjax()) {
            $this->_json($r ? 200 : 400, $r ? '重写成功，状态已改为草稿' : (model($this->model)->errors ? current(model($this->model)->errors) : '重写失败'));
        }
        $this->_jump(['重写成功', model($this->model)->errors ? current(model($this->model)->errors) : '重写失败'], $r, $this->jumpUrl);
    }
}
```

### 5.4 Task

文件：`app/admin/controller/Task.php`（**不继承 Cp，use Jump**）

```php
<?php
declare(strict_types=1);
namespace app\admin\controller;
use xphp\core\Jump;

class Task
{
    use Jump;

    protected array $middleware = [
        'cp_auth' => ['except' => []],
    ];

    protected string $jumpUrl = 'index';

    public function index()
    {
        $list = db('task')->order('id ASC')->select();
        return view()->with('list', $list);
    }

    public function add(array $req)
    {
        if ($this->isPost()) {
            $r = pdo()->trans(function () use ($req) {
                $res = model('common.task')->save($req);
                if (!$res) {
                    throw new \Exception('保存失败');
                }
            });
            $this->_jump(['添加成功', '添加失败'], $r, $this->jumpUrl);
        }
        return view();
    }

    public function edit(int $id, array $req)
    {
        $task = db('task')->find($id);
        if (!$task) $this->error('任务不存在');
        if ($this->isPost()) {
            unset($req['type']);
            $r = pdo()->trans(function () use ($id, $req) {
                $res = model('common.task')->find($id)->save($req);
                if (!$res) {
                    throw new \Exception('修改失败');
                }
            });
            $this->_jump(['修改成功', '修改失败'], $r, $this->jumpUrl);
        }
        return view()->with('vo', $task);
    }

    public function del(string $ids)
    {
        $ids = ids_filter($ids, true);
        if (!$ids) $this->error('请选择ID');
        $count = 0;
        foreach ($ids as $id) {
            $hasLogs = db('task_log')->where('task_id', $id)->count();
            if ($hasLogs > 0) continue;
            $r = model('common.task')->find($id);
            if ($r) {
                $ok = pdo()->trans(function () use ($r) {
                    $res = $r->del();
                    if (!$res) {
                        throw new \Exception('删除失败');
                    }
                });
                if ($ok) $count++;
            }
        }
        $this->_jump(['删除成功', '删除失败，存在执行日志或未停用'], $count, $this->jumpUrl);
    }

    public function state(string $ids, string $params)
    {
        $ids = ids_filter($ids, true);
        if (empty($ids)) $this->error('请选择ID');
        [$field, $value] = name_parse($params, 'status', '-');
        $map = [[$field, '<>', $value]];
        if (count($ids) == 1) {
            $map['id'] = current($ids);
        } else {
            $map[] = ['id', 'in', $ids];
        }
        $r = db('task')->where($map)->setField($field, $value);
        $this->_jump(['操作成功', '操作失败'], $r, $this->jumpUrl);
    }

    public function run()
    {
        $id = input('id', 0, 'intval');
        if (!$id) $this->_json(400, '参数错误');
        $r = model('common.task')->execute($id);
        $this->_json($r ? 200 : 400, $r ? '执行成功' : '执行失败');
    }

    public function logs()
    {
        $taskId = input('task_id', 0, 'intval');
        $where = [];
        if ($taskId) $where['task_id'] = $taskId;
        $list = db('task_log')->where($where)->order('id DESC')->paginate(20);
        return view()->with('list', $list);
    }
}
```

---

## 六、前台控制器

### 6.1 Site

文件：`app/index/controller/Site.php`（use Jump）

```php
<?php
declare(strict_types=1);
namespace app\index\controller;
use xphp\core\Jump;

class Site
{
    use Jump;

    public function dispatch(string $path = '')
    {
        if (empty($path) || mb_strlen($path) > 200) {
            halt('页面不存在', 404);
        }
        $page = model('common.page')->findByPath($path);
        if (!$page) {
            halt('页面不存在', 404);
        }
        db('page')->where('id', $page['id'])->setInc('view_count');
        header("Content-Security-Policy: default-src 'self'; style-src 'unsafe-inline'; script-src 'none'; img-src 'self' data: https:; font-src 'self' https:");
        return view()->with('content', $page['content'])->cache(3600);
    }
}
```

配套模板 `template/default/site/dispatch.html`，仅 `{$content|raw}`，不使用布局包裹。

### 6.2 Cron

文件：`app/index/controller/Cron.php`（use Jump）

```php
<?php
declare(strict_types=1);
namespace app\index\controller;
use xphp\core\Jump;

class Cron
{
    use Jump;

    protected bool $isApi = true;

    public function run(string $key = '')
    {
        $cronKey = db('config')->where('config_key', 'cron_key')->value('config_value');
        if (empty($key) || $key !== $cronKey) {
            $this->_json(403, '密钥无效');
        }

        $allowedIps = db('config')->where('config_key', 'cron_allowed_ips')->value('config_value');
        if (!empty($allowedIps)) {
            $ipList = array_map('trim', explode(',', $allowedIps));
            if (!in_array(get_ip(), $ipList)) {
                $this->_json(403, 'IP不允许');
            }
        }

        $rateKey = 'cron_rate';
        if (cache('?' . $rateKey)) {
            $this->_json(429, '请求过于频繁');
        }
        cache($rateKey, 1, 60);

        $lockFile = RUNTIME_PATH . 'seo_cron.lock';
        $fp = fopen($lockFile, 'w+');
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            $this->_json(409, '任务执行中');
        }

        try {
            set_time_limit(300);
            model('common.task')->recoverTimeout();
            $tasks = db('task')->where('status', 1)->select();
            foreach ($tasks as $task) {
                model('common.task')->execute($task['id']);
            }
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        $this->_json(200, '执行完成');
    }
}
```

---

## 七、CLI 命令

文件：`app/index/command/Cron.php`

```php
<?php
declare(strict_types=1);
namespace app\index\command;
use xphp\cli\Command;

class Cron extends Command
{
    public function cli(): bool
    {
        if (php_sapi_name() !== 'cli') {
            return $this->error('仅限CLI执行');
        }

        $lockFile = RUNTIME_PATH . 'seo_cron.lock';
        $fp = fopen($lockFile, 'w+');
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return $this->error('任务执行中，请稍后重试');
        }

        try {
            set_time_limit(300);
            model('common.task')->recoverTimeout();
            $tasks = db('task')->where('status', 1)->select();
            foreach ($tasks as $task) {
                model('common.task')->execute($task['id']);
            }
            $this->success('Cron执行完成');
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return true;
    }
}
```

执行方式：`php xphpcli index.cron`

crontab：`* * * * * cd /path/to/project && php xphpcli index.cron >> /dev/null 2>&1`

CLI 与 Web Cron 共享同一个 flock 锁文件（`RUNTIME_PATH/seo_cron.lock`），互斥执行。

---

## 八、路由

文件：`route/index.php`

```php
<?php
return [
    'keyword/:keyword' => 'site/dispatch/path/$1',
    'cron/:string' => 'cron/run/key/$1',
];
```

| 路由 | 参数别名 | 正则 | 说明 |
|------|----------|------|------|
| `keyword/:keyword` | `:keyword` | `[a-zA-Z\x7f-\xff0-9-%\+]+` | `config/route.php` rule_alias 已定义，支持中文 |
| `cron/:string` | `:string` | `[a-zA-Z0-9\-_]+` | 限制密钥格式为字母数字 |

框架 `url_clear_suffix => ['.html']` 自动去除后缀，`/keyword/abc.html` 和 `/keyword/abc` 均可匹配。

---

## 九、视图模板

8 个新增模板，遵循项目现有模板语法（`{foreach}`、`{if condition:}`、`{literal}` 包裹 JS、`{layout}` 布局继承）。

### 9.1 后台视图（7个）

| 文件 | 说明 |
|------|------|
| `app/admin/view/ai_config/index.html` | AI引擎入口页：顶部选项卡（AI配置/提示词模板）+ AI配置列表(统计卡片3列+列表+Modal表单+厂商下拉自动填充+测试连接) |
| `app/admin/view/ai_config/prompt.html` | 提示词模板：与index.html共享侧边栏和头部，顶部选项卡（AI配置/提示词模板，当前高亮提示词）+ 类型筛选 + 列表(名称/类型badge/方向badge/激活状态高亮) + Modal表单 + 激活按钮(二次确认) |
| `app/admin/view/keyword/index.html` | 关键词管理：搜索(关键词/来源/有无页面) + 工具栏(添加/AI拓词/导入CSV/导出CSV/批量生成) + AI拓词弹窗(选方向→生成预览→勾选保存) + 批量生成进度条(3秒轮询) |
| `app/admin/view/page/index.html` | 页面管理：列表(关键词/URL/标题/状态/访问量/推送状态/操作) + 新增Modal + AI重写(loading状态) |
| `app/admin/view/page/edit.html` | 页面编辑(独立页面)：上方表单 + 下方textarea代码编辑(行号样式,等宽字体)；已发布url_path只读 |
| `app/admin/view/task/index.html` | 定时任务：列表(名称/类型badge/频率/状态/最后执行/操作) + Modal表单(5种类型) + 手动执行 + 日志链接 |
| `app/admin/view/task/logs.html` | 任务日志：列表(task_id/状态/结果JSON/时间/耗时)；失败项红色标记 |

**选项卡实现**：`ai_config/index.html` 和 `ai_config/prompt.html` 顶部均包含 Bootstrap Nav Tabs，AI配置选项卡链接 `{:url('index')}`，提示词模板选项卡链接 `{:url('index')}?tab=prompt`，当前页高亮 `active` 类。

**厂商预设数据**（JS 硬编码在 ai_config/index.html 的 `{literal}` 块中）：

| 标识 | 名称 | api_type | api_url | 默认模型 | verify_ssl |
|------|------|----------|---------|----------|------------|
| openai | OpenAI | openai | https://api.openai.com/v1/chat/completions | gpt-4o-mini | 1 |
| deepseek | DeepSeek | openai | https://api.deepseek.com/chat/completions | deepseek-chat | 1 |
| qwen | 通义千问 | openai | https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions | qwen-turbo | 1 |
| baidu | 百度千帆 | openai | https://qianfan.baidubce.com/v2/chat/completions | ernie-4.0-8k | 1 |
| xiaomi | 小米MiMo | openai | https://api.xiaomimimo.com/v1/chat/completions | mimo-v2-flash | 1 |
| siliconflow | 硅基流动 | openai | https://api.siliconflow.cn/v1/chat/completions | Qwen/Qwen3-32B | 1 |
| openrouter | OpenRouter | openai | https://openrouter.ai/api/v1/chat/completions | openai/gpt-4o-mini | 1 |
| together | Together AI | openai | https://api.together.xyz/v1/chat/completions | meta-llama/Meta-Llama-3.1-8B-Instruct-Turbo | 1 |
| groq | Groq | openai | https://api.groq.com/openai/v1/chat/completions | llama-3.1-8b-instant | 1 |
| anthropic | Anthropic | anthropic | https://api.anthropic.com/v1/messages | claude-sonnet-4-5 | 1 |
| ollama | Ollama本地 | ollama | http://localhost:11434/api/chat | qwen3:8b | 0 |

百度千帆 API Key 格式为 `bce-v3/{AK}/{SK}`。Ollama 厂商预设 `verify_ssl=0`。

### 9.2 前台视图（1个）

| 文件 | 说明 |
|------|------|
| `template/default/site/dispatch.html` | 仅 `{$content|raw}`，不使用布局包裹 |

---

## 十、修改现有文件

### 10.1 `app/common.php`

追加 4 个函数：`ai_chat()`、`filter_landing_html()`、`to_pinyin()`、`baidu_push()`（详见第三节）

### 10.2 `route/index.php`

替换返回数组（详见第八节）

### 10.3 `app/index/controller/Index.php`

```php
<?php
declare(strict_types=1);
namespace app\index\controller;
class Index
{
    public function index()
    {
        $pages = cache_make('index_pages', function() {
            return db('page')->where('status', 1)->field('id,title,description,create_time')->order('id DESC')->limit(10)->select();
        }, 3600);
        return view()->with('pages', $pages);
    }
}
```

### 10.4 `template/default/index/index.html`

在公告栏下方追加卡片式页面列表（标题+摘要+时间，最多10条，不分页）。页面发布/下线/删除时清除 `cache('index_pages', null)`。

### 10.5 `app/admin/controller/Index.php`

在现有 `index()` 方法中追加 SEO 统计数据（`cache_make('seo_stats', ..., 300)`），保留原系统信息。新增私有方法 `getPageTrend()`、`getKeywordSource()`、`getPageStatus()` 供 Chart.js 使用。

### 10.6 `app/admin/view/index/index.html`

在现有内容基础上追加：4列统计卡片（关键词总数/已生成页面/页面总访问/AI调用次数）+ 3个 Chart.js 图表（7天页面生成趋势折线图/关键词来源饼图/页面状态环形图）。系统信息改为底部折叠面板（Bootstrap Collapse），默认收起。JS 初始化代码使用 `{literal}` 包裹。

---

## 十一、安全约束

| # | 约束 | 实现位置 |
|---|------|----------|
| 1 | API Key 加密存储（`encrypt()`/`decrypt()`），编辑不回显完整密钥 | AiConfig Model `_before_insert`/`_before_update` |
| 2 | 前台只查 status=1，草稿不可访问 | Site Controller `dispatch()` |
| 3 | `filter_landing_html()` 纵深防御（不使用 `remove_xss()`），CSP 头第三道防线，提示词第一道防线 | `app/common.php` + Site Controller |
| 4 | SSL 验证默认开启（Ollama 除外） | AiConfig `verify_ssl` 默认值1 |
| 5 | 批量生成单次最多3个，flock 互斥 | Task Model `execGeneratePage()` + Keyword Controller `generate()` |
| 6 | direction 白名单校验 | Keyword Controller `expand()` |
| 7 | api_key 新增必填/更新空值移除 | AiConfig Model `$validate` + `$filter` |
| 8 | Cron 密钥验证 + 60秒限频 + flock锁 + IP白名单 | Cron Controller `run()` |
| 9 | 百度 Token 存数据库 + HTTPS + baidu_site 校验域名防 SSRF | `baidu_push()` |
| 10 | 任务超时 300 秒 | `set_time_limit(300)` |
| 11 | 日志 30 天自动清理 | Task Model `execClearCache()` |
| 12 | AI 轮询容错 | `ai_chat()` |
| 13 | 路由参数校验（path 非空 + ≤200，密钥字母数字） | Site Controller + `:string` 路由规则 |
| 14 | `setInc` 原子递增 | `ai_chat()` |
| 15 | 事务包裹 page + has_page，闭包内检查返回值失败抛异常 | Keyword Model `generatePage()` |
| 16 | 重写先临时变量再更新 | Page Model `rewriteByAi()` |
| 17 | 已发布 url_path 不可修改 | Page Controller `edit()` 后端强制拒绝 + 前端 readonly |
| 18 | 删除前检查关联（控制器层，因 Model::del() 为 final 不检查 errors） | Keyword/Task Controller `del()` |
| 19 | 提示注入防护（`<keyword>` 标签） | Prompt 模板 |
| 20 | CSV 限制 2MB/1000行 | Keyword Controller `import()` |
| 21 | AI 速率限制 3秒/配置 | `ai_chat()` |
| 22 | 内容质量控制 <500字符=失败 | Keyword Model `generatePage()` + Page Model `rewriteByAi()` |
| 23 | Sitemap 只含 status=1 + 绝对URL + 路径校验 | Task Model `execSitemap()` |
| 24 | CLI 仅命令行执行 | `app/index/command/Cron.php` `php_sapi_name()` 检测 |
