# SEO 自动化落地页系统 — 统一需求文档
> **适用框架**: XPHP v6.1.1 | PHP >= 8.1

---

## 1. 产品概述

### 1.1 产品背景
为满足 SEO 运营团队批量生成高质量落地页的需求，构建一套基于 AI 的自动化落地页生成系统。系统支持通过关键词驱动，自动生成符合 SEO 规范的 HTML 落地页，并提供完整的内容管理、百度推送、定时任务等能力。

### 1.2 产品目标
| 目标 | 描述 |
|------|------|
| 效率提升 | 通过 AI 自动化生成落地页，减少人工编写成本 |
| SEO 优化 | 自动生成符合搜索引擎规范的页面结构和元数据 |
| 批量管理 | 支持关键词批量导入、页面批量生成、定时自动维护 |
| 安全稳定 | 多层安全防护，确保生成内容安全可控 |

### 1.3 目标用户
- **SEO 运营人员**：管理关键词、审核生成内容、配置推送策略
- **内容编辑**：编辑和优化 AI 生成的落地页内容
- **系统管理员**：配置 AI 引擎、管理定时任务、监控系统状态

---

## 2. 术语表
| 术语 | 定义 |
|------|------|
| 落地页 (Landing Page) | 针对特定关键词生成的独立 HTML 页面，用于搜索引擎收录 |
| 拓词 | 基于已有关键词，通过 AI 扩展生成相关关键词的过程 |
| 拼音 URL | 将中文关键词转换为拼音格式，作为页面访问路径 |
| AI 配置 | 对接第三方 AI 服务的连接参数（API 地址、密钥、模型等） |
| 提示词 (Prompt) 模板 | 指导 AI 生成内容的预设指令模板 |
| 定时任务 | 定时执行的自动化任务，如批量生成、百度推送等 |

---

## 3. 产品架构

### 3.1 系统架构图
```
┌─────────────────────────────────────────────────────┐
│                    后台管理 (admin)                   │
├──────────┬──────────┬───────────┬────────────────────┤
│ AI引擎    │ 关键词管理 │ 落地页管理  │ 定时任务            │
│ AiConfig │ Keyword  │ Page      │ Task               │
│ 提示词    │          │           │                    │
├──────────┴──────────┴───────────┴────────────────────┤
│                    公共层 (common)                     │
├─────────────────────┬───────────────────────────────┤
│  ai_chat()          │  AiConfig Model (admin)        │
│  filter_landing_html│  提示词 Model (admin)          │
│  to_pinyin()        │  Keyword Model                 │
│  generate_url_path()│  Page Model                    │
│  baidu_push()       │  Task Model                    │
│  parse_seo_meta()   │  TaskLog Model                 │
│  render_prompt()    │                                │
├─────────────────────┴───────────────────────────────┤
│                    前台 (index)                       │
├─────────────────────┬───────────────────────────────┤
│  Index::dispatch()  │  Cron::index()                 │
│  Index::index()     │                                │
└─────────────────────┴───────────────────────────────┘
```

### 3.2 核心业务流程

#### 3.2.1 关键词到落地页生成流程
**详细生成步骤**:
1. 选择关键词（has_page=0）
2. 调用 render_prompt() 渲染 page 类型模板
3. 调用 ai_chat() 生成内容
4. 检查内容长度（<500 视为失败）
5. 调用 parse_seo_meta() 解析 SEO 元数据
6. 调用 filter_landing_html() 安全过滤
7. 自动包裹 HTML（不含 <html> 的话）
8. 同一事务内：
   - 保存 page 到数据库（status=0）
   - 更新 keyword.has_page=1
9. 提交事务
```
添加关键词(has_page=0) → [手动/批量触发] → AI生成内容 → 安全过滤 → 存入数据库(草稿status=0)
                                                              ↓
                                                    URL 格式: /keyword/{pinyin}.html
```

#### 3.2.2 页面生命周期状态机
```
[无页面] ──AI生成──→ 草稿(status=0) ──发布──→ 已发布(status=1) ──下线──→ 草稿(status=0)
                          ↑                       │
                          │                       ├──百度普通推送──→ is_pushed_normal=1
                          │                       ├──百度快速推送──→ is_pushed_fast=1
                          │                       │
                          └──── AI重写 ←──────────┘
草稿(status=0) ──删除──→ [已删除]
已发布(status=1) ──必须先下线──→ 草稿(status=0) ──删除──→ [已删除]
```
**状态说明**:
- **草稿(status=0)**: 页面已生成但不可前台访问，可继续编辑
- **已发布(status=1)**: 页面可正常访问，URL 路径锁定不可修改
- **下线**: 已发布页面下线后回到草稿，前台返回 404，推送标记保持不变
- **删除**: 草稿可直接删除；已发布页面需先下线再删除
**状态转换规则**:
| 操作 | 状态变化 | 推送标记变化 |
|------|----------|-------------|
| AI 生成 | 无→草稿 | 不涉及 |
| 发布 | 草稿→已发布 | is_pushed_normal 和 is_pushed_fast 重置为 0 |
| 下线 | 已发布→草稿 | 推送标记保持不变 |
| AI 重写 | 任意→草稿 | is_pushed_normal 和 is_pushed_fast 重置为 0 |
| 删除 | 草稿→删除 | 不涉及 |
**推送约束**: 仅 `status=1 AND 对应 is_pushed_xxx=0` 的页面参与推送
**推送策略**:
- **快速收录**: 仅推送 `is_pushed_fast=0 AND is_pushed_normal=0` 的页面（从未推送过的新页面）
- **普通收录**: 推送 `is_pushed_normal=0` 的所有已发布页面（兜底所有未推送的）

---

## 4. 功能需求

### 4.1 AI 引擎管理

#### 4.1.1 AI 配置管理
| 功能项 | 需求描述 |
|--------|----------|
| 配置 CRUD | 支持创建、读取、更新、删除 AI 接入配置 |
| 协议支持 | 支持 OpenAI / Anthropic / Ollama 三种协议类型 |
| 厂商预设 | 提供 11 种厂商预设，选择后自动填充协议类型、API 地址、默认模型、SSL 验证设置 |
| 密钥管理 | 新增时必填（Ollama 除外），更新时若提交空值则保留原值；API Key 明文存储（按用户要求） |
| 连接测试 | 提供测试连接功能，3 秒限频缓存 |
| 轮询容错 | 未指定配置时自动轮询所有启用配置，实现故障转移 |
**厂商预设清单**:
| 标识 | 名称 | 协议类型 | API 地址 | 默认模型 | SSL验证 |
|------|------|----------|----------|----------|---------|
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

#### 4.1.2 提示词模板管理
| 功能项 | 需求描述 |
|--------|----------|
| 模板 CRUD | 支持创建、读取、更新、删除提示词模板 |
| 类型区分 | 支持「落地页生成」和「拓词扩展」两种类型 |
| 拓词方向 | 拓词类型支持四种方向：相关词(related)、问答词(question)、长尾词(longtail)、商业词(commercial) |
| 变量支持 | 提示词内容支持变量：`{keyword}` `{site_name}` `{site_url}` `{date}` `{time}` |
| 变量替换 | 调用方在调用 `ai_chat()` 前使用 `render_prompt()` 替换模板变量 |

### 4.2 关键词管理
| 功能模块 | 方法 | 需求描述 | 优先级 |
|----------|------|----------|--------|
| 关键词列表 | index() | 关键词列表，按词/来源/是否有页面筛选 | P0 |
| 新增关键词 | add() | 添加关键词，自动生成拼音 | P0 |
| 编辑关键词 | edit() | 编辑关键词文本 | P0 |
| 删除关键词 | del() | 仅允许删除已停用(status=0)且未生成页面的关键词 | P0 |
| 状态切换 | state() | 单个启用/停用关键词 | P0 |
| 批量启停 | batchToggle() | 批量启用/停用关键词 | P0 |
| CSV 导入 | importCsv() | 支持上传 CSV 文件批量导入，限制 2MB / 1000 行；公式注入防护（等号等触发字符前加单引号转义）；MIME 类型验证 + 随机哈希重命名 | P1 |
| CSV 导出 | exportCsv() | 支持导出全部关键词数据 | P1 |
| AI 拓词 | expand() | 选择关键词 → 调用 render_prompt() 渲染 expand 类型模板 → 调用 ai_chat() 生成候选词 → 标记已存在词 → 用户勾选 → 批量保存 | P1 |
**关键词来源类型**: manual(手动添加) / ai(AI拓词) / csv(CSV导入)

### 4.3 落地页管理
| 功能模块 | 方法 | 需求描述 | 优先级 |
|----------|------|----------|--------|
| 页面列表 | index() | 关联显示关键词，支持按标题/状态筛选 | P0 |
| 新增页面 | add() | 手动新增页面 | P0 |
| 编辑页面 | edit() | 上方表单编辑 + 下方 textarea 代码编辑区（等宽字体，最小高度 500px）；已发布页面的 url_path 只读 | P0 |
| 删除页面 | del() | 删除页面，同步更新 keyword.has_page | P0 |
| 状态切换 | state() | 发布时重置推送标记；下线后页面不可访问，前台返回 404 | P0 |
| 页面预览 | preview() | 预览页面内容 | P1 |
| 手动生成 | generate() | 手动触发单页面 AI 生成 | P1 |
| AI 重写 | rewrite() | 调用 AI 重新生成内容，**重写前弹出确认框**，重写后状态回退为草稿，推送标记重置 | P1 |
| 批量生成 | batchGenerate() | 选择多个关键词 → flock 互斥执行（单次最多3个）→ 同步返回结果 | P1 |
**缓存管理**: 页面增删改时自动清除前台缓存

### 4.4 定时任务管理
| 功能模块 | 方法 | 需求描述 | 优先级 |
|----------|------|----------|--------|
| 任务列表 | index() | 显示任务名称、执行频率、最后执行状态、累计统计 | P0 |
| 新增任务 | add() | 支持创建新任务 | P0 |
| 编辑任务 | edit() | 支持编辑任务名称、执行频率描述、启用状态、超时时间 | P0 |
| 删除任务 | del() | 存在执行日志的任务不可删除（需覆盖父类 del()，因 Cp 基类 del() 内置 status=0 过滤，而 xphp_task 的 status 默认值为 0） | P0 |
| 状态切换 | state() | 启用/禁用任务 | P0 |
| 执行日志 | log() | 查看任务执行历史，失败项红色标记 | P1 |
| 手动执行 | run() | 支持手动触发单个任务执行 | P1 |
**内置任务类型**:
| 任务类型 | 说明 | 触发方式 |
|----------|------|----------|
| generate_page | 批量生成页面（每次最多3个未生成页面的关键词） | 定时 |
| push_baidu | 百度普通收录推送（仅推 is_pushed_normal=0 的已发布页面） | 定时 |
| push_baidu_fast | 百度快速收录推送（仅推 is_pushed_fast=0 AND is_pushed_normal=0 的新发布页面） | 定时 |
| sitemap | 生成 sitemap.xml（robots.txt 为静态文件） | 定时 / 页面发布后标记脏 |
| clear_cache | 清理缓存 + 清理30天前日志 + 修复 has_page 不一致（result 分段记录） | 定时 |
**定时触发方式**:
- **HTTP 触发**: `/cron/{key}` → `Cron::index()`，需密钥验证（64位十六进制字符串，bin2hex(random_bytes(32))）+ IP 白名单（为空时默认拒绝所有）+ 60秒限频
**定时触发流程**（9步）:
1. 访问 `/cron/{cron_key}`
2. 验证 cron_key
3. IP 白名单验证（空则拒绝所有）
4. 60 秒限频
5. 获取 flock 锁（`runtime/cache/seo_lock_generate.lock`）
6. 超时恢复：标记超时任务为失败
7. 执行所有启用的任务
8. 释放锁
9. 返回执行结果
**Sitemap 脏标记策略**: 页面发布/下线/删除时通过 `cache('sitemap_dirty', true)` 设置缓存标记；Sitemap 任务执行时通过 `cache('sitemap_dirty')` 读取标记，无变更则跳过生成并记录"无变更跳过"，有变更则生成后通过 `cache('sitemap_dirty', null)` 清除标记。
**批量生成并发控制**: 手动批量生成和定时任务生成共享同一 flock 锁文件（锁文件路径：`runtime/cache/seo_lock_generate.lock`）。获取锁失败时：手动操作（Page 控制器 `batchGenerate()` 或 Task 控制器 `run()`）返回 HTTP 409"已有生成任务执行中"；Cron 触发（Cron 控制器 `index()`）记录"因其他生成任务执行中而跳过"并返回 HTTP 200。
**批量生成部分失败**: 每个关键词独立生成、独立记录成功/失败，前端展示每个关键词的生成状态，失败的允许单独重试。所有生成的页面默认为草稿状态。

### 4.5 前台落地页展示
| 功能模块 | 方法 | 需求描述 | 优先级 |
|----------|------|----------|--------|
| 路由分发 | Index::dispatch() | `/keyword/{pinyin}.html` → `Index::dispatch()` | P0 |
| 访问控制 | Index::dispatch() | 仅渲染 status=1 的页面，草稿/不存在的页面返回 404 状态码 | P0 |
| 安全防护 | Index::dispatch() | 输出 CSP 头，禁止脚本执行 | P0 |
| Canonical | Index::dispatch() | 每个落地页输出 `<link rel="canonical" href="{绝对URL}">` | P0 |
| 相关推荐 | Index::dispatch() | 页面底部追加"相关推荐"模块（5条同站已发布页面链接） | P1 |
| 访问统计 | Index::dispatch() | 访问时原子递增 view_count | P1 |
| 页面缓存 | Index::dispatch() | 页面缓存 1 小时（`cache_make()` 读取或生成并缓存） | P1 |
**CSP 头配置**:
```
default-src 'none'; style-src 'self' 'unsafe-inline'; script-src 'none'; img-src 'self' data: https:; font-src 'self' https:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; connect-src 'none'
```
**404 处理**: 页面不存在或 status=0 时，返回 HTTP 404 状态码 + 简单提示页面。
**落地页渲染**: 从数据库读取 content 字段直接输出，无单独模板。
**落地页访问流程**（8步）:
1. 访问 `/keyword/{pinyin}.html`
2. 根据 pinyin 查询 page 表，url_path = pinyin
3. 检查 status=1，否则返回 404
4. 输出 CSP 头（禁止脚本执行）
5. 输出 Canonical URL
6. 原子递增 view_count
7. 输出页面 content
8. 追加相关推荐（5 条页面链接，在 `</body>` 前）
**相关推荐实现**: `Index::dispatch()` 渲染时，查询 5 条 status=1 的其他页面，拼成 HTML 块追加到 content 末尾（`</body>` 前或内容末尾）。
**参数传递机制**: 路由配置 `keyword/([a-zA-Z\x7f-\xff0-9-%\+]+)` 中的正则捕获组，通过框架参数注入传递给控制器方法。路由映射 `'keyword/([a-zA-Z\x7f-\xff0-9-%\+]+)' => 'index/dispatch/path/${1}'` 中，`${1}` 为正则第一个捕获组，框架将其作为 `dispatch()` 方法的 `$path` 参数注入。`$path` 参数即 keyword 表的 `pinyin` 字段值（存储为 page 表的 `url_path` 字段），控制器通过 `WHERE url_path = $path` 查询对应页面。

