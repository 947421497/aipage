
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

详细设计参见 [S0 详细设计文档](SEO自动化落地页系统-S0-数据库与公共函数-详细设计.md)。

- **SQL 文件**: 追加到 `backup/bak_all_initialize/` 现有文件末尾，每条用 `-- <fen> --` 分隔
- **公共函数**: 追加到 `app/common.php` 末尾，共 7 个函数（ai_chat、filter_landing_html、to_pinyin、generate_url_path、baidu_push、parse_seo_meta、render_prompt）
- **依赖框架函数**: db()、cache()、encrypt()、decrypt() 等

### 注意事项
1. **SQL 格式**: 严格遵循现有 `backup/bak_all_initialize/` 目录下的格式，追加到现有文件末尾
2. **公共函数位置**: 必须追加在 `app/common.php` **文件末尾**
3. **依赖框架函数**: 使用框架提供的 `db()`、`cache()`、`encrypt()`、`decrypt()` 等函数
4. **安全性**: API Key 明文存储（按用户要求）
5. **flock 锁**: 锁文件路径为 `RUNTIME_PATH . '/cache/seo_lock_{type}.lock'`

### 验证方法

参见 [S0 详细设计文档 · 第5章 验证方法](SEO自动化落地页系统-S0-数据库与公共函数-详细设计.md)

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

详细设计参见 [S1 详细设计文档](SEO自动化落地页系统-S1-AI配置管理-详细设计.md)。

- **AiConfig**: 模型引用 `common@ai_config`，支持 11 种厂商预设，连接测试 3 秒限频
- **Prompt**: 模型引用 `common@prompt`，类型分 page/expand，同 type+direction 仅一个可激活
- **控制器**: 继承 Cp 基类，$model 设为 `common@ai_config` / `common@prompt`

### 注意事项
1. **模型位置**: 放在 `app/common/model/`，使用 `model('common@ai_config')` 引用
2. **Cp 基类**: 继承 Cp 基类，方法签名必须完全兼容
3. **视图布局**: 必须包含完整的 lyear 布局（_head.html、sidebar.html、_header.html、footer.html）
4. **API Key**: 明文存储，更新时空值保留原值（Ollama 除外）
5. **厂商预设**: 提供 11 种预设，选择后自动填充

### 验证方法

参见 [S1 详细设计文档 · 第9章 验证方法](SEO自动化落地页系统-S1-AI配置管理-详细设计.md)

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

详细设计参见 [S2 详细设计文档](SEO自动化落地页系统-S2-关键词管理-详细设计.md)。

- **Keyword**: 模型引用 `common@keyword`，添加时自动调用 to_pinyin() 和 generate_url_path()
- **删除限制**: 只能删除 status=0 且 has_page=0 的关键词
- **CSV 安全**: 2MB 限制、最多 1000 行、MIME 验证、随机重命名、公式注入防护
- **批量生成**: flock 互斥，单次最多 3 个

### 注意事项
1. **拼音生成**: 添加关键词时自动调用 `to_pinyin()` 和 `generate_url_path()`
2. **冲突处理**: `generate_url_path()` 自动处理拼音冲突，追加数字后缀
3. **删除检查**: 控制器层检查关联完整性（框架 _before_delete 不检查 errors）
4. **CSV 安全**: 文件大小限制、MIME 类型验证、随机重命名、公式注入防护
5. **拓词提示**: 使用 prompt 模板的 `expand` 类型

### 验证方法

参见 [S2 详细设计文档 · 第9章 验证方法](SEO自动化落地页系统-S2-关键词管理-详细设计.md)

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

详细设计参见 [S3 详细设计文档](SEO自动化落地页系统-S3-页面生成管理-详细设计.md)。

- **Page**: 模型引用 `common@page`，url_path 唯一，发布后不可修改
- **事务一致性**: 页面创建/删除时同一事务内同步更新 keyword.has_page
- **内容质量**: 生成内容 < 500 字符视为失败
- **状态机**: 草稿(0) ↔ 发布(1)，发布→删除需先下线

### 注意事项
1. **URL 不可变性**: 发布后 `url_path` 后端强制拒绝修改
2. **事务一致性**: 页面创建/删除时，同一事务内同步更新 `keyword.has_page`
3. **内容质量**: 落地页生成内容 &lt; 500 字符视为失败（调用方检查）
4. **提示词注入**: Prompt 模板中使用 `&lt;keyword&gt;{keyword}&lt;/keyword&gt;` 标签包裹
5. **缓存清理**: 页面增删改时自动清除前台缓存

### 验证方法

参见 [S3 详细设计文档 · 第9章 验证方法](SEO自动化落地页系统-S3-页面生成管理-详细设计.md)

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

详细设计参见 [S4 详细设计文档](SEO自动化落地页系统-S4-定时任务管理-详细设计.md)。

- **Task**: 模型引用 `common@task`，type 字段 UNIQUE，每种任务全局仅一条
- **TaskLog**: 模型引用 `common@task_log`
- **Cron 触发**: `/cron/{key}`，密钥验证 + 60秒限频 + flock锁 + IP 白名单
- **并发控制**: flock 锁文件 `runtime/cache/seo_lock_{type}.lock`

### 注意事项
1. **任务唯一性**: `type` 字段 UNIQUE，每种任务全局仅一条
2. **超时控制**: `set_time_limit(task.timeout)`
3. **日志清理**: 30 天前日志自动清理
4. **Sitemap 脏标记**: 页面发布/下线/删除时设置缓存 `sitemap_dirty=true`
5. **手动触发**: 后台支持手动触发单任务执行

### 验证方法

参见 [S4 详细设计文档 · 第9章 验证方法](SEO自动化落地页系统-S4-定时任务管理-详细设计.md)

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

详细设计参见 [S5 详细设计文档](SEO自动化落地页系统-S5-前台展示-详细设计.md)。

- **落地页路由**: `/keyword/{pinyin}.html`，仅查询 status=1 的页面
- **SEO 优化**: Canonical URL + CSP 头 + view_count 原子递增 + 页面缓存 1 小时
- **Sitemap**: 脏标记策略，由 S4 的 sitemap 任务定时生成

### 注意事项
1. **落地页渲染**: 从数据库读取 `content` 字段直接输出，无单独模板
2. **相关推荐**: 页面底部自动追加 5 条同站已发布页面链接
3. **CSP 头**: 必须输出，禁止脚本执行
4. **404 处理**: 页面不存在或 status=0 时，返回 HTTP 404 状态码 + 简单提示页
5. **首页缓存**: 页面发布/下线/删除时清除首页缓存

### 验证方法

参见 [S5 详细设计文档 · 第9章 验证方法](SEO自动化落地页系统-S5-前台展示-详细设计.md)

