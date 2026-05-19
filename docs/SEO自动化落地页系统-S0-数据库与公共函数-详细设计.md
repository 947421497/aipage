
# S0：数据库与公共函数 - 详细设计文档

&gt; **文档版本**: v1.0
&gt; **创建日期**: 2026-05-19
&gt; **模块编号**: S0
&gt; **模块名称**: 数据库与公共函数

---

## 1. 功能需求分析

### 1.1 功能概述
本模块负责数据库准备和公共函数实现，为后续所有模块提供基础支持。包含：
- 6 张新表的创建和初始数据
- 7 个核心公共函数

### 1.2 依赖关系
- **无依赖**: 本模块为最基础层

---

## 2. 数据模型设计

### 2.1 表清单

| 表名 | 说明 | 位置 |
|------|------|------|
| `xphp_ai_config` | AI 接入配置 | 2_create_table.sql |
| `xphp_prompt` | 提示词模板 | 2_create_table.sql |
| `xphp_keyword` | 关键词 | 2_create_table.sql |
| `xphp_page` | 落地页 | 2_create_table.sql |
| `xphp_task` | 定时任务 | 2_create_table.sql |
| `xphp_task_log` | 任务执行日志 | 2_create_table.sql |

### 2.2 SQL 文件结构

> **注意**: 所有 SQL 直接追加到 `backup/bak_all_initialize/` 目录下的现有文件中。

#### backup/bak_all_initialize/1_drop_table.sql（追加）
在现有内容末尾追加 6 张表的 DROP 语句：
```sql
-- 清空表: xphp_ai_config
DROP TABLE IF EXISTS `xphp_ai_config`;
-- <fen> --
-- 清空表: xphp_prompt
DROP TABLE IF EXISTS `xphp_prompt`;
-- <fen> --
-- 清空表: xphp_keyword
DROP TABLE IF EXISTS `xphp_keyword`;
-- <fen> --
-- 清空表: xphp_page
DROP TABLE IF EXISTS `xphp_page`;
-- <fen> --
-- 清空表: xphp_task
DROP TABLE IF EXISTS `xphp_task`;
-- <fen> --
-- 清空表: xphp_task_log
DROP TABLE IF EXISTS `xphp_task_log`;
-- <fen> --
```

#### backup/bak_all_initialize/2_create_table.sql（追加）
在现有内容末尾追加 6 张表的 CREATE 语句（完整内容请参考统一需求文档）：
- `xphp_ai_config`（16 字段）
- `xphp_prompt`（9 字段）
- `xphp_keyword`（8 字段）
- `xphp_page`（14 字段）
- `xphp_task`（13 字段）
- `xphp_task_log`（9 字段）

每条用 `-- <fen> --` 分隔。

#### backup/bak_all_initialize/3_insert_xphp_config_part1.sql（追加）
在现有内容末尾追加 7 条 config 配置项：
- site_name
- site_url
- baidu_site
- baidu_token
- baidu_fast_token
- cron_key（自动生成 32 位随机字符）
- cron_allowed_ips

#### backup/bak_all_initialize/3_insert_xphp_menu_part1.sql（追加）
在现有内容末尾追加 4 个后台菜单：
- 关键词管理 (keyword/index)
- 页面管理 (page/index)
- AI引擎 (ai_config/index)
- 定时任务 (task/index)

---

## 3. 公共函数设计

### 3.1 函数清单

| 函数名 | 文件位置 | 功能 |
|--------|---------|------|
| `ai_chat()` | app/common.php | AI 调用统一入口 |
| `filter_landing_html()` | app/common.php | HTML 安全过滤 |
| `to_pinyin()` | app/common.php | 中文转拼音 |
| `generate_url_path()` | app/common.php | URL 路径生成 |
| `baidu_push()` | app/common.php | 百度收录推送 |
| `parse_seo_meta()` | app/common.php | SEO 元数据解析 |
| `render_prompt()` | app/common.php | 提示词渲染 |

### 3.2 函数签名详解

#### 1. ai_chat()
```php
function ai_chat(string $prompt, ?int $config_id = null, ?string $system = null, int $timeout = 60): array
```
- **功能**: AI 调用统一入口，支持 OpenAI/Anthropic/Ollama 协议
- **返回**: 成功返回 `['ok'=&gt;true, 'content'=&gt;'...', 'config_id'=&gt;5]`
- **失败返回**: `['ok'=&gt;false, 'error'=&gt;'...', 'code'=&gt;'...']`

#### 2. filter_landing_html()
```php
function filter_landing_html(string $html): string
```
- **功能**: HTML 安全过滤 + 结构规范化
- **处理步骤**:
  1. 移除空字节和 Unicode 控制字符
  2. 循环 html_entity_decode
  3. 移除危险标签
  4. 保留 &lt;style&gt;，移除危险样式
  5. 移除 on* 事件属性
  6. 移除 javascript: 协议
  7. 仅允许 &lt;img&gt; 的 data: 协议
  8. 对 style 属性执行过滤
  9. 不含 &lt;html&gt; 则自动包裹

#### 3. to_pinyin()
```php
function to_pinyin(string $text): string
```
- **功能**: 中文转拼音
- **实现**: 使用 `Transliterator::create('Any-Latin; Latin-ASCII; Lower()')`
- **Fallback**: intl 不可用 → PHP 拼音库 → page-{md5($text)}

#### 4. generate_url_path()
```php
function generate_url_path(string $keyword): string
```
- **功能**: 生成唯一 URL 路径段
- **处理**:
  1. to_pinyin() 转换
  2. 合并连字符，去首尾
  3. 截断 180 字符
  4. 检查冲突，追加数字后缀

#### 5. baidu_push()
```php
function baidu_push(string $type, array $urls): array
```
- **功能**: 百度收录推送
- **参数**: $type = 'normal' | 'fast'
- **返回**: `['ok'=&gt;true, 'success'=&gt;5, 'fail'=&gt;0, 'detail'=&gt;'...']`

#### 6. parse_seo_meta()
```php
function parse_seo_meta(string $html): array
```
- **功能**: 从 HTML 中解析 SEO 元数据
- **优先级**: 1. 注释标注 2. HTML 标签 3. 兜底策略
- **返回**: `['title'=&gt;'...', 'keywords'=&gt;'...', 'description'=&gt;'...']`

#### 7. render_prompt()
```php
function render_prompt(string $template, array $vars): string
```
- **功能**: 提示词变量渲染
- **支持变量**: `{keyword}`、`{site_name}`、`{site_url}`、`{date}`、`{time}`
- **实现**: `str_replace(array_keys($vars), array_values($vars), $template)`

---

## 4. 文件清单

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| **修改文件** | backup/bak_all_initialize/1_drop_table.sql | 追加6张表的 DROP |
| **修改文件** | backup/bak_all_initialize/2_create_table.sql | 追加6张表的 CREATE |
| **修改文件** | backup/bak_all_initialize/3_insert_xphp_config_part1.sql | 追加7条配置 |
| **修改文件** | backup/bak_all_initialize/3_insert_xphp_menu_part1.sql | 追加4个菜单 |
| **修改文件** | app/common.php | 末尾追加 7 个函数 |

---

## 5. 验证方法

- [ ] SQL 文件语法检查通过
- [ ] `php -l app/common.php` 语法检查通过
- [ ] 执行 SQL 能成功创建 6 张新表
- [ ] 各公共函数可正常调用

---

## 6. 参考文档

- SEO 自动化落地页系统-统一需求文档.md（第 5-6 章）