### 4.6 前台首页
| 功能模块 | 需求描述 | 优先级 |
|----------|----------|--------|
| 内容展示 | 展示最新 10 条已发布页面（标题+描述+时间，按 create_time 倒序） | P0 |
| 缓存策略 | 缓存 1 小时（`cache_make()` 读取或生成并缓存），页面发布/下线/删除时通过 `cache('index_pages', null)` 清除 | P1 |

### 4.7 后台仪表盘
| 功能模块 | 需求描述 | 优先级 |
|----------|----------|--------|
| 统计卡片 | 关键词总数 / 已生成页面 / 页面总访问 / AI 调用次数 | P0 |
| 系统就绪检查 | 顶部横幅提示未满足的前置条件（AI配置/提示词模板/站点配置） | P0 |
| 数据图表 | 7天页面生成趋势折线图 / 关键词来源饼图 / 页面状态环形图 | P1 |
| 系统信息 | 底部折叠面板，默认收起 | P2 |
| 自动初始化 | 首次访问时自动生成 cron_key（64位十六进制字符串，bin2hex(random_bytes(32))） | P0 |
**系统就绪检查项**:
- AI 配置：至少 1 个 status=1 的配置
- 提示词模板：落地页类型至少 1 个 status=1 的模板
- 站点配置：site_name 和 site_url 非空
不满足时顶部显示黄色横幅提示，引导用户完成配置。

---

## 5. 数据模型

### 5.1 实体关系图
```
keyword (1) ──── (0..1) page     （keyword_id=0 表示无关联，应用层通过 has_page 标志保证一对一）
page ────→ (0..1) ai_config      （ai_config_id=0 表示未记录）
page ────→ (0..1) prompt         （prompt_id=0 表示未记录）
task (1) ──── (0..n) task_log    （task_id NOT NULL）
```

### 5.2 数据表定义
> **字段数量以 CREATE TABLE 为准**：ai_config=15, prompt=8, keyword=9, page=15, task=15, task_log=9

#### 5.2.1 xphp_ai_config（AI 接入配置，15 字段）
| 字段 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | int(11) unsigned PK | 是 | 自增 | 主键 |
| name | varchar(50) | 是 | '' | 配置名称 |
| api_type | varchar(20) | 是 | 'openai' | 接口类型：openai / anthropic / ollama |
| api_url | varchar(500) | 是 | '' | API 完整 URL |
| api_key | varchar(1000) | 条件 | '' | API Key 明文存储，Ollama 类型非必填 |
| model | varchar(100) | 是 | '' | 模型名称 |
| max_tokens | int(11) unsigned | 否 | 2000 | 最大 token 数 |
| temperature | decimal(4,2) | 否 | 0.70 | 温度 0.00-2.00 |
| max_retries | tinyint(1) unsigned | 否 | 3 | 重试次数 |
| retry_interval | int(11) unsigned | 否 | 2 | 重试间隔秒 |
| verify_ssl | tinyint(1) unsigned | 否 | 1 | 0 不验证 / 1 验证 SSL（Ollama 厂商预设为 0） |
| call_count | int(11) unsigned | 否 | 0 | 累计调用次数（原子递增更新） |
| status | tinyint(1) unsigned | 否 | 1 | 0 禁用 / 1 启用 |
| create_time | int(10) unsigned | 否 | 0 | 创建时间（框架自动填充） |
| update_time | int(10) unsigned | 否 | 0 | 更新时间（框架自动填充） |
**索引**: `idx_status (status)`

#### 5.2.2 xphp_prompt（提示词模板，8 字段）
| 字段 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | int(11) unsigned PK | 是 | 自增 | 主键 |
| name | varchar(50) | 是 | '' | 模板名称 |
| type | varchar(20) | 是 | 'page' | 模板类型：page（落地页）/ expand（拓词） |
| direction | varchar(20) | 条件 | '' | 拓词方向：related / question / longtail / commercial；落地页类型为空 |
| content | text | 是 | - | 提示词内容，支持变量 |
| status | tinyint(1) unsigned | 否 | 1 | 0 禁用 / 1 启用 |
| create_time | int(10) unsigned | 否 | 0 | 创建时间（框架自动填充） |
| update_time | int(10) unsigned | 否 | 0 | 更新时间（框架自动填充） |
**索引**: `idx_type (type)`, `idx_type_direction (type, direction)`

#### 5.2.3 xphp_keyword（关键词，9 字段）
| 字段 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | int(11) unsigned PK | 是 | 自增 | 主键 |
| word | varchar(100) | 是 | '' | 关键词文本（UNIQUE） |
| pinyin | varchar(200) | 是 | '' | 拼音（自动生成，用于 URL） |
| source | varchar(20) | 否 | 'manual' | 来源：manual / ai / csv |
| group_id | int(11) unsigned | 否 | NULL | 分组 ID（预留字段，MVP 不建分组表和 UI） |
| status | tinyint(1) unsigned | 否 | 1 | 0 停用 / 1 启用 |
| has_page | tinyint(1) unsigned | 否 | 0 | 0 无页面 / 1 已有页面（与 page 表事务同步，定时任务兜底校验） |
| create_time | int(10) unsigned | 否 | 0 | 创建时间（框架自动填充） |
| update_time | int(10) unsigned | 否 | 0 | 更新时间（框架自动填充） |
**索引**: `uk_word (word)`, `idx_pinyin (pinyin)`, `idx_status (status)`, `idx_has_page (has_page)`, `idx_source (source)`
**pinyin 冲突处理**: pinyin 使用普通索引 `idx_pinyin`（非唯一索引），冲突时由 `generate_url_path()` 自动追加数字后缀（如 `beijing-2`）。
**has_page 一致性保障**:
1. 页面 INSERT/DELETE 时在同一事务内同步更新 keyword.has_page
2. 删除校验直接查询 page 表而非依赖 has_page 字段
3. clear_cache 定时任务作为兜底校验

#### 5.2.4 xphp_page（页面，15 字段）
| 字段 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | int(11) unsigned PK | 是 | 自增 | 主键 |
| keyword_id | int(11) unsigned | 否 | 0 | 关联关键词 ID，0=无关联 |
| url_path | varchar(200) | 是 | '' | URL 路径段（UNIQUE），仅存拼音部分，如 `seo-optimization` |
| title | varchar(200) | 是 | '' | 页面标题 |
| keywords | varchar(500) | 否 | '' | meta keywords |
| description | varchar(500) | 否 | '' | meta description |
| content | mediumtext | 是 | - | 完整 HTML 落地页内容 |
| ai_config_id | int(11) unsigned | 否 | 0 | 使用的 AI 配置 ID，0=未记录 |
| prompt_id | int(11) unsigned | 否 | 0 | 使用的提示词 ID，0=未记录 |
| status | tinyint(1) unsigned | 否 | 0 | 0 草稿 / 1 已发布 |
| view_count | int(11) unsigned | 否 | 0 | 访问量（原子递增） |
| is_pushed_normal | tinyint(1) unsigned | 否 | 0 | 0 未推送 / 1 已推送百度普通收录 |
| is_pushed_fast | tinyint(1) unsigned | 否 | 0 | 0 未推送 / 1 已推送百度快速收录 |
| create_time | int(10) unsigned | 否 | 0 | 创建时间（框架自动填充） |
| update_time | int(10) unsigned | 否 | 0 | 更新时间（框架自动填充） |
**索引**: `uk_url_path (url_path)`, `idx_keyword_id (keyword_id)`, `idx_status (status)`, `idx_status_is_pushed_normal (status, is_pushed_normal)`
**url_path 存储格式**: 仅存储拼音路径段（如 `seo-optimization`），不含 `/keyword/` 前缀和 `.html` 后缀。前台路由匹配时框架自动剥离前缀和后缀，控制器直接 `WHERE url_path = ?` 查询。

#### 5.2.5 xphp_task（定时任务，15 字段）
| 字段 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | int(11) unsigned PK | 是 | 自增 | 主键 |
| name | varchar(50) | 是 | '' | 任务名称 |
| type | varchar(30) | 是 | '' | 任务类型（UNIQUE，每种全局仅一条） |
| cron_desc | varchar(50) | 否 | '' | 执行频率描述（仅展示，实际频率由外部 crontab 控制） |
| timeout | int(11) unsigned | 否 | 300 | 超时时间（秒），用于 set_time_limit 和超时恢复 |
| last_run_time | int(10) unsigned | 否 | 0 | 最后执行时间 |
| last_run_status | tinyint(1) unsigned | 否 | 0 | 0 未执行 / 1 成功 / 2 失败 |
| last_run_msg | varchar(500) | 否 | '' | 最后执行结果消息 |
| last_success_time | int(10) unsigned | 否 | 0 | 最后成功执行时间 |
| total_run | int(11) unsigned | 否 | 0 | 累计执行次数 |
| total_fail | int(11) unsigned | 否 | 0 | 累计失败次数 |
| consecutive_fail | int(11) unsigned | 否 | 0 | 连续失败次数（成功时重置为 0） |
| status | tinyint(1) unsigned | 否 | **0** | 0 禁用 / 1 启用（**默认值为 0，即新建任务默认禁用**） |
| create_time | int(10) unsigned | 否 | 0 | 创建时间（框架自动填充） |
| update_time | int(10) unsigned | 否 | 0 | 更新时间（框架自动填充） |
**索引**: `uk_type (type)`, `idx_status (status)`
**⚠️ status 默认值为 0（禁用）**: 新建任务默认禁用，需手动启用。Cp 基类 `del()` 方法内置 `status = 0` 过滤条件，而 xphp_task 的 status 默认值恰好为 0，因此 Task 控制器必须覆盖 `del()` 方法以绕过此过滤。

#### 5.2.6 xphp_task_log（任务执行日志，9 字段）
| 字段 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| id | int(11) unsigned PK | 是 | 自增 | 主键 |
| task_id | int(11) unsigned | 是 | 0 | 关联任务 ID（NOT NULL） |
| status | tinyint(1) unsigned | 否 | 0 | 0 执行中 / 1 成功 / 2 失败 |
| result | text | 否 | - | 执行结果详情 JSON |
| start_time | int(10) unsigned | 否 | 0 | 开始时间（0=未记录） |
| end_time | int(10) unsigned | 否 | 0 | 结束时间（0=未记录） |
| duration | int(11) unsigned | 否 | 0 | 耗时毫秒（由 microtime 差值计算） |
| create_time | int(10) unsigned | 否 | 0 | 记录创建时间（框架自动填充） |
| update_time | int(10) unsigned | 否 | 0 | 更新时间（框架自动填充） |
**索引**: `idx_task_id (task_id)`, `idx_task_id_start_time (task_id, start_time)`, `idx_start_time (start_time)`, `idx_create_time (create_time)`
**clear_cache 任务 result 格式**:
```json
{
    "cache_clear": {"ok": true, "count": 15},
    "log_clean": {"ok": true, "deleted": 3},
    "has_page_fix": {"ok": true, "fixed": 0}
}
```
当 `has_page_fix.fixed > 0` 时，Dashboard 应提示数据不一致已修复。

### 5.3 站点配置扩展
需在现有 xphp_config 表（8条初始配置）基础上新增 7 条配置项：
| config_key | 说明 | 用途 | 存储方式 |
|------------|------|------|----------|
| site_name | 网站名称 | 用于落地页生成，同步到前台 site.php | 明文 |
| site_url | 网站 URL | 生成绝对链接、Sitemap、Canonical | 明文 |
| baidu_site | 百度站点域名 | 百度推送 | 明文 |
| baidu_token | 百度普通收录 Token | 百度普通收录推送 | 明文 |
| baidu_fast_token | 百度快速收录 Token | 百度快速收录推送 | 明文 |
| cron_key | 定时任务安全密钥 | HTTP 触发方式安全验证 | 明文；自动生成 64 位十六进制字符串（bin2hex(random_bytes(32))） |
| cron_allowed_ips | 定时任务允许 IP 列表 | IP 白名单限制；为空时默认拒绝所有 HTTP 触发 | 明文 |
> SQL 语句见第 11.3 节。

### 5.4 后台菜单扩展
在原有 4 个系统菜单基础上新增 4 个业务菜单（按业务工作流排序）：
| 菜单名称 | 路由 | 图标 |
|----------|------|------|
| AI引擎 | ai_config/index | mdi mdi-robot-outline |
| 关键词管理 | keyword/index | mdi mdi-key-variant |
| 落地页管理 | page/index | mdi mdi-file-document-outline |
| 定时任务 | task/index | mdi mdi-clock-outline |
> SQL 语句见第 11.4 节。

---

## 6. 公共函数接口
文件：`app/common.php`，在现有函数末尾追加。

