
# SEO 自动化落地页系统 - 开发提示词

&gt; **文档版本**: v1.0
&gt; **创建日期**: 2026-05-19
&gt; **使用说明**: 每个模块开发前请先阅读对应模块的详细设计文档

---

## 前置说明

### 1. 开发顺序
请严格按照以下顺序开发：
- S0（数据库与公共函数）
- S1（AI配置管理）
- S2（关键词管理）
- S3（页面生成管理）
- S4（定时任务管理）
- S5（前台展示）

### 2. 参考文档优先级
1. **主参考**: `SEO自动化落地页系统-统一需求文档.md` - 完整需求说明
2. **模块详细设计**: `SEO自动化落地页系统-S{x}-{xxx}-详细设计.md` - 模块详情
3. **开发计划**: `SEO自动化落地页系统-开发计划.md` - 总体计划

---

## S0：数据库与公共函数 - 开发提示词

### 开发目标
完成数据库准备和7个公共函数，为后续模块提供基础支持。

### 需要创建/修改的文件

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| **修改文件** | `backup/bak_all_initialize/1_drop_table.sql` | 追加：6张表的 DROP |
| **修改文件** | `backup/bak_all_initialize/2_create_table.sql` | 追加：6张表的 CREATE |
| **修改文件** | `backup/bak_all_initialize/3_insert_xphp_config_part1.sql` | 追加：7条配置项 |
| **修改文件** | `backup/bak_all_initialize/3_insert_xphp_menu_part1.sql` | 追加：4个后台菜单 |
| **修改文件** | `app/common.php` | 追加：7个公共函数 |

### 关键功能说明

#### 1. SQL 文件
- **1_drop_table.sql**: 追加 6 张表的 DROP TABLE 语句，每条用 `-- <fen> --` 分隔
- **2_create_table.sql**: 追加 6 张表的 CREATE TABLE 语句，每条用 `-- <fen> --` 分隔
- **3_insert_xphp_config_part1.sql**: 追加 7 条 config 配置项
- **3_insert_xphp_menu_part1.sql**: 追加 4 个后台菜单

#### 2. 公共函数（app/common.php 末尾追加）

| 函数名 | 功能 |
|--------|------|
| `ai_chat()` | AI 调用统一入口（OpenAI/Anthropic/Ollama 协议） |
| `filter_landing_html()` | 落地页 HTML 安全过滤 + 结构规范化 |
| `to_pinyin()` | 中文转拼音，使用 `Transliterator` |
| `generate_url_path()` | 生成唯一的 URL 路径段，处理拼音冲突 |
| `baidu_push()` | 百度收录推送 |
| `parse_seo_meta()` | 从 AI 生成的 HTML 中解析 SEO 元数据 |
| `render_prompt()` | 提示词模板变量渲染 |

### 注意事项
1. **SQL 格式**: 严格遵循现有 `backup/bak_all_initialize/` 目录下的格式，追加到现有文件末尾
2. **公共函数位置**: 必须追加在 `app/common.php` **文件末尾**
3. **依赖框架函数**: 使用框架提供的 `db()`、`cache()`、`encrypt()`、`decrypt()` 等函数
4. **安全性**: API Key 明文存储（按用户要求）
5. **flock 锁**: 锁文件路径为 `RUNTIME_PATH . '/cache/seo_lock_{type}.lock'`

### 验证方法
- SQL 文件语法检查
- `php -l app/common.php` 语法检查
- 能成功执行 SQL 创建 6 张新表

---

## S1：AI配置管理 - 开发提示词

### 开发目标
完成 AI 配置管理和 Prompt 模板管理功能，支持 AI 调用配置和提示词管理。

### 需要创建/修改的文件

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| **创建模型** | `app/common/model/AiConfig.php` | AI 配置模型 |
| **创建模型** | `app/common/model/Prompt.php` | Prompt 模板模型 |
| **创建控制器** | `app/admin/controller/AiConfig.php` | AI 配置控制器（继承 Cp） |
| **创建控制器** | `app/admin/controller/Prompt.php` | Prompt 模板控制器（继承 Cp） |
| **创建视图** | `app/admin/view/ai_config/index.html` | AI 配置列表页 |
| **创建视图** | `app/admin/view/ai_config/_form.html` | AI 配置表单 |
| **创建视图** | `app/admin/view/prompt/index.html` | Prompt 列表页 |
| **创建视图** | `app/admin/view/prompt/_form.html` | Prompt 表单 |
| **修改路由** | `route/admin.php` | 追加路由配置 |

### 关键功能说明

#### 1. AiConfig 模型
- 表名：`ai_config`
- 支持：CRUD、状态切换、连接测试
- 厂商预设：11 种预设配置
- 连接测试：3秒限频缓存

#### 2. Prompt 模型
- 表名：`prompt`
- 类型：`page`（落地页）、`expand`（拓词）
- 激活机制：同类型同方向仅一个可激活
- 变量支持：`{keyword}`、`{site_name}`、`{site_url}`、`{date}`、`{time}`

#### 3. 控制器规范
- 继承 `app\admin\controller\Cp`
- `$model` 属性：`admin@ai_config`、`admin@prompt`
- 使用 `state()` 进行状态切换
- 使用 `del()` 进行删除（先停用）

### 注意事项
1. **模型位置**: 放在 `app/common/model/`，使用 `model('common@ai_config')` 引用
2. **Cp 基类**: 继承 Cp 基类，方法签名必须完全兼容
3. **视图布局**: 必须包含完整的 lyear 布局（_head.html、sidebar.html、_header.html、footer.html）
4. **API Key**: 明文存储，更新时空值保留原值（Ollama 除外）
5. **厂商预设**: 提供 11 种预设，选择后自动填充

### 验证方法
- 后台可访问 AI 配置页面
- 后台可添加、编辑、删除 Prompt 模板
- 连接测试功能正常
- 激活机制正常工作

---

## S2：关键词管理 - 开发提示词

### 开发目标
完成关键词管理功能，支持关键词 CRUD、AI 拓词、CSV 导入导出。

### 需要创建/修改的文件

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| **创建模型** | `app/common/model/Keyword.php` | 关键词模型 |
| **创建控制器** | `app/admin/controller/Keyword.php` | 关键词控制器（继承 Cp） |
| **创建视图** | `app/admin/view/keyword/index.html` | 关键词列表页 |
| **创建视图** | `app/admin/view/keyword/_form.html` | 关键词表单 |
| **修改路由** | `route/admin.php` | 追加路由配置 |

### 关键功能说明

#### 1. Keyword 模型
- 表名：`keyword`
- 字段：`word`、`pinyin`、`source`、`status`、`has_page`
- `word` 唯一，`pinyin` 唯一
- 添加时自动调用 `to_pinyin()` 和 `generate_url_path()`

#### 2. 关键词管理功能
- CRUD：增删改查
- 状态切换：批量启用/停用
- AI 拓词：调用 ai_chat() 生成相关关键词
- CSV 导入/导出：2MB 限制，最多 1000 行
- 批量生成：flock 互斥，单次最多 3 个

#### 3. 删除限制
- 只能删除 status=0 且 has_page=0 的关键词
- 有页面关联的关键词禁止删除

### 注意事项
1. **拼音生成**: 添加关键词时自动调用 `to_pinyin()` 和 `generate_url_path()`
2. **冲突处理**: `generate_url_path()` 自动处理拼音冲突，追加数字后缀
3. **删除检查**: 控制器层检查关联完整性（框架 _before_delete 不检查 errors）
4. **CSV 安全**: 文件大小限制、MIME 类型验证、随机重命名、公式注入防护
5. **拓词提示**: 使用 prompt 模板的 `expand` 类型

### 验证方法
- 后台可访问关键词管理页面
- 后台可添加、编辑、删除关键词
- 拼音 URL 自动生成正常
- 冲突处理正常
- AI 拓词功能正常

---

## S3：页面生成管理 - 开发提示词

### 开发目标
完成页面管理功能，支持 AI 生成页面、状态管理、预览等。

### 需要创建/修改的文件

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| **创建模型** | `app/common/model/Page.php` | 页面模型 |
| **创建控制器** | `app/admin/controller/Page.php` | 页面控制器（继承 Cp） |
| **创建视图** | `app/admin/view/page/index.html` | 页面列表页 |
| **创建视图** | `app/admin/view/page/_form.html` | 页面表单 |
| **创建视图** | `app/admin/view/page/preview.html` | 页面预览 |
| **修改路由** | `route/admin.php` | 追加路由配置 |

### 关键功能说明

#### 1. Page 模型
- 表名：`page`
- 字段：`keyword_id`、`url_path`、`title`、`keywords`、`description`、`content`、`status` 等
- 状态机：`0`（草稿）↔ `1`（已发布）
- `url_path` 唯一，发布后不可修改

#### 2. 页面生成流程
1. 选择关键词（has_page=0）
2. 调用 `render_prompt()` 渲染提示词模板
3. 调用 `ai_chat()` 生成内容
4. 调用 `parse_seo_meta()` 解析 SEO 元数据
5. 调用 `filter_landing_html()` 安全过滤
6. 存入数据库（状态=草稿）

#### 3. 状态切换
- **草稿 → 发布**: 重置 `is_pushed_normal`、`is_pushed_fast`
- **发布 → 下线**: `url_path` 不可修改，前端显示 readonly
- **草稿 → 删除**: 可直接删除
- **发布 → 删除**: 需先下线，再删除

### 注意事项
1. **URL 不可变性**: 发布后 `url_path` 后端强制拒绝修改
2. **事务一致性**: 页面创建/删除时，同一事务内同步更新 `keyword.has_page`
3. **内容质量**: 落地页生成内容 &lt; 500 字符视为失败（调用方检查）
4. **提示词注入**: Prompt 模板中使用 `&lt;keyword&gt;{keyword}&lt;/keyword&gt;` 标签包裹
5. **缓存清理**: 页面增删改时自动清除前台缓存