### 6.1 ai_chat(string $prompt, ?int $config_id = null, ?string $system = null, int $timeout = 60): array
**功能**: AI 调用统一入口（纯通信层，不包含业务质量控制）
**参数**:
- `$prompt`: 提示词内容（变量替换已在调用方完成）
- `$config_id`: 指定 AI 配置 ID，不传则自动轮询
- `$system`: system prompt（仅 OpenAI/Anthropic 协议使用，Ollama 忽略）
- `$timeout`: 每次重试的超时时间（不含重试累计），默认 60 秒
**行为**:
- 不传 `$config_id`：轮询所有 status=1 的 AI 配置，从上次成功的配置之后开始，成功后缓存本次配置 ID
- 传入 `$config_id`：使用指定配置，失败不切换
- 按 api_type 分发请求：openai / anthropic / ollama 三种协议
- 重试策略：超时/连接失败/5xx/429 可重试；401/403/400/404/SSL 错误不可重试；429 指数退避上限 30s
- 速率限制：同一 AI 配置 3 秒内不允许重复调用
- 单次超时控制：每次请求的 CURLOPT_TIMEOUT 设为 `$timeout` 秒，超时即进入重试
- 全部配置轮询失败后返回 `NO_AVAILABLE_CONFIG` 错误
**按 api_type 分发**:
| api_type | 认证 | 请求体 | 响应提取 |
|----------|------|--------|----------|
| openai | `Authorization: Bearer {api_key}` | `{model, messages[{role:"user",content}], max_tokens, temperature}` | `choices[0].message.content` |
| anthropic | `x-api-key: {api_key}` + `anthropic-version: 2023-06-01` | `{model, messages[{role:"user",content}], max_tokens, temperature, system:""}` | `content[0].text` |
| ollama | 无 | `{model, messages[{role:"user",content}], stream:false}` | `message.content` |
**cURL 配置**: `CURLOPT_CONNECTTIMEOUT = 10`，`CURLOPT_TIMEOUT = $timeout`（默认 60）
**重试策略**:
- 可重试：超时(CURLE_OPERATION_TIMEDOUT)、连接失败(CURLE_COULDNT_CONNECT)、HTTP 5xx、HTTP 429
- 不可重试：HTTP 401/403/400/404、cURL SSL 错误(CURLE_SSL_*)——直接返回，不消耗重试次数
- 429 指数退避：从 `retry_interval` 起，每次翻倍，上限 30s
- 其他可重试：固定间隔 `retry_interval`
**返回值**:
- 成功: `['ok'=>true,'content'=>'...','config_id'=>5]`
- 失败: `['ok'=>false,'error'=>'...','code'=>'...']`
**错误码**:
| 错误码 | 说明 |
|--------|------|
| NO_AVAILABLE_CONFIG | 无可用配置 |
| ALL_RETRIES_FAILED | 所有重试失败 |
| RATE_LIMITED | 速率限制 |
| CONFIG_NOT_FOUND | 指定配置不存在 |
**后处理**:
1. 去除 markdown 代码块包裹：`preg_replace('/^```(?:\w+)?\s*\n(.*?)\n```\s*$/s', '$1', $content)`
2. 成功后 `db('ai_config')->where('id', $configId)->setInc('call_count')` 原子递增
**内容质量控制**: 不在 ai_chat 内做长度检查。落地页生成流程中，AI 返回内容 <500 字符视为生成失败；拓词流程中，AI 返回内容 <50 字符视为失败。
**提示注入防护**: 提示词模板中使用 `<keyword>{keyword}</keyword>` 标签标记关键词边界。关键词文本长度限制 100 字符。

### 6.2 filter_landing_html(string $html): string
**功能**: 落地页 HTML 安全过滤 + 结构规范化
**处理步骤**:
1. 移除空字节（`\x00`）
2. 移除 Unicode 控制字符（U+200B-U+200F, U+202A-U+202E）
3. 循环 `html_entity_decode` 直到输出不再变化（最多 10 次）
4. 移除危险标签：script / iframe / embed / object / form / svg / math / details / applet / meta / link / base / template / noscript / portal / xmp
5. 保留 `<style>`，移除其中 `@import` / `expression()` / `url(javascript:)` / `behavior:` / `-moz-binding:`
6. 移除所有 `on*` 事件属性（大小写不敏感）
7. 移除 `javascript:` / `vbscript:` 协议（属性值中，先去除制表符/换行符后再检测）
8. 移除非 `<img>` 标签的 `data:` 协议（仅允许 `<img src="data:image/...">` 的 data URI）
9. 对所有保留标签的 `style` 属性执行与 `<style>` 相同的过滤规则
10. 如果内容不含 `<html>` 标签，自动包裹基本 HTML 结构：
```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>
{原始内容}
</html>
```

### 6.3 to_pinyin(string $text): string
**功能**: 中文转拼音，用于生成 URL 路径
**实现**: 使用 `Transliterator::create('Any-Latin; Latin-ASCII; Lower()')` 转换，空格转连字符 `-`，输出小写。
**后处理**: 连续连字符合并为单个；移除首尾连字符；超过 180 字符截断。
**Fallback**: `intl` 扩展不可用时，使用 PHP 拼音库（如 `overtrue/pinyin`）；最终 fallback 使用 `page-{md5($text)}`，确保永远不返回空字符串。

### 6.4 generate_url_path(string $keyword): string
**功能**: 生成唯一的 URL 路径段
**实现**:
1. 调用 `to_pinyin($keyword)` 获取拼音
2. 后处理：合并连续连字符、去首尾连字符、截断至 180 字符
3. 如果结果为空，使用 `page-{md5($keyword)}`
4. 检查唯一性：查询 page 表 url_path 是否已存在
5. 冲突时追加数字后缀（如 `beijing-2`、`beijing-3`）
**返回**: 唯一的拼音路径段（不含 `/keyword/` 前缀和 `.html` 后缀）

### 6.5 baidu_push(string $type, array $urls): array
**功能**: 百度收录推送
**参数**:
- `$type`: `'normal'`（普通收录）或 `'fast'`（快速收录），白名单校验
- `$urls`: 待推送的 URL 数组（空数组直接返回失败；每个 URL 必须以 site_url 开头）
**行为**:
- 普通收录：`POST https://data.zz.baidu.com/urls?site={site}&token={baidu_token}`
- 快速收录：`POST https://data.zz.baidu.com/urls?site={site}&token={baidu_fast_token}&type=daily`
- Token 缺失时返回 `['ok'=>false,'error'=>'未配置百度推送Token','code'=>'TOKEN_MISSING']`
- Body：`implode("\n", $urls)`
- 分批推送：每批最多 2000 条 URL
- `baidu_site` 校验为合法域名（正则防 SSRF）
**返回值**:
- 成功: `['ok'=>true,'success'=>5,'fail'=>0,'detail'=>'...']`
- 部分成功: `['ok'=>true,'success'=>3,'fail'=>2,'detail'=>'...']`
- 失败: `['ok'=>false,'error'=>'...','code'=>'...']`

### 6.6 parse_seo_meta(string $html): array
**功能**: 从 AI 生成的 HTML 中解析 SEO 元数据
**解析规则**（正则容错：忽略注释标记内空白和大小写）:
1. 注释标注（最高优先级）：`<!--\s*seo:\s*title\s*-->(.*?)<!--\s*/\s*seo:\s*title\s*-->`（is 模式）
2. HTML 标签：`<title>` / `<meta name="keywords">` / `<meta name="description">`
3. 兜底策略：
   - title：取关联关键词 word
   - keywords：取关联关键词 word
   - description：strip_tags → 去除多余空白 → 截取前 150 字符
**长度校验提示**:
- title 超过 60 个字符时警告
- description 超过 160 个字符时警告
**返回值**: `['title'=>'...','keywords'=>'...','description'=>'...']`

### 6.7 render_prompt(string $template, array $vars): string
**功能**: 提示词模板变量渲染（纯字符串替换，不包含任何质量控制逻辑）
**支持的变量**: `{keyword}` `{site_name}` `{site_url}` `{date}` `{time}`
**实现**: `str_replace(array_keys($vars), array_values($vars), $template)`
**质量控制说明**: 注意：本函数仅负责变量替换，不做内容质量控制。质量控制由调用方负责：`generatePage()` 检查落地页 <500 字符，`expandByAi()` 检查拓词 <50 字符

---

## 7. SEO 元数据规范
AI 生成的落地页 HTML 中需使用以下注释标注 SEO 元数据，系统解析后存入 page 表对应字段：
```html
<!--seo:title-->标题内容<!--/seo:title-->
<!--seo:keywords-->关键词内容<!--/seo:keywords-->
<!--seo:description-->描述内容<!--/seo:description-->
```
**解析优先级**:
1. 注释标注（最高优先级，正则容错忽略空白和大小写）
2. HTML 标签（`<title>` / `<meta name="keywords">` / `<meta name="description">`）
3. 兜底策略：title/keywords 取关联关键词，description 取 body 文本 strip_tags 后前 150 字符
**Canonical URL**: 每个落地页必须输出 `<link rel="canonical" href="{site_url}/keyword/{url_path}.html">`，在前台渲染时动态注入 head 区域。
**robots.txt 规范**（静态文件，非动态生成）:
```
User-agent: *
Allow: /keyword/
Disallow: /admin/
Disallow: /cron/
Sitemap: {site_url}/sitemap.xml
```
**内链策略**:
- AI 提示词模板中要求在正文中自然插入 2-3 个相关落地页的内链
- 前台页面底部自动追加"相关推荐"模块（5 条同站已发布页面链接）
- 前台首页展示最新 10 条已发布页面

---

## 8. 安全需求
| 编号 | 安全需求 | 实现方式 |
|------|----------|----------|
| SEC-001 | 前台只查 status=1，草稿不可访问，不存在返回 404 | Index::dispatch() 过滤 + 404 响应 |
| SEC-002 | HTML 纵深防御 + CSP 头 | filter_landing_html() 9步过滤 + 1步结构规范化 + CSP Header |
| SEC-003 | SSL 验证默认开启 | verify_ssl 默认值 1（Ollama 厂商预设为 0） |
| SEC-004 | 批量生成并发控制 | 单次最多3个，flock 互斥执行（手动+定时任务共享锁） |
| SEC-005 | 参数白名单校验 | direction 仅允许 related/question/longtail/commercial |
| SEC-006 | API Key 明文存储 | API Key 明文存储（按用户要求），新增必填/更新空值保留原值（Ollama除外） |
| SEC-007 | 定时任务安全防护 | 密钥验证（64位十六进制字符串）+ 60秒限频 + flock锁（`seo_lock_generate.lock`）+ IP白名单（空=拒绝所有） |
| SEC-008 | 百度推送防 SSRF | HTTPS + 域名正则校验 |
| SEC-009 | 任务执行超时控制 | set_time_limit(task.timeout) |
| SEC-010 | 日志自动清理 | 30 天前日志自动清理 |
| SEC-011 | AI 轮询容错 | ai_chat() 自动切换配置 |
| SEC-012 | 路由参数校验 | path 非空+≤200字符，密钥仅允许字母数字及连字符下划线 |
| SEC-013 | 计数器原子操作 | setInc 原子递增 view_count / call_count |
| SEC-014 | 数据一致性 | 事务包裹 page + has_page 更新；事务内检查 isFail() 并抛异常 |
| SEC-015 | 更新安全 | 重写先临时变量再更新，避免部分更新 |
| SEC-016 | URL 不可变性 | 已发布 url_path 后端强制拒绝修改 + 前端 readonly |
| SEC-017 | 关联删除检查 | 控制器层检查关联完整性（框架 _before_delete 不检查 errors） |
| SEC-018 | 提示注入防护 | `<keyword>` 标签包裹用户输入 |
| SEC-019 | 资源限制 + 文件上传安全 | CSV 限制 2MB/1000行；MIME 类型验证；随机哈希重命名；上传目录禁止 PHP 执行；CSV 公式注入防护（等号等触发字符前加单引号转义） |
| SEC-020 | API 速率限制 | AI 调用 3秒/配置 |
| SEC-021 | 内容质量控制 | 落地页生成内容 <500 字符视为失败（在调用方检查） |
| SEC-022 | Sitemap 安全 | 只含 status=1 + 绝对URL + 路径校验 |
| SEC-023 | CLI 执行限制 | php_sapi_name() 检测，CLI 下不可用 __HOST__ 等常量 |
| SEC-024 | CSRF 防护 | 所有后台 POST/PUT/DELETE 请求验证 CSRF Token（cp_auth 中间件统一处理） |
| SEC-025 | 会话安全 | Session Cookie: HttpOnly + Secure + SameSite=Strict；登录后 session_regenerate_id(true)；超时 30 分钟 |
| SEC-026 | 登录安全 | 连续 5 次失败等 15 分钟（缓存计数） |
| SEC-027 | 后台 XSS 防护 | 框架模板引擎默认 HTML 实体编码输出；后台设置 CSP 头 |
| SEC-028 | 安全响应头 | Nginx 层配置（见部署安全要求） |

---