### 验证方法
- 后台可访问页面管理页面
- 可手动生成单页面
- 页面状态转换正常（草稿 ←→ 发布）
- 页面预览正常
- SEO 元数据解析正常

---

## S4：定时任务管理 - 开发提示词

### 开发目标
完成定时任务管理和 Cron 触发功能，支持自动生成页面、百度推送等。

### 需要创建/修改的文件

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| **创建模型** | `app/common/model/Task.php` | 任务模型 |
| **创建模型** | `app/common/model/TaskLog.php` | 任务日志模型 |
| **创建控制器** | `app/admin/controller/Task.php` | 任务控制器（继承 Cp） |
| **创建控制器** | `app/index/controller/Cron.php` | Cron 触发控制器 |
| **创建视图** | `app/admin/view/task/index.html` | 任务列表页 |
| **创建视图** | `app/admin/view/task/_form.html` | 任务表单 |
| **创建视图** | `app/admin/view/task/log.html` | 任务日志页 |
| **修改路由** | `route/admin.php` | 追加路由配置 |
| **修改路由** | `route/index.php` | 追加 Cron 路由配置 |

### 关键功能说明

#### 1. 内置任务类型
| 类型 | 说明 |
|------|------|
| `generate_page` | 批量生成页面（每次最多 3 个） |
| `push_baidu` | 百度普通收录推送 |
| `push_baidu_fast` | 百度快速收录推送 |
| `sitemap` | 生成 sitemap.xml |
| `clear_cache` | 清理缓存、清理 30 天前日志、修复 has_page 不一致 |

#### 2. Cron 触发方式
- **HTTP 触发**: `/cron/{key}`
- **安全性**: 密钥验证（≥32 位随机字符）+ 60 秒限频 + flock锁 + IP 白名单（空=拒绝所有）
- **超时恢复**: 每次检查超过 `timeout+60` 秒仍在执行的日志标记为失败

#### 3. 并发控制
- **flock 锁**: 手动批量生成和 Cron 共享同一锁文件 `runtime/cache/seo_lock_generate.lock`
- **获取锁失败**: 手动操作返回提示，Cron 记录跳过并标记成功

### 注意事项
1. **任务唯一性**: `type` 字段 UNIQUE，每种任务全局仅一条
2. **超时控制**: `set_time_limit(task.timeout)`
3. **日志清理**: 30 天前日志自动清理
4. **Sitemap 脏标记**: 页面发布/下线/删除时设置缓存 `sitemap_dirty=true`
5. **手动触发**: 后台支持手动触发单任务执行

### 验证方法
- 后台可访问任务管理页面
- Cron 接口可正常触发
- 任务执行日志记录正常
- flock 锁机制正常
- 百度推送功能正常

---

## S5：前台展示 - 开发提示词

### 开发目标
完成前台落地页展示、首页改造、Sitemap 等功能。

### 需要创建/修改的文件

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| **修改控制器** | `app/index/controller/Index.php` | 追加落地页路由处理 |
| **修改视图** | `template/default/index/index.html` | 首页改造 |
| **修改路由** | `route/index.php` | 追加落地页路由配置 |
| **修改文件** | `public/robots.txt` | SEO 配置 |

### 关键功能说明

#### 1. 落地页路由
- **URL 格式**: `/keyword/{pinyin}.html`
- **访问控制**: 仅查询 `status=1` 的页面，草稿/不存在返回 404
- **SEO 优化**:
  - 输出 Canonical URL
  - 输出 CSP 头（`default-src 'none'; style-src 'self' 'unsafe-inline'; script-src 'none'; ...`）
  - 访问时原子递增 `view_count`
  - 页面缓存 1 小时

#### 2. 首页改造
- 展示最新 10 条已发布页面（标题 + 描述 + 时间）
- 按 `create_time` 倒序
- 首页缓存 1 小时

#### 3. Sitemap.xml
- 生成 sitemap.xml 到 `public/sitemap.xml`
- 只含 `status=1` 的页面
- 绝对 URL，带最后修改时间
- 脏标记策略：页面变更时标记 `sitemap_dirty=true`，无变更则跳过

#### 4. robots.txt
```
User-agent: *
Allow: /keyword/
Disallow: /admin/
Disallow: /cron/
Sitemap: {site_url}/sitemap.xml
```

### 注意事项
1. **落地页渲染**: 从数据库读取 `content` 字段直接输出，无单独模板
2. **相关推荐**: 页面底部自动追加 5 条同站已发布页面链接
3. **CSP 头**: 必须输出，禁止脚本执行
4. **404 处理**: 页面不存在或 status=0 时，返回 HTTP 404 状态码 + 简单提示页
5. **首页缓存**: 页面发布/下线/删除时清除首页缓存

### 验证方法
- 落地页 URL 可正常访问
- 首页展示最新页面列表
- Sitemap.xml 可正常生成
- Canonical URL 正确输出
- CSP 头正确输出
- view_count 正确递增