## 9. 实现顺序与任务清单
| 步骤 | 任务 | 文件数 | 依赖 | 验证方式 |
|------|------|--------|------|----------|
| 1 | SQL 备份文件 | 修改4个+新增1个 | 无 | 执行安装器后检查 6 张新表 |
| 2 | 公共函数 | 1个文件追加7个函数 | 1 | PHP语法检查 |
| 3 | 模型 | 6个新文件：6个common/model | 1,2 | PHP语法检查 |
| 4 | 后台控制器 | 5个新文件：AiConfig, Prompt, Keyword, Page, Task | 1,2,3 | PHP语法检查 |
| 5 | 前台控制器 | 1个新文件Cron + 1个修改Index | 1,2,3 | PHP语法检查 |
| 6 | 路由配置 | 2个修改：admin.php + index.php | 无 | 访问测试URL |
| 7 | 视图模板 | 8个新文件 | 1,4,6 | 浏览器渲染检查 |
| 8 | 修改现有文件（S0 模块） | 2个：common.php, Index控制器(后台仪表盘), 仪表盘视图 | 1,2 | 仪表盘功能集成检查 |
| 9 | 修改现有文件（S5 模块） | 4个：Index控制器(前台), 首页模板, robots.txt | 2,3,6 | 前台功能集成检查 |
**可以并行执行的步骤**：步骤 7（路由）不依赖其他步骤，可提前执行。
**步骤1 详细文件清单**：
| 操作 | 文件 | 说明 |
|------|------|------|
| 修改 | 1_drop_table.sql | 追加6张新表的 DROP TABLE |
| 修改 | 2_create_table.sql | 追加6张新表的 CREATE TABLE |
| 修改 | 3_insert_xphp_config_part1.sql | 追加7条站点配置 |
| 修改 | 3_insert_xphp_menu_part1.sql | 追加4个业务菜单 |
| 新增 | 3_insert_xphp_task_part1.sql | 追加5条内置任务初始数据 |
**步骤3 详细文件清单**：
| 文件 | 位置 | 引用方式 |
|------|------|----------|
| AiConfig.php | app/common/model/ | model('common@ai_config') |
| Prompt.php | app/common/model/ | model('common@prompt') |
| Keyword.php | app/common/model/ | model('common@keyword') |
| Page.php | app/common/model/ | model('common@page') |
| Task.php | app/common/model/ | model('common@task') |
| TaskLog.php | app/common/model/ | model('common@task_log') |
**步骤4 详细文件清单**：
| 文件 | 位置 | 继承 | 说明 |
|------|------|------|------|
| AiConfig.php | app/admin/controller/ | extends Cp | AI配置管理，含测试连接 |
| Prompt.php | app/admin/controller/ | extends Cp | 提示词模板管理 |
| Keyword.php | app/admin/controller/ | extends Cp | 关键词管理，含拓词/导入导出 |
| Page.php | app/admin/controller/ | extends Cp | 落地页管理，含AI重写/批量生成/状态切换 |
| Task.php | app/admin/controller/ | extends Cp | 定时任务管理，覆盖del()，含手动执行/日志 |
**步骤5 详细文件清单**：
| 操作 | 文件 | 说明 |
|------|------|------|
| 新增 | app/index/controller/Cron.php | Cron入口控制器，方法名 index() |
| 修改 | app/index/controller/Index.php | 追加 dispatch() 方法处理落地页路由 |
**步骤8 详细文件清单**（S0 模块 — 仪表盘与公共函数）：
| 文件 | 修改内容 |
|------|----------|
| app/common.php | 追加7个公共函数 |
| app/admin/controller/Index.php | 追加 SEO 统计数据（cache_make）、cron_key 自动生成、系统就绪检查、图表数据方法（不继承 Cp，独立类） |
| app/admin/view/index/index.html | 替换统计卡片、追加就绪横幅、追加 Chart.js 图表、系统信息改为折叠面板 |
**步骤9 详细文件清单**（S5 模块 — 前台展示）：
| 文件 | 修改内容 |
|------|----------|
| app/index/controller/Index.php | 追加 dispatch() 方法 + 修改 index() 方法 |
| template/default/index/index.html | 追加页面列表卡片 |
| public/robots.txt | 初始 robots.txt 内容 |

---

## 10. 框架约束

### 10.1 框架 API 参考
| 函数/常量 | 位置 | 说明 |
|-----------|------|------|
| `encrypt($str, $salt='')` / `decrypt($str, $salt='')` | `xphp/helper.php` | 字符串加解密 |
| `cache(string $name, $value = '', int $expire = 0)` | `xphp/helper.php` | 缓存三种模式：`$value === null` 删除、`$value === ''` 获取/检测（`?` 前缀检测存在性）、其他值设置；用于直接设置标记（如 `sitemap_dirty`）或清除缓存 |
| `cache_make(string $name, ?Closure $closure = null, int $expire = 0)` | `xphp/helper.php` | 缓存获取，不存在则通过闭包生成并缓存；用于"读取或生成并缓存"场景（如页面数据、统计缓存） |
| `cache_clear(string $path = ''): bool` | `xphp/helper.php` | 清除缓存 |
| `site(string $name = '', $default = '', bool $to_array = false)` | `xphp/helper.php` | 读取站点配置 |
| `db(string $table = '', $config = []): object` | `xphp/helper.php` | 数据库查询构造器 |
| `model(string $name = ''): object` | `xphp/helper.php` | 模型实例化，分隔符 `@` 为推荐写法，`.` 也可工作（`name_parse` 中 `@` 被替换为 `.`） |
| `pdo($config = []): object` | `xphp/helper.php` | PDO实例，`pdo()->trans(Closure): bool` 事务 |
| `halt(string $msg = '', int $code = 400, array $params = []): never` | `xphp/helper.php` | 中断输出（never） |
| `ids_filter(string $ids, bool $to_array = false, bool $gt_0 = true): array|string` | `xphp/function.php` | ID过滤转换 |
| `name_parse(string $name, string $app = '', string $sep = '@'): array` | `xphp/function.php` | 名称解析 |
| `remove_xss(string $str): string` | `xphp/function.php` | XSS过滤（会移除style标签，不适用于落地页） |
| `get_curl(string $url, array $post = [], array $header = [], bool $get_header = false, string $cookie = '', string $referer = '', string $ua = '', bool $nobody = false, int $timeout = 30): string` | `xphp/function.php` | cURL请求（不支持JSON body和SSL验证控制） |
| `get_ip(): string` | `xphp/function.php` | 获取客户端IP |
| `ROOT_PATH` | `public/index.php` | 项目根目录（入口文件中 define） |
| `RUNTIME_PATH` | `App::initApp()` | 运行时目录（`ROOT_PATH . '/runtime/' . $app`，按应用区分） |
| `__HOST__` | `App::initApp()` | 当前协议+域名（`(IS_HTTPS ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']`） |
| `__ROOT__` | `App::initApp()` | 站点根路径（`rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')`） |
| `__STATIC__` | `App::initApp()` | 静态资源路径（`__ROOT__ . '/static'`） |

### 10.2 关键约束
- `Model::save()` 和 `Model::del()` 均为 `final`，不可覆写；`Cp` 为 `abstract class`；关联完整性检查须在控制器层执行
- 公共文件路径使用 `ROOT_PATH . '/public/'` 拼接
- `site()` 函数读取当前应用的 `config/site.php`（前台为 `app/index/config/site.php`），后台 Config 控制器的 `syncConfigFile()` 方法将 status=1 的配置写入 `app/index/config/site.php`
- 现有 config 中 `site_title` 用于后台标题栏，新增 `site_name` 用于落地页生成，二者为不同字段

### 10.3 框架行为约束
- **`model()` 分隔符**: `@` 为推荐写法（`model('common@keyword')`），`.` 也可工作（`name_parse` 中 `@` 被替换为 `.`）。后台自有模型使用简写 `model('config')`
- **PHP 8.1+ LSP 严格检查**: 覆盖 Cp 基类方法时参数数量和类型必须完全一致，否则 Fatal Error。调试方法：`php -r "new ReflectionClass('app\admin\controller\Keyword')"`
- **分页渲染**: 必须用 `{$list->links()|raw}`，不能用 `{$list|raw}`。判断空列表用 `{empty $list:}`
- **`db()` JOIN + `paginate()`**: 会导致 count() 查询失败，应避免在 `paginate()` 中使用 JOIN，改用分步查询
- **后台视图布局**: 必须包含完整 lyear 布局（`_head.html` → `lyear-preloader` → `lyear-layout-web` → `sidebar.html` → `_header.html` → `<main>` → `footer.html`）
- **模板编译缓存**: 模板修改后须删除 `runtime/admin/view/` 下所有文件
- **`APP_DEBUG=false`**: 错误被完全隐藏，调试时临时开启 `config/app.php` 的 `debug => true` 和 `trace => true`

### 10.4 业务逻辑约束
- **generatePage() url_path 唯一性**: 必须使用 `generate_url_path($keyword['word'])` 而非 `$keyword['pinyin']`。两个不同关键词可能产生相同拼音，`generate_url_path()` 在页面生成时重新检查 page 表唯一性，避免 `uk_url_path` 约束冲突
- **edit() 视图缓存**: 编辑已发布页面内容后必须显式调用 `_clearViewCache()`。`_after_update` 钩子仅在 status 变更时清缓存，编辑内容（status 不变）不触发钩子
- **execClearCache() sitemap_dirty**: `cache_clear()` 会清除所有缓存含 `sitemap_dirty` 标记，必须在其后调用 `cache('sitemap_dirty', true)` 防止 sitemap 任务跳过生成
- **execPushBaidu 精确标记**: 推送成功后仅标记本次实际推送的页面ID（`where('id', 'in', array_column($pages, 'id'))`），不使用全量条件更新，避免推送期间新发布页面被误标记
- **_after_delete has_page**: has_page 更新须在 `_after_delete` 而非 `_before_delete` 中执行，确保删除成功后才修改关联状态
- **sitemap XML 转义**: `<loc>` 标签中的 URL 须用 `htmlspecialchars()` 转义，防止 `site_url` 含 `&` 等字符导致 XML 格式错误

### 10.5 代码模式
**模型定义规范**:
```php
protected string $table = 'config';
protected string $pk = 'id';
protected array $validate = [...];
protected array $auto = [...];
protected array $filter = [...];
protected array $errors = [];
```
**模型自动时间**: 框架默认启用 `create_time`/`update_time` 自动填充。表中无这两个字段时需设置 `protected string $createTime = ''; protected string $updateTime = '';` 关闭。
**模型钩子**:
```php
protected function _before_insert(array &$data): void {}
protected function _after_insert(array $data): void {}
protected function _before_update(array &$data): void {}
protected function _after_update(array $before, array $after): void {}
protected function _before_delete(array $data): void {}
protected function _after_delete(array $data): void {}
```
**Cp 基类 state() 钩子**:
```php
protected function _after_state(string $field, string $value, array $ids): void {}
```
Cp 基类 `state()` 方法在更新状态后调用 `_after_state()`，子模型可覆盖此钩子执行状态变更后的逻辑（如 Config 控制器用此钩子同步配置文件）。
**⚠️ 重要**: 框架在 `_before_delete` 后不检查 `$this->errors`，关联完整性检查必须在控制器层实现。
**Cp 控制器规范**:
```php
class Config extends Cp
{
    protected string $model = 'config';
    protected string $middleware = 'cp_auth';
    protected string $order = 'id DESC';
    protected int $limit = 10;
    protected array $stateList = ['status' => ['停用', '启用']];
}
```
**Cp 基类 del() 方法**: 内置 `status = 0` 的硬编码过滤条件。对于无 status 字段的表，需在子控制器中覆盖 `del()` 方法。
**模型文件位置**: 所有 SEO 模块模型统一放在 `app\common\model\` 下，使用 `model('common@xxx')` 引用。
**事务内模型操作**: 框架 `pdo()->trans()` 仅在闭包抛异常时回滚，闭包返回值不被使用，`trans()` 只返回 bool。模型 `save()` 验证失败时调用 `halt()` 直接 exit。事务内应手动检查 `isFail()` 并抛异常，不应依赖闭包返回值传递数据：
```php
$result = null;
pdo()->trans(function() use (&$result) {
    $model->save($data);
    if ($model->isFail()) {
        throw new \Exception($model->getError()[0] ?? '操作失败');
    }
    $result = $model->find($model->id);
});
// $result 在事务外使用
```
**halt() 函数**: 返回类型 `never`，调用后脚本立即终止。
**Jump Trait**（用于不继承 Cp 的控制器，如 Cron、后台 Index）:
```php
class Cron { use Jump; protected bool $isApi = true; ... }
```
**注意**: `$jumpUrl` 属性定义在 `Cp` 中而非 `Jump` trait 中。使用 Jump 但不继承 Cp 的控制器（如 Cron）没有 `$jumpUrl`，需自行处理跳转逻辑或使用 `_json()` 响应。
**Jump Trait 关键方法**:
- `error(string|array $msg = '', int $code = 400, ?string $url = null, ?array $data = null)`: 错误跳转，第二个参数 `$code` 是 HTTP 状态码（int），不是 URL
- `_json(int $code = 200, string $msg = '', ?array $data = null, array $extend = []): void`: JSON 响应
- `_jump(string|array $info, $status = 1, ?string $url = null, ?array $data = null): void`: 成功/失败跳转，`$info` 为 `[成功消息, 失败消息]`
- `isPost(): bool` / `isAjax(): bool`: 请求类型判断
- `$isApi` 属性（默认 `false`）：设为 `true` 时，`_jump` 和 `error` 使用 JSON 响应
**后台视图模板语法**:
- 头部：`{include file='public/_head.html'}`
- 侧边栏：`{include file='public/sidebar.html'}`
- 头部导航：`{include file='public/_header.html'}`
- 底部：`{include file='public/footer.html'}`
- 表单：`_form.html` 通过 `openModal()` 加载
- 列表操作：`ajaxConfirm(url, action, refresh)` / `actionConfirm(title, url)` / `openModal(url, title)`
- 模板语法：`{foreach $list as $vo}...{/foreach}`、`{if condition:}...{else:}...{/if}`、`{literal}...JS...{/literal}`

---

## 11. 数据库备份文件
> **产出**：4个 SQL 文件（均为修改现有文件）
> **位置**：`backup/bak_all_initialize/`
> **格式**：每条 SQL 之间用 `-- <fen> --` 分隔

### 11.1 修改 `1_drop_table.sql`
在现有内容末尾追加：
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

### 11.2 修改 `2_create_table.sql`
在现有内容末尾追加 6 张表（约束：UNIQUE 字段用 `NOT NULL DEFAULT ''`；时间字段 `int unsigned NOT NULL DEFAULT 0`；索引命名 `uk_字段名` / `idx_字段名`）：
```sql
-- 表结构: xphp_ai_config --
CREATE TABLE `xphp_ai_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '配置名称',
  `api_type` varchar(20) NOT NULL DEFAULT 'openai' COMMENT '接口类型：openai/anthropic/ollama',
  `api_url` varchar(500) NOT NULL DEFAULT '' COMMENT 'API完整URL',
  `api_key` varchar(1000) NOT NULL DEFAULT '' COMMENT 'API Key（明文存储）',
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
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '通用状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_type_direction` (`type`, `direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提示词模板';
-- <fen> --
-- 表结构: xphp_keyword
CREATE TABLE `xphp_keyword` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `word` varchar(100) NOT NULL DEFAULT '' COMMENT '关键词文本',
  `pinyin` varchar(200) NOT NULL DEFAULT '' COMMENT '拼音',
  `source` varchar(20) NOT NULL DEFAULT 'manual' COMMENT '来源：manual/ai/csv',
  `group_id` int(11) unsigned DEFAULT NULL COMMENT '分组ID（预留）',
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
-- 表结构: xphp_page
CREATE TABLE `xphp_page` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `keyword_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联关键词ID，0=无关联',
  `url_path` varchar(200) NOT NULL DEFAULT '' COMMENT 'URL路径段（仅拼音部分）',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '页面标题',
  `keywords` varchar(500) NOT NULL DEFAULT '' COMMENT 'meta keywords',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT 'meta description',
  `content` mediumtext COMMENT '完整HTML落地页内容',
  `ai_config_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '使用的AI配置ID，0=未记录',
  `prompt_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '使用的提示词ID，0=未记录',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0草稿1已发布',
  `view_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '访问量',
  `is_pushed_normal` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0未推送1已推送百度普通收录',
  `is_pushed_fast` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0未推送1已推送百度快速收录',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_url_path` (`url_path`),
  KEY `idx_keyword_id` (`keyword_id`),
  KEY `idx_status` (`status`),
  KEY `idx_status_is_pushed_normal` (`status`, `is_pushed_normal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='页面';
-- <fen> --
-- 表结构: xphp_task --
CREATE TABLE `xphp_task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '任务名称',
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '任务类型（UNIQUE，每种全局仅一条）',
  `cron_desc` varchar(50) NOT NULL DEFAULT '' COMMENT '执行频率描述（仅展示）',
  `timeout` int(11) unsigned NOT NULL DEFAULT '300' COMMENT '超时时间（秒）',
  `last_run_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后执行时间',
  `last_run_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0未执行1成功2失败',
  `last_run_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '最后执行结果消息',
  `last_success_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后成功执行时间',
  `total_run` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '累计执行次数',
  `total_fail` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '累计失败次数',
  `consecutive_fail` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '连续失败次数',
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

### 11.3 修改 `3_insert_xphp_config_part1.sql`
在现有 8 条 config 记录末尾追加 7 条：
```sql
INSERT INTO `xphp_config` (`id`, `name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('9', '网站名称', 'site_name', '', '0', '1');-- <fen> --
INSERT INTO `xphp_config` (`id`, `name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('10', '网站URL', 'site_url', '', '0', '1');-- <fen> --
INSERT INTO `xphp_config` (`id`, `name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('11', '百度站点域名', 'baidu_site', '', '0', '1');-- <fen> --
INSERT INTO `xphp_config` (`id`, `name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('12', '百度普通收录Token', 'baidu_token', '', '0', '1');-- <fen> --
INSERT INTO `xphp_config` (`id`, `name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('13', '百度快速收录Token', 'baidu_fast_token', '', '0', '1');-- <fen> --
INSERT INTO `xphp_config` (`id`, `name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('14', '定时任务安全密钥', 'cron_key', '', '0', '1');-- <fen> --
INSERT INTO `xphp_config` (`id`, `name`, `config_key`, `config_value`, `config_type`, `status`) VALUES ('15', '定时任务允许IP列表', 'cron_allowed_ips', '', '0', '1');-- <fen> --
```

### 11.4 修改 `3_insert_xphp_menu_part1.sql`
在现有内容末尾追加 4 个业务菜单：
```sql
-- 表数据: xphp_menu(2/2) 每页: 1000 --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('5', '0', 'AI引擎', 'ai_config/index', 'ai_engine', 'mdi mdi-robot-outline', '0', '1010', UNIX_TIMESTAMP(), '1');-- <fen> --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('6', '0', '关键词管理', 'keyword/index', 'keyword', 'mdi mdi-key-variant', '0', '1020', UNIX_TIMESTAMP(), '1');-- <fen> --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('7', '0', '落地页管理', 'page/index', 'page', 'mdi mdi-file-document-outline', '0', '1030', UNIX_TIMESTAMP(), '1');-- <fen> --
INSERT INTO `xphp_menu` (`id`, `parent_id`, `title`, `href`, `sign`, `icon`, `is_sys`, `sort`, `update_time`, `status`) VALUES ('8', '0', '定时任务', 'task/index', 'task', 'mdi mdi-clock-outline', '0', '1040', UNIX_TIMESTAMP(), '1');-- <fen> --
```
安装完成后执行 `widget_reload('menu')` 刷新侧边栏缓存。
> **注意**: Cp 基类 `state()` 方法末尾会自动调用 `$model->widgetReload()` 刷新缓存，但安装时菜单数据是通过 SQL 直接插入的，不经过 `state()` 方法，因此需手动执行 `widget_reload('menu')`。

### 11.5 新增 `3_insert_xphp_task_part1.sql`
追加 5 条内置任务初始数据：
```sql
-- 表数据: xphp_task(1/1) 每页: 1000 --
INSERT INTO `xphp_task` (`id`, `name`, `type`, `cron_desc`, `timeout`, `last_run_time`, `last_run_status`, `last_run_msg`, `last_success_time`, `total_run`, `total_fail`, `consecutive_fail`, `status`, `create_time`, `update_time`) VALUES ('1', '批量生成页面', 'generate_page', '每小时', '300', '0', '0', '', '0', '0', '0', '0', '0', '0', '0');-- <fen> --
INSERT INTO `xphp_task` (`id`, `name`, `type`, `cron_desc`, `timeout`, `last_run_time`, `last_run_status`, `last_run_msg`, `last_success_time`, `total_run`, `total_fail`, `consecutive_fail`, `status`, `create_time`, `update_time`) VALUES ('2', '百度普通收录推送', 'push_baidu', '每天', '300', '0', '0', '', '0', '0', '0', '0', '0', '0', '0');-- <fen> --
INSERT INTO `xphp_task` (`id`, `name`, `type`, `cron_desc`, `timeout`, `last_run_time`, `last_run_status`, `last_run_msg`, `last_success_time`, `total_run`, `total_fail`, `consecutive_fail`, `status`, `create_time`, `update_time`) VALUES ('3', '百度快速收录推送', 'push_baidu_fast', '每天', '300', '0', '0', '', '0', '0', '0', '0', '0', '0', '0');-- <fen> --
INSERT INTO `xphp_task` (`id`, `name`, `type`, `cron_desc`, `timeout`, `last_run_time`, `last_run_status`, `last_run_msg`, `last_success_time`, `total_run`, `total_fail`, `consecutive_fail`, `status`, `create_time`, `update_time`) VALUES ('4', '生成Sitemap', 'sitemap', '每天', '300', '0', '0', '', '0', '0', '0', '0', '0', '0', '0');-- <fen> --
INSERT INTO `xphp_task` (`id`, `name`, `type`, `cron_desc`, `timeout`, `last_run_time`, `last_run_status`, `last_run_msg`, `last_success_time`, `total_run`, `total_fail`, `consecutive_fail`, `status`, `create_time`, `update_time`) VALUES ('5', '清理缓存', 'clear_cache', '每天', '300', '0', '0', '', '0', '0', '0', '0', '0', '0', '0');-- <fen> --
```

---

## 12. 模型
> **产出**：6 个新 PHP 文件
> **依赖**：步骤 1（表存在）、步骤 2（函数可用）

### 12.1 AiConfig
文件：`app/common/model/AiConfig.php`
```php
<?php
declare(strict_types=1);
namespace app\common\model;
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
    public function apiKeyRequired(string $value, string $field, string $params, array $data): bool
    {
        return ($data['api_type'] ?? '') === 'ollama' || !empty($value);
    }
    public function getActiveConfigs(): array
    {
        return db('ai_config')->where('status', 1)->order('id ASC')->select();
    }
}
```
引用方式：`model('common@ai_config')`
**设计说明**：
- `api_key` 明文存储，不使用 `encrypt()`/`decrypt()`
- `filter` 规则 `['api_key', FV_EMPTY, AC_UPDATE]`：更新时若 `api_key` 为空值则跳过该字段，保留原值
- `apiKeyRequired` 验证方法：仅 `AC_INSERT` 时校验，Ollama 类型允许空值，其他类型必填
- 无 `_before_insert` / `_before_update` 钩子（无需加解密处理）

### 12.2 Prompt
文件：`app/common/model/Prompt.php`
```php
<?php
declare(strict_types=1);
namespace app\common\model;
use xphp\core\Model;
class Prompt extends Model
{
    protected string $table = 'prompt';
    protected string $pk = 'id';
    protected array $validate = [
        ['name', 'required', '模板名称必填', FV_MUST, AC_BOTH],
        ['type', '/^(page|expand)$/', '类型无效', FV_MUST, AC_BOTH],
        ['direction', '/^(related|question|longtail|commercial|)$/', '拓词方向无效', FV_MUST, AC_BOTH],
    ];
    protected array $auto = [
        ['status', '1', 'string', FV_MUST, AC_INSERT],
    ];
}
```
引用方式：`model('common@prompt')`

### 12.3 Keyword
文件：`app/common/model/Keyword.php`
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
        $data['pinyin'] = generate_url_path($data['word']);
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
        $promptRow = db('prompt')->where('type', 'expand')->where('direction', $direction)->where('status', 1)->find();
        $promptText = $promptRow ? $promptRow['content'] : $this->getDefaultExpandPrompt($direction);
        $promptText = render_prompt($promptText, [
            '{keyword}'   => $keyword['word'],
            '{site_name}' => site('site_name'),
            '{site_url}'  => site('site_url') ?: __HOST__,
            '{date}'      => date('Y-m-d'),
            '{time}'      => date('H:i:s'),
        ]);
        $result = ai_chat($promptText);
        if (!$result['ok']) {
            $this->errors[] = 'AI拓词失败: ' . $result['error'];
            return [];
        }
        if (mb_strlen($result['content']) < 50) {
            $this->errors[] = 'AI返回内容过短，拓词可能失败';
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
    public function getDefaultExpandPrompt(string $direction): string
    {
        $prompts = [
            'related' => '你是一个SEO关键词专家。请根据以下关键词生成10个语义相关的关键词。
关键词：<keyword>{keyword}</keyword>
要求：
1. 每行一个关键词，不要编号
2. 关键词要与目标关键词语义相关
3. 关键词要有实际搜索价值
4. 不要重复目标关键词本身
5. 关键词长度不超过100个字符',
            'question' => '你是一个SEO关键词专家。请根据以下关键词生成10个用户搜索时常用的疑问句式关键词。
关键词：<keyword>{keyword}</keyword>
要求：
1. 每行一个关键词，不要编号
2. 以疑问句式为主（如何、怎么、为什么、哪里、什么等）
3. 关键词要有实际搜索价值
4. 不要重复目标关键词本身
5. 关键词长度不超过100个字符',
            'longtail' => '你是一个SEO关键词专家。请根据以下关键词生成10个包含目标关键词的长尾组合关键词。
关键词：<keyword>{keyword}</keyword>
要求：
1. 每行一个关键词，不要编号
2. 每个关键词必须包含目标关键词
3. 关键词要有实际搜索价值
4. 长尾组合要自然、符合搜索习惯
5. 关键词长度不超过100个字符',
            'commercial' => '你是一个SEO关键词专家。请根据以下关键词生成10个具有商业/交易意图的关键词。
关键词：<keyword>{keyword}</keyword>
要求：
1. 每行一个关键词，不要编号
2. 关键词要体现购买、比价、评测、推荐等商业意图
3. 关键词要有实际搜索价值
4. 不要重复目标关键词本身
5. 关键词长度不超过100个字符',
        ];
        return $prompts[$direction] ?? $prompts['related'];
    }
}
```
引用方式：`model('common@keyword')`
**设计说明**：
- `_before_insert`：自动调用 `generate_url_path()` 生成唯一拼音路径段，`generate_url_path()` 内部调用 `to_pinyin()` 并检查 page 表 url_path 唯一性，冲突时追加数字后缀
- `expandByAi()`：AI 拓词，返回候选词数组（不含已存在判断，由控制器层处理）
- `getDefaultExpandPrompt()`：无启用模板时的兜底提示词

### 12.4 Page
文件：`app/common/model/Page.php`
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
        if (!empty($data['keyword_id']) && $data['keyword_id'] > 0) {
            db('keyword')->where('id', $data['keyword_id'])->setField('has_page', 1);
        }
    }
    protected function _after_update(array $before, array $after): void
    {
        if (isset($before['status']) && $before['status'] !== ($after['status'] ?? null)) {
            $this->_clearViewCache($after['url_path'] ?? '');
            cache('sitemap_dirty', true);
        }
    }
    protected function _before_delete(array $data): void
    {
    }
    protected function _after_delete(array $data): void
    {
        if (!empty($data['keyword_id']) && $data['keyword_id'] > 0) {
            $exists = db('page')->where('keyword_id', $data['keyword_id'])->where('id', '<>', $data['id'])->count();
            if ($exists === 0) {
                db('keyword')->where('id', $data['keyword_id'])->setField('has_page', 0);
            }
        }
        $this->_clearViewCache($data['url_path'] ?? '');
        cache('sitemap_dirty', true);
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
    public function findByPath(string $path): array
    {
        return db('page')->where('url_path', $path)->where('status', 1)->find();
    }
    public function generatePage(int $keywordId): bool
    {
        $keyword = db('keyword')->find($keywordId);
        if (!$keyword || $keyword['has_page']) {
            $this->errors[] = $keyword ? '该关键词已生成页面' : '关键词不存在';
            return false;
        }
        $promptRow = db('prompt')->where('type', 'page')->where('status', 1)->find();
        $promptText = $promptRow ? $promptRow['content'] : $this->getDefaultPagePrompt();
        $promptText = render_prompt($promptText, [
            '{keyword}' => $keyword['word'],
            '{site_name}' => site('site_name'),
            '{site_url}' => site('site_url') ?: __HOST__,
            '{date}' => date('Y-m-d'),
            '{time}' => date('H:i:s'),
        ]);
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
        $seoMeta = parse_seo_meta($content);
        $content = filter_landing_html($content);
        $urlPath = generate_url_path($keyword['word']);
        $title = $seoMeta['title'] ?: $keyword['word'];
        $pageData = [
            'keyword_id' => $keywordId,
            'url_path' => $urlPath,
            'title' => $title,
            'keywords' => $seoMeta['keywords'],
            'description' => $seoMeta['description'],
            'content' => $content,
            'ai_config_id' => $result['config_id'] ?? 0,
            'prompt_id' => $promptRow ? $promptRow['id'] : 0,
            'status' => 0,
        ];
        $r = pdo()->trans(function () use ($pageData) {
            $res = db('page')->insertGetId($pageData);
            if (!$res) {
                throw new \Exception('页面保存失败');
            }
            db('keyword')->where('id', $pageData['keyword_id'])->setField('has_page', 1);
        });
        if ($r) {
            cache('sitemap_dirty', true);
        }
        return (bool)$r;
    }
    public function rewriteByAi(int $pageId): bool
    {
        $page = db('page')->find($pageId);
        if (!$page) {
            $this->errors[] = '页面不存在';
            return false;
        }
        $keyword = (!empty($page['keyword_id']) && $page['keyword_id'] > 0) ? db('keyword')->find($page['keyword_id']) : null;
        $promptRow = db('prompt')->where('type', 'page')->where('status', 1)->find();
        $promptText = $promptRow ? $promptRow['content'] : $this->getDefaultPagePrompt();
        $promptText = render_prompt($promptText, [
            '{keyword}' => $keyword ? $keyword['word'] : $page['title'],
            '{site_name}' => site('site_name'),
            '{site_url}' => site('site_url') ?: __HOST__,
            '{date}' => date('Y-m-d'),
            '{time}' => date('H:i:s'),
        ]);
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
        $seoMeta = parse_seo_meta($content);
        $content = filter_landing_html($content);
        db('page')->where('id', $pageId)->update([
            'title' => $seoMeta['title'] ?: $page['title'],
            'keywords' => $seoMeta['keywords'],
            'description' => $seoMeta['description'],
            'content' => $content,
            'ai_config_id' => $result['config_id'] ?? 0,
            'prompt_id' => $promptRow ? $promptRow['id'] : 0,
            'status' => 0,
            'is_pushed_normal' => 0,
            'is_pushed_fast' => 0,
        ]);
        $this->_clearViewCache($page['url_path']);
        cache('sitemap_dirty', true);
        return true;
    }
    public function _clearViewCache(string $urlPath): void
    {
        if (!empty($urlPath)) {
            $path = ltrim($urlPath, '/');
            cache('view/' . md5('index/index/dispatch?path=' . $path), null);
        }
        cache('index_pages', null);
    }
}
```
引用方式：`model('common@page')`
**设计说明**：
- `_after_insert`：页面插入后，同步更新 `keyword.has_page=1`，并清除视图缓存
- `_after_delete`：页面删除后，检查该关键词是否还有其他页面，若无则更新 `keyword.has_page=0`；清除视图缓存，设置 `sitemap_dirty=true`
- `_after_update`：通过对比 `$before` 和 `$after` 参数检测变更字段，仅在 status 变更时清除视图缓存并设置 `sitemap_dirty=true`（view_count 递增、is_pushed_* 更新等非状态变更不触发）
- `getDefaultPagePrompt()`：无启用模板时的兜底落地页提示词
- `findByPath()`：前台 `Index::dispatch()` 调用，仅查询 `status=1` 的页面
- `generatePage()`：生成落地页，调用 `ai_chat()` → `parse_seo_meta()` → `filter_landing_html()`，使用 `generate_url_path($keyword['word'])` 生成 url_path（在页面生成时重新检查 page 表唯一性，避免同拼音关键词 UNIQUE 约束冲突）；事务内使用 `db('page')->insertGetId()` 代替 `$this->save()`，避免 save() 验证失败时 halt() 导致事务无法回滚；同时显式更新 `keyword.has_page=1`（替代 `_after_insert` 钩子逻辑）；成功后设置 `sitemap_dirty` 缓存标记
- `rewriteByAi()`：AI 重写内容，调用 `ai_chat()` → `parse_seo_meta()` → `filter_landing_html()`，使用 `db('page')->update()` 代替模型 save()，避免 halt 风险；重写后状态回退为草稿（`status=0`），推送标记重置为 0；`sitemap_dirty` 由方法自身设置（不依赖 `_after_update` 钩子）
- `_clearViewCache()`：**public** 方法，供 Page 控制器 `state()` 方法调用；缓存键使用 `index/index/dispatch` 路径（对应 `Index::dispatch()`），同时清除首页缓存 `index_pages`

### 12.5 Task
文件：`app/common/model/Task.php`
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
        $startMicrotime = microtime(true);
        $startTime = (int)$startMicrotime;
        $logId = db('task_log')->insertGetId([
            'task_id' => $taskId,
            'status' => 0,
            'start_time' => $startTime,
        ]);
        try {
            set_time_limit($task['timeout'] ?? 300);
            $method = 'exec' . str_replace(' ', '', ucwords(str_replace('_', ' ', $task['type'])));
            if (!method_exists($this, $method)) {
                throw new \Exception("任务方法{$method}不存在");
            }
            $result = $this->$method();
            $duration = (int)((microtime(true) - $startMicrotime) * 1000);
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
                'last_success_time' => time(),
                'consecutive_fail' => 0,
            ]);
            db('task')->where('id', $taskId)->setInc('total_run');
            return true;
        } catch (\Throwable $e) {
            $duration = (int)((microtime(true) - $startMicrotime) * 1000);
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
            db('task')->where('id', $taskId)->setInc('consecutive_fail');
            return false;
        }
    }
    public function recoverTimeout(): void
    {
        $tasks = db('task')->column('timeout', 'id');
        $timeoutLogs = db('task_log')
            ->where('status', 0)
            ->select();
        foreach ($timeoutLogs as $log) {
            $timeout = $tasks[$log['task_id']] ?? 300;
            if ($log['start_time'] >= time() - ($timeout + 60)) {
                continue;
            }
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
            db('task')->where('id', $log['task_id'])->setInc('consecutive_fail');
        }
    }
    public function execGeneratePage(): string
    {
        $keywords = db('keyword')->where('has_page', 0)->where('status', 1)->limit(3)->select();
        $count = 0;
        $fail = 0;
        foreach ($keywords as $kw) {
            $r = model('common@page')->generatePage($kw['id']);
            $r ? $count++ : $fail++;
        }
        return "生成{$count}个页面，失败{$fail}个";
    }
    public function execPushBaidu(): string
    {
        $pages = db('page')->where('status', 1)->where('is_pushed_normal', 0)->select();
        if (empty($pages)) return '无待推送页面';
        $siteUrl = site('site_url') ?: __HOST__;
        $urls = array_map(fn($p) => $siteUrl . '/keyword/' . $p['url_path'] . '.html', $pages);
        $result = baidu_push('normal', $urls);
        if (($result['success'] ?? 0) > 0 && ($result['fail'] ?? 0) == 0) {
            $pageIds = array_column($pages, 'id');
            db('page')->where('id', 'in', $pageIds)->setField('is_pushed_normal', 1);
        } elseif (($result['success'] ?? 0) > 0) {
            return "部分推送成功{$result['success']}个，失败{$result['fail']}个，下次将重试";
        }
        return "推送" . count($pages) . "个页面，成功{$result['success']}个";
    }
    public function execPushBaiduFast(): string
    {
        $pages = db('page')->where('status', 1)->where('is_pushed_fast', 0)->where('is_pushed_normal', 0)->select();
        if (empty($pages)) return '无待推送页面';
        $siteUrl = site('site_url') ?: __HOST__;
        $urls = array_map(fn($p) => $siteUrl . '/keyword/' . $p['url_path'] . '.html', $pages);
        $result = baidu_push('fast', $urls);
        if (($result['success'] ?? 0) > 0 && ($result['fail'] ?? 0) == 0) {
            $pageIds = array_column($pages, 'id');
            db('page')->where('id', 'in', $pageIds)->setField('is_pushed_fast', 1);
        } elseif (($result['success'] ?? 0) > 0) {
            return "部分快速推送成功{$result['success']}个，失败{$result['fail']}个，下次将重试";
        }
        return "快速推送" . count($pages) . "个页面，成功{$result['success']}个";
    }
    public function execSitemap(): string
    {
        if (!cache('sitemap_dirty')) {
            return '无变更跳过';
        }
        $pages = db('page')->where('status', 1)->order('update_time DESC')->field('url_path,update_time')->select();
        $siteUrl = site('site_url') ?: __HOST__;
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= "  <url>\n    <loc>" . htmlspecialchars($siteUrl) . "/</loc>\n    <changefreq>daily</changefreq>\n    <priority>1.0</priority>\n  </url>\n";
        foreach ($pages as $page) {
            if (empty($page['url_path']) || !preg_match('/^[a-zA-Z0-9\x7f-\xff\-_]+$/', $page['url_path'])) continue;
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($siteUrl . "/keyword/" . $page['url_path'] . ".html") . "</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d', $page['update_time']) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';
        $publicPath = ROOT_PATH . '/public/';
        $tmpFile = $publicPath . 'sitemap.xml.tmp';
        file_put_contents($tmpFile, $xml);
        rename($tmpFile, $publicPath . 'sitemap.xml');
        cache('sitemap_dirty', null);
        return "生成sitemap，共" . count($pages) . "条";
    }
    public function execClearCache(): string
    {
        cache_clear();
        cache('sitemap_dirty', true);
        $expire = time() - 86400 * 30;
        $deletedLogs = db('task_log')->where('create_time', '<', $expire)->delete() ?: 0;
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
        return json_encode([
            'cache_clear' => ['ok' => true, 'count' => 1],
            'log_clean' => ['ok' => true, 'deleted' => $deletedLogs],
            'has_page_fix' => ['ok' => true, 'fixed' => $fixed],
        ], JSON_UNESCAPED_UNICODE);
    }
}
```
引用方式：`model('common@task')`
**设计说明**：
- `execute()`：任务执行主入口，通过 `type` 动态派发到 `exec{Type}()` 方法；自动创建日志记录，成功/失败时更新任务统计字段；并发控制通过检查 `status=0` 的日志实现
- `recoverTimeout()`：将超过 `timeout + 60` 秒仍在执行中的日志标记为失败
- `execGeneratePage()`：每次最多生成 3 个未生成页面的关键词
- `execPushBaidu()`：推送 `is_pushed_normal=0` 的已发布页面
- `execPushBaiduFast()`：推送 `is_pushed_fast=0 AND is_pushed_normal=0` 的新发布页面
- `execSitemap()`：**仅生成 sitemap.xml**，不生成 robots.txt；通过 `sitemap_dirty` 缓存标记判断是否有变更，无变更则跳过；使用临时文件+rename 保证原子写入
- `execClearCache()`：清理缓存 + 清理30天前日志 + 修复 `has_page` 不一致，result 分段记录

### 12.6 TaskLog
文件：`app/common/model/TaskLog.php`
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
引用方式：`model('common@task_log')`

---

## 13. 控制器
> **产出**：5 个后台控制器（AiConfig、Prompt、Keyword、Page、Task）+ 1 个前台控制器修改（Index）+ 1 个前台控制器新增（Cron）
> **依赖**：步骤 1（表存在）、步骤 2（函数可用）、步骤 3（模型可用）

### 13.1 后台 — AiConfig（extends Cp）
文件：`app/admin/controller/AiConfig.php`
AI配置管理控制器。
```php
<?php
declare(strict_types=1);
namespace app\admin\controller;
class AiConfig extends Cp
{
    protected string $model = 'common@ai_config';
    public function test()
    {
        $id = input('id', 0, 'intval');
        if (!$id) $this->_json(400, '参数错误');
        $lockKey = 'ai_test_' . $id;
        if (cache('?' . $lockKey)) {
            $this->_json(429, '请3秒后再试');
        }
        cache($lockKey, 1, 3);
        $result = ai_chat('Hi', $id);
        if ($result['ok']) {
            $this->_json(200, '连接成功');
        } else {
            $this->_json(400, '连接失败: ' . $result['error']);
        }
    }
}
```
**说明**:
- 继承 Cp，标准 CRUD 由基类提供（index/add/edit/del/state）
- `test()` 使用 3 秒限频缓存，调用 `ai_chat('Hi', $id)` 测试指定配置连接
- API Key 明文存储，`test()` 无需 decrypt

### 13.2 后台 — Prompt（extends Cp）
文件：`app/admin/controller/Prompt.php`
提示词模板管理控制器。
```php
<?php
declare(strict_types=1);
namespace app\admin\controller;
class Prompt extends Cp
{
    protected string $model = 'common@prompt';
}
```
**说明**:
- 继承 Cp，标准 CRUD 由基类提供（index/add/edit/del/state）

### 13.3 后台 — Keyword（extends Cp）
文件：`app/admin/controller/Keyword.php`
```php
<?php
declare(strict_types=1);
namespace app\admin\controller;
class Keyword extends Cp
{
    protected string $model = 'common@keyword';
    public function del(string $ids)
    {
        $idArr = ids_filter($ids, true);
        if (!$idArr) $this->error('请选择ID');
        $count = 0;
        foreach ($idArr as $id) {
            $hasPage = db('page')->where('keyword_id', $id)->count();
            if ($hasPage > 0) continue;
            $kw = db('keyword')->where('id', $id)->where('status', 0)->find();
            if (!$kw) continue;
            $tmp = model($this->model)->find($id);
            if ($tmp) {
                $ok = pdo()->trans(function () use ($tmp) {
                    $res = $tmp->del();
                    if (!$res) throw new \Exception('删除失败');
                });
                if ($ok) $count++;
            }
        }
        $this->_jump(
            ['删除成功', '删除失败，已生成页面或未停用的关键词不可删除'],
            $count > 0,
            $this->jumpUrl
        );
    }
    protected function _where(): array
    {
        $where = [];
        $word = input('word', '', 'clear_html');
        if (!empty($word)) $where[] = ['word', 'like', '%' . $word . '%'];
        $source = input('source', '', 'clear_html');
        if (!empty($source)) $where['source'] = $source;
        $hasPage = input('has_page', -1, 'intval');
        if ($hasPage >= 0) $where['has_page'] = $hasPage;
        return $where;
    }
    public function batchToggle()
    {
        $ids = input('ids/a', []);
        $status = input('status', 0, 'intval');
        if (empty($ids)) $this->_json(400, '请选择关键词');
        $r = db('keyword')->where('id', 'in', $ids)->setField('status', $status);
        $this->_json($r ? 200 : 400, $r ? '操作成功' : '操作失败');
    }
    public function importCsv()
    {
        if ($this->isPost()) {
            $file = input('csv_file', '', 'clear_html');
            if (empty($file) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
                $this->_json(400, '文件名无效');
            }
            $filePath = ROOT_PATH . '/public/uploads/' . $file;
            if (!is_file($filePath)) $this->_json(400, '文件不存在');
            if (filesize($filePath) > 2 * 1024 * 1024) $this->_json(400, '文件大小不能超过2MB');
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            $allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
            if (!in_array($mime, $allowedMimes)) {
                $this->_json(400, '文件类型无效，仅支持CSV格式');
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
    public function exportCsv()
    {
        $list = db('keyword')->order('id DESC')->select();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=keywords_' . date('YmdHis') . '.csv');
        $fp = fopen('php://output', 'w');
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, ['ID', '关键词', '拼音', '来源', '状态', '是否生成页面']);
        foreach ($list as $row) {
            $word = $row['word'];
            if (in_array($word[0], ['=', '+', '-', '@', "\t"])) {
                $word = "'" . $word;
            }
            fputcsv($fp, [
                $row['id'], $word, $row['pinyin'],
                $row['source'], $row['status'], $row['has_page']
            ]);
        }
        fclose($fp);
        exit;
    }
    public function expand()
    {
        $id = input('id', 0, 'intval');
        $direction = input('direction', '', 'clear_html');
        if (!in_array($direction, ['related', 'question', 'longtail', 'commercial'])) {
            $this->_json(400, '拓词方向无效');
        }
        $m = model($this->model);
        $words = $m->expandByAi($id, $direction);
        if (empty($words)) {
            $errors = $m->getError();
            if (!empty($errors)) {
                $this->_json(400, current($errors));
            }
        }
        $existing = db('keyword')->column('word');
        $existingLower = array_map('mb_strtolower', $existing);
        $result = [];
        foreach ($words as $w) {
            $result[] = [
                'word'   => $w,
                'exists' => in_array(mb_strtolower(trim($w)), $existingLower),
            ];
        }
        $this->_json(200, 'ok', $result);
    }
}
```
**说明**:
- `del()` 仅删除 `status=0` 且无关联页面（直接查 page 表确认 `has_page=0`）的关键词
- `batchToggle()` 批量启用/停用关键词
- `importCsv()` / `exportCsv()` CSV 导入导出，限制 2MB / 1000 行，CSV 公式注入防护（等号等触发字符前加单引号转义），MIME 类型验证（仅允许 text/csv、text/plain、application/csv、application/vnd.ms-excel）
- `expand()` AI 拓词，返回候选词 + 已存在标记（`exists` 字段）

### 13.4 后台 — Page（extends Cp）
文件：`app/admin/controller/Page.php`
```php
<?php
declare(strict_types=1);
namespace app\admin\controller;
class Page extends Cp
{
    protected string $model = 'common@page';
    protected array $stateList = ['status' => ['草稿', '已发布']];
    protected function _where(): array
    {
        $where = [];
        $keyword = input('keyword', '', 'clear_html');
        if (!empty($keyword)) $where[] = ['title', 'like', '%' . $keyword . '%'];
        $status = input('status', -1, 'intval');
        if ($status >= 0) $where['status'] = $status;
        return $where;
    }
    public function index()
    {
        $where = $this->_where();
        $list = db('page')->where($where)->order('id DESC')->paginate($this->limit);
        foreach ($list->data as &$item) {
            if (!empty($item['keyword_id'])) {
                $kw = db('keyword')->where('id', $item['keyword_id'])->find();
                $item['keyword_word'] = $kw['word'] ?? '';
            } else {
                $item['keyword_word'] = '';
            }
        }
        return view()->with('list', $list);
    }
    public function edit(int $id, array $req)
    {
        $page = db('page')->find($id);
        if (!$page) $this->error('页面不存在');
        if ($this->isPost()) {
            if ($page['status'] == 1 && isset($req['url_path']) && $req['url_path'] !== $page['url_path']) {
                $this->_jump([null, '已发布页面不可修改URL路径'], false, $this->jumpUrl);
            }
            $m = model($this->model)->find($id);
            $r = $m->save($req);
            if ($r) {
                model('common@page')->_clearViewCache($page['url_path']);
                if ($page['status'] == 1) {
                    cache('sitemap_dirty', true);
                }
            }
            $errors = $m->getError();
            $error = $errors ? current($errors) : '修改失败';
            $this->_jump(['修改成功', $error], $r, $this->jumpUrl);
        }
        return view()->with('vo', $page);
    }
    public function state(string $ids, string $params)
    {
        $idArr = ids_filter($ids, true);
        foreach ($idArr as $id) {
            $page = db('page')->find($id);
            if ($page) model('common@page')->_clearViewCache($page['url_path']);
        }
        parent::state($ids, $params);
    }
    protected function _after_state(string $field, string $value, array $ids): void
    {
        if ($field === 'status' && $value == 1) {
            db('page')->where('id', 'in', $ids)->update([
                'is_pushed_normal' => 0,
                'is_pushed_fast'   => 0,
            ]);
        }
        if ($field === 'status') {
            cache('sitemap_dirty', true);
        }
    }
    public function preview()
    {
        $id = input('id', 0, 'intval');
        if (!$id) $this->_json(400, '参数错误');
        $page = db('page')->find($id);
        if (!$page) $this->_json(400, '页面不存在');
        return view()->with('content', $page['content']);
    }
    public function generate()
    {
        $keywordId = input('keyword_id', 0, 'intval');
        if (!$keywordId) $this->_json(400, '参数错误');
        $m = model('common@page');
        $r = $m->generatePage($keywordId);
        $errors = $m->getError();
        $error = $errors ? current($errors) : '生成失败';
        $this->_json($r ? 200 : 400, $r ? '生成成功' : $error);
    }
    public function batchGenerate()
    {
        $ids = input('ids/a', []);
        if (empty($ids)) $this->_json(400, '请选择关键词');
        $lockFile = RUNTIME_PATH . '/cache/seo_lock_generate.lock';
        $fp = fopen($lockFile, 'w+');
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            $this->_json(409, '已有生成任务执行中，请稍后重试');
        }
        $keywords = db('keyword')
            ->where('id', 'in', $ids)
            ->where('has_page', 0)
            ->where('status', 1)
            ->limit(3)
            ->column('id');
        try {
            $count = 0;
            $fail = 0;
            foreach ($keywords as $kid) {
                $r = model('common@page')->generatePage((int)$kid);
                $r ? $count++ : $fail++;
            }
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
        $this->_json(200, "生成{$count}个页面，失败{$fail}个");
    }
    public function rewrite()
    {
        $id = input('id', 0, 'intval');
        if (!$id) $this->_json(400, '参数错误');
        $m = model($this->model);
        $r = $m->rewriteByAi($id);
        $errors = $m->getError();
        $error = $errors ? current($errors) : '重写失败';
        $this->_json($r ? 200 : 400, $r ? '重写成功，状态已改为草稿' : $error);
    }
}
```
**说明**:
- `edit()` 已发布页面（status=1）拒绝修改 url_path；内容修改后若页面已发布则设置 `sitemap_dirty=true`
- `state()` 发布时重置 `is_pushed_normal` 和 `is_pushed_fast` 为 0，清除前台缓存，设置 sitemap 脏标记
- `preview()` 预览页面内容（新窗口打开）
- `generate()` 单个关键词生成页面（调用 `model('common@page')->generatePage()`）
- `batchGenerate()` flock 互斥批量生成，单次最多3个关键词
- `rewrite()` AI 重写，重写前弹出确认框，重写后状态回退草稿，推送标记重置

### 13.5 后台 — Task（extends Cp）
文件：`app/admin/controller/Task.php`
```php
<?php
declare(strict_types=1);
namespace app\admin\controller;
class Task extends Cp
{
    protected string $model = 'common@task';
    protected array $stateList = ['status' => ['禁用', '启用']];
    public function del(string $ids)
    {
        $idArr = ids_filter($ids, true);
        if (!$idArr) $this->error('请选择ID');
        $count = 0;
        foreach ($idArr as $id) {
            $hasLogs = db('task_log')->where('task_id', $id)->count();
            if ($hasLogs > 0) continue;
            $tmp = model($this->model)->find($id);
            if ($tmp) {
                $ok = pdo()->trans(function () use ($tmp) {
                    $res = $tmp->del();
                    if (!$res) throw new \Exception('删除失败');
                });
                if ($ok) $count++;
            }
        }
        $this->_jump(
            ['删除成功', '删除失败，存在执行日志的任务不可删除'],
            $count > 0,
            $this->jumpUrl
        );
    }
    public function state(string $ids, string $params)
    {
        $idArr = ids_filter($ids, true);
        if (empty($idArr)) $this->error('请选择ID');
        [$field, $value] = name_parse($params, 'status', '-');
        $map = [[$field, '<>', $value]];
        if (count($idArr) == 1) {
            $map['id'] = current($idArr);
        } else {
            $map[] = ['id', 'in', $idArr];
        }
        $r = db('task')->where($map)->setField($field, $value);
        if ($r) {
            model($this->model)->widgetReload();
        }
        $this->_jump(['操作成功', '操作失败'], $r, $this->jumpUrl);
    }
    public function edit(int $id, array $req)
    {
        $task = db('task')->find($id);
        if (!$task) $this->error('任务不存在');
        if ($this->isPost()) {
            unset($req['type']);
            $r = model($this->model)->find($id)->save($req);
            $this->_jump(['修改成功', '修改失败'], $r, $this->jumpUrl);
        }
        return view()->with('vo', $task);
    }
    public function run()
    {
        $id = input('id', 0, 'intval');
        if (!$id) $this->_json(400, '参数错误');
        $task = db('task')->find($id);
        if (!$task) $this->_json(400, '任务不存在');
        $fp = null;
        if ($task['type'] === 'generate_page') {
            $lockFile = RUNTIME_PATH . '/cache/seo_lock_generate.lock';
            $fp = fopen($lockFile, 'w+');
            if (!flock($fp, LOCK_EX | LOCK_NB)) {
                fclose($fp);
                $this->_json(409, '已有生成任务执行中');
            }
        }
        try {
            $r = model($this->model)->execute($id);
        } finally {
            if ($fp) {
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
        $this->_json($r ? 200 : 400, $r ? '执行成功' : '执行失败');
    }
    public function log()
    {
        $taskId = input('task_id', 0, 'intval');
        $where = [];
        if ($taskId) $where['task_id'] = $taskId;
        $list = db('task_log')->where($where)->order('id DESC')->paginate(20);
        return view()->with('list', $list);
    }
}
```
**说明**:
- 继承 Cp，`model = 'common@task'`（Task 模型在 `common/model/` 下，使用 `common@task` 引用）
- 覆盖 `del()`：检查是否有执行日志，有则拒绝删除；不使用 Cp 默认的 `status=0` 过滤
- 覆盖 `state()`：自定义状态切换逻辑，直接 `setField` 而非使用 Cp 默认行为
- 覆盖 `edit()`：禁止修改任务类型（`unset $req['type']`）
- `run()` 手动触发单个任务执行；若任务类型为 `generate_page`，先获取 flock 锁（与 Cron 共享 `seo_lock_generate.lock`），锁失败返回 HTTP 409"已有生成任务执行中"
- `log()` 查看执行日志，支持按 task_id 筛选

### 13.6 前台 — Index（修改现有控制器）
文件：`app/index/controller/Index.php`
在现有 Index 控制器中追加 `dispatch()` 方法：
```php
<?php
declare(strict_types=1);
namespace app\index\controller;
class Index
{
    public function index()
    {
        $pages = cache_make('index_pages', function() {
            return db('page')
                ->where('status', 1)
                ->field('id,title,description,create_time')
                ->order('create_time DESC')
                ->limit(10)
                ->select();
        }, 3600);
        return view()->with('pages', $pages);
    }
    public function dispatch(string $path = '')
    {
        if (empty($path) || mb_strlen($path) > 200) {
            halt('页面不存在', 404);
        }
        $page = cache_make('view/' . md5('index/index/dispatch?path=' . $path), function() use ($path) {
            return model('common@page')->findByPath($path);
        }, 3600);
        if (!$page) {
            halt('页面不存在', 404);
        }
        db('page')->where('id', $page['id'])->setInc('view_count');
        $relatedPages = db('page')
            ->where('status', 1)
            ->where('id', '<>', $page['id'])
            ->field('url_path,title')
            ->order('create_time DESC')
            ->limit(5)
            ->select();
        $siteUrl = site('site_url') ?: __HOST__;
        $relatedHtml = '';
        if (!empty($relatedPages)) {
            $relatedHtml .= '<div class="related-pages"><h3>相关推荐</h3><ul>';
            foreach ($relatedPages as $rp) {
                $relatedHtml .= '<li><a href="' . $siteUrl . '/keyword/'
                    . htmlspecialchars($rp['url_path']) . '.html">'
                    . htmlspecialchars($rp['title']) . '</a></li>';
            }
            $relatedHtml .= '</ul></div>';
        }
        $content = $page['content'];
        $canonicalUrl = $siteUrl . '/keyword/' . $page['url_path'] . '.html';
        $canonicalTag = '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl) . '">';
        if (stripos($content, '</head>') !== false) {
            $content = preg_replace('/<\/head>/i', $canonicalTag . "\n</head>", $content, 1);
        } elseif (stripos($content, '<html') !== false) {
            $content = preg_replace('/(<html[^>]*>)/i', '$1<head>' . $canonicalTag . '</head>', $content, 1);
        } else {
            $content = $canonicalTag . "\n" . $content;
        }
        if (!empty($relatedHtml)) {
            if (stripos($content, '</body>') !== false) {
                $content = str_ireplace('</body>', $relatedHtml . "\n</body>", $content);
            } else {
                $content .= $relatedHtml;
            }
        }
        header("Content-Security-Policy: default-src 'none'; style-src 'self' 'unsafe-inline'; script-src 'none'; img-src 'self' data: https:; font-src 'self' https:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; connect-src 'none'");
        echo $content;
    }
}
```
**说明**:
- `dispatch(string $path)` 查询 page 表 `status=1` 的记录，不存在返回 404；`$path` 参数由路由正则捕获组通过框架参数注入（见 §14.1 参数传递机制）
- 输出 CSP 安全头（禁止脚本执行）
- 输出 Canonical URL（`<link rel="canonical">`），注入到 `<head>` 区域
- 原子递增 `view_count`（`setInc`）
- 追加相关推荐（5 条同站已发布页面链接），插入到 `</body>` 前
- 从数据库读取 content 字段直接输出，无单独模板
- 页面数据缓存 1 小时（`cache_make`）
- `index()` 展示最新 10 条已发布页面（按 create_time 倒序），缓存 1 小时

### 13.7 前台 — Cron（use Jump，独立控制器）
文件：`app/index/controller/Cron.php`
```php
<?php
declare(strict_types=1);
namespace app\index\controller;
use xphp\core\Jump;
class Cron
{
    use Jump;
    protected bool $isApi = true;
    public function index(string $key = '')
    {
        $cronKey = site('cron_key');
        if (empty($key) || $key !== $cronKey) {
            $this->_json(403, '密钥无效');
        }
        $allowedIps = site('cron_allowed_ips');
        if (empty($allowedIps)) {
            $this->_json(403, '未配置允许IP，默认拒绝所有');
        }
        $ipList = array_map('trim', explode(',', $allowedIps));
        if (!in_array(get_ip(), $ipList)) {
            $this->_json(403, 'IP不允许');
        }
        $rateKey = 'cron_rate';
        if (cache('?' . $rateKey)) {
            $this->_json(429, '请求过于频繁');
        }
        cache($rateKey, 1, 60);
        $lockFile = RUNTIME_PATH . '/cache/seo_lock_generate.lock';
        set_time_limit(300);
        model('common@task')->recoverTimeout();
        $tasks = db('task')->where('status', 1)->select();
        foreach ($tasks as $task) {
            $fp = null;
            if ($task['type'] === 'generate_page') {
                $fp = fopen($lockFile, 'w+');
                if (!flock($fp, LOCK_EX | LOCK_NB)) {
                    fclose($fp);
                    $fp = null;
                    model('common@task_log')->save([
                        'task_id' => $task['id'],
                        'status' => 2,
                        'start_time' => time(),
                        'end_time' => time(),
                        'duration' => 0,
                        'result' => '因其他生成任务执行中而跳过',
                    ]);
                    continue;
                }
            }
            try {
                model('common@task')->execute($task['id']);
            } finally {
                if ($fp) { flock($fp, LOCK_UN); fclose($fp); }
            }
        }
        $this->_json(200, '执行完成');
    }
}
```
**说明**:
- 独立控制器，`use Jump`，不继承 Cp
- 方法名为 `index()`，路由 `/cron/{key}` 指向 `cron/index`
- 密钥验证：从 `site('cron_key')` 读取，比对 URL 参数
- IP 白名单：从 `site('cron_allowed_ips')` 读取，为空时默认拒绝所有 HTTP 触发
- 60 秒限频：缓存标记
- flock 锁：使用 `seo_lock_generate.lock`（与手动批量生成共享同一锁文件），防止并发执行；获取锁失败时：手动操作（Page 控制器 `batchGenerate()` 或 Task 控制器 `run()`）返回 HTTP 409"已有生成任务执行中"，Cron 触发记录"因其他生成任务执行中而跳过"并返回 HTTP 200
- 执行所有 `status=1` 的启用任务
- 使用 `site()` 读取配置（config 表变更通过 `syncConfigFile()` 自动同步）

---

## 14. 路由配置

### 14.1 前台路由
文件：`route/index.php`
在现有路由数组中追加：
```php
<?php
return [
    'keyword/([a-zA-Z\x7f-\xff0-9-%\+]+)' => 'index/dispatch/path/${1}',
    'cron/([a-zA-Z0-9\-_]+)'               => 'cron/index/key/${1}',
];
```
| 路由 | 参数别名 | 正则 | 指向 | 说明 |
|------|----------|------|------|------|
| `keyword/:path` | `:path` | `[a-zA-Z\x7f-\xff0-9-%\+]+` | `index/dispatch` | 落地页分发，支持中文 URL |
| `cron/:key` | `:key` | `[a-zA-Z0-9\-_]+` | `cron/index` | 定时触发，限制密钥格式为字母数字及连字符下划线 |
框架 `url_clear_suffix => ['.html']` 自动去除 `.html` 后缀，因此 `/keyword/seo-optimization.html` 实际匹配 `keyword/seo-optimization`。
**参数传递机制**: 路由正则捕获组通过框架参数注入传递给控制器方法。例如路由 `'keyword/([a-zA-Z\x7f-\xff0-9-%\+]+)' => 'index/dispatch/path/${1}'` 中，`${1}` 为正则第一个捕获组，框架将其作为 `dispatch()` 方法的 `$path` 参数注入。

### 14.2 后台路由
文件：`route/admin.php`
在现有路由数组中追加：
```php
'ai_config/:string' => 'ai_config/$1',
'prompt/:string'    => 'prompt/$1',
'keyword/:string'   => 'keyword/$1',
'page/:string'      => 'page/$1',
'task/:string'      => 'task/$1',
```
| 路由 | 指向控制器 | 说明 |
|------|-----------|------|
| `ai_config/:string` | AiConfig | AI配置管理 |
| `prompt/:string` | Prompt | 提示词模板管理 |
| `keyword/:string` | Keyword | 关键词管理 |
| `page/:string` | Page | 落地页管理 |
| `task/:string` | Task | 定时任务管理 |

---

## 15. 视图模板

### 15.1 后台视图（5个视图目录，12个文件）

#### AI配置模块
| 文件 | 说明 |
|------|------|
| `app/admin/view/ai_config/index.html` | AI配置列表（统计卡片3列+列表+Modal表单+厂商下拉自动填充+测试连接按钮） |
| `app/admin/view/ai_config/_form.html` | AI配置 Modal 表单：名称+厂商预设下拉+协议类型+API地址+API Key+模型+温度+最大Token+重试配置+SSL验证+状态 |

#### 提示词模板模块
| 文件 | 说明 |
|------|------|
| `app/admin/view/prompt/index.html` | 提示词模板列表：类型筛选+方向筛选+Modal表单 |
| `app/admin/view/prompt/_form.html` | 提示词 Modal 表单：名称+类型（落地页/拓词）+方向（拓词时显示4选项）+内容textarea+状态 |

#### 关键词管理模块
| 文件 | 说明 |
|------|------|
| `app/admin/view/keyword/index.html` | 关键词管理：搜索+筛选（来源/是否有页面）+工具栏（批量启停/CSV导入/CSV导出）+AI拓词弹窗（选择方向→候选词列表+已存在标记+勾选导入） |
| `app/admin/view/keyword/_form.html` | 关键词 Modal 表单：关键词文本（自动生成拼音）+来源+状态 |

#### 落地页管理模块
| 文件 | 说明 |
|------|------|
| `app/admin/view/page/index.html` | 落地页管理：筛选（标题/状态）+列表+新增Modal+AI重写按钮（loading+确认框）+批量生成（flock互斥，单次最多3个）+状态切换+预览链接 |
| `app/admin/view/page/_form.html` | 页面编辑：上方表单（标题+关键词+URL路径+SEO元数据）+下方textarea代码编辑区（等宽字体，最小高度500px）；已发布url_path只读；AI重写按钮（确认框） |
| `app/admin/view/page/preview.html` | 页面预览：新窗口打开，仅输出 `{$content|raw}`，不使用后台布局 |

#### 定时任务模块
| 文件 | 说明 |
|------|------|
| `app/admin/view/task/index.html` | 定时任务：列表（名称+类型+频率+最后执行状态+累计统计）+Modal表单（名称+频率描述+超时时间+启用状态）+手动执行按钮+日志链接 |
| `app/admin/view/task/_form.html` | 任务 Modal 表单：名称+类型（新增时可选）+频率描述+超时时间+状态 |
| `app/admin/view/task/log.html` | 任务日志：列表（任务名+状态+耗时+开始时间）+失败项红色标记+result JSON 折叠展开 |
**后台视图开发规范**:
1. 必须包含完整 lyear 布局（`_head.html` → `lyear-preloader` → `lyear-layout-web` → `sidebar.html` → `_header.html` → `<main>` → `footer.html`）
2. 分页用 `{$list->links()|raw}`，判断空用 `{empty $list:}`
3. 模板修改后清除 `runtime/admin/view/` 编译缓存
4. JS 代码使用 `{literal}` 包裹
厂商预设数据与第 4.1.1 节厂商预设清单相同，JS 硬编码在 ai_config/index.html 的 {literal} 块中。

### 15.2 前台视图（1个修改）
| 文件 | 说明 |
|------|------|
| `template/default/index/index.html` | 首页改造：在公告栏下方追加卡片式页面列表（标题+摘要+时间，最多10条，不分页） |
落地页不使用独立模板，从数据库读取 content 字段直接输出。

---

## 16. 修改现有文件

### 16.1 `app/common.php`
追加 7 个函数（详见第 6 节）

### 16.2 `route/index.php`
追加 keyword 和 cron 路由（详见第 14.1 节）

### 16.3 `route/admin.php`
追加后台路由配置（详见第 14.2 节）

### 16.4 `app/index/controller/Index.php`
追加 `dispatch(string $path)` 方法 + 修改 `index()` 方法（完整代码见第 13.6 节）

### 16.5 `template/default/index/index.html`
在公告栏下方追加卡片式页面列表（标题+摘要+时间，最多10条，不分页）。页面发布/下线/删除时清除 `cache('index_pages', null)`。

### 16.6 `public/robots.txt`
创建静态 robots.txt 文件（Sitemap 任务仅生成 sitemap.xml，robots.txt 为静态文件）：
```
User-agent: *
Allow: /keyword/
Disallow: /admin/
Disallow: /cron/
Sitemap: {site_url}/sitemap.xml
```
> `{site_url}` 需在部署时替换为实际站点 URL。

### 16.7 `app/common/model/AiConfig.php`
无需额外修改（见第 12.1 节）。

### 16.8 `app/common/model/Task.php`
无需额外修改（见第 12.5 节）。

### 16.9 `app/admin/controller/Index.php`（仪表盘）
> **注意**: 后台 Index 控制器是独立类，不继承 Cp，**未使用 Jump trait**。通过声明 `protected string $middleware = 'cp_auth'` 绑定后台认证中间件（框架自动读取 `$middleware` 属性，不依赖 Cp 继承）。
在现有 `index()` 方法中追加 SEO 统计数据（`cache_make('seo_stats', function() use (...) { return $stats; }, 300)`），保留原系统信息。新增私有方法 `getPageTrend()`、`getKeywordSource()`、`getPageStatus()` 供 Chart.js 使用。
同时追加 cron_key 自动生成逻辑和系统就绪检查：
```php
$cronKey = site('cron_key');
if (empty($cronKey)) {
    pdo()->trans(function () {
        $existing = db('config')->where('config_key', 'cron_key')->value('config_value');
        if (!empty($existing)) return;
        $newKey = bin2hex(random_bytes(32));
        db('config')->where('config_key', 'cron_key')->update(['config_value' => $newKey]);
    });
}
```
**系统就绪检查项**:
- AI 配置：至少 1 个 `status=1` 的配置
- 提示词模板：落地页类型至少 1 个 `status=1` 的模板
- 站点配置：`site_name` 和 `site_url` 非空
不满足时顶部显示黄色横幅提示，引导用户完成配置。

### 16.10 `app/admin/view/index/index.html`（仪表盘视图）
修改现有内容：
- 替换4列统计卡片为：关键词总数 / 已生成页面 / 页面总访问 / AI调用次数
- 追加系统就绪检查横幅（黄色，条件不满足时显示）
- 追加3个 Chart.js 图表（7天页面生成趋势折线图 / 关键词来源饼图 / 页面状态环形图）
- 系统信息改为底部折叠面板（Bootstrap Collapse），默认收起
- JS 初始化代码使用 `{literal}` 包裹

---

## 17. 首次使用流程
1. **配置 AI 引擎** — 进入后台 → AI引擎 → 添加 AI 配置（至少1个），填写 API 地址/Key/模型后测试连接
2. **配置提示词模板** — 进入 AI引擎 → 提示词模板，5条默认模板已内置，可按需编辑内容
3. **配置站点信息** — 进入后台 → 网站配置，填写网站名称、网站URL、百度站点域名和推送 Token（如需百度推送）
4. **添加关键词并生成页面** — 进入后台 → 关键词管理 → 添加关键词 → 批量生成页面（生成结果为草稿，需手动发布）
5. **配置定时任务** — 进入后台 → 定时任务 → 启用所需任务，配置 crontab: `* * * * * curl -s https://yourdomain.com/cron/{cron_key} > /dev/null 2>&1`

---

## 18. 部署安全要求
以下安全配置在 Nginx 层实现，不属于应用代码：
**HTTPS 强制**:
```nginx
server {
    listen 80;
    server_name example.com;
    return 301 https://$server_name$request_uri;
}
```
**安全响应头**:
```nginx
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```
**隐藏版本信息**:
- Nginx: `server_tokens off;`
- PHP: `expose_php = Off`
**上传目录安全**:
```nginx
location /uploads/ {
    location ~ \.php$ {
        deny all;
    }
}
```
**生产环境**: `APP_DEBUG=false`，禁止显示详细错误信息，所有异常统一由框架捕获返回通用错误页面。

