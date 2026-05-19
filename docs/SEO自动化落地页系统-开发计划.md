
# SEO 自动化落地页系统 - 开发计划

&gt; **文档版本**: v1.0
&gt; **创建日期**: 2026-05-19
&gt; **适用框架**: XPHP v6.1.1 | PHP &gt;= 8.1

---

## 1. 项目概述

### 1.1 项目目标
构建一套基于 AI 的自动化落地页生成系统，支持关键词驱动、自动生成符合 SEO 规范的 HTML 落地页，提供完整的内容管理、百度推送、定时任务等能力。

### 1.2 技术架构
- **分层架构**: 后台（admin）+ 公共层（common）+ 前台（index）
- **框架**: XPHP v6.1.1
- **PHP 版本**: &gt;= 8.1
- **数据库**: MySQL (InnoDB)

---

## 2. 架构设计

### 2.1 文件组织
```
app/
├── common.php                          ← 公共函数（直接追加）
├── common/model/                       ← 跨应用模型
│   ├── AiConfig.php
│   ├── Prompt.php
│   ├── Keyword.php
│   ├── Page.php
│   ├── Task.php
│   └── TaskLog.php
├── admin/controller/                    ← 后台控制器
│   ├── AiConfig.php
│   ├── Prompt.php
│   ├── Keyword.php
│   ├── Page.php
│   └── Task.php
├── index/controller/                    ← 前台控制器
│   ├── Index.php (修改)
│   └── Cron.php
└── admin/view/                          ← 后台视图
    ├── ai_config/
    ├── prompt/
    ├── keyword/
    ├── page/
    └── task/

backup/bak_all_initialize/              ← SQL 文件（追加到现有文件）
├── 1_drop_table.sql                     （追加6张表DROP）
├── 2_create_table.sql                   （追加6张表CREATE）
├── 3_insert_xphp_config_part1.sql       （追加7条配置）
└── 3_insert_xphp_menu_part1.sql         （追加4个菜单）
```

---

## 3. 模块拆分总览

### 3.1 模块依赖关系
```
S0 (数据库与公共函数)
  ├──→ S1 (AI配置管理)
  │       └──→ S2 (关键词管理)
  │               └──→ S3 (页面生成管理)
  │                       ├──→ S4 (定时任务管理)
  │                       └──→ S5 (前台展示)
```

### 3.2 模块说明

| 模块编号 | 模块名称 | 功能说明 |
|----------|----------|----------|
| S0 | 数据库与公共函数 | SQL 文件 + 7个公共函数 |
| S1 | AI配置管理 | AI引擎配置 + Prompt模板管理 |
| S2 | 关键词管理 | 关键词 CRUD + AI拓词 + CSV导入导出 |
| S3 | 页面生成管理 | 页面生成 + 状态管理 + 预览 |
| S4 | 定时任务管理 | Cron触发 + 任务日志 + 批量生成 |
| S5 | 前台展示 | 落地页路由 + 首页改造 + Sitemap |

---

## 4. 开发顺序与里程碑

### 4.1 开发顺序

| 顺序 | 模块 | 预计复杂度 | 关键依赖 | 可交付成果 |
|------|------|-----------|---------|----------|
| 1 | S0 数据库与公共函数 | 低 | 无 | 6张新表 + 7个函数 |
| 2 | S1 AI配置管理 | 中 | S0 | 后台可配置AI + Prompt |
| 3 | S2 关键词管理 | 中 | S0, S1 | 后台可管理关键词 |
| 4 | S3 页面生成管理 | 高 | S0, S1, S2 | 后台可生成页面 |
| 5 | S4 定时任务管理 | 高 | S0, S3 | Cron可自动生成 |
| 6 | S5 前台展示 | 中 | S2, S3 | 落地页可正常访问 |

### 4.2 里程碑验收标准

#### S0 完成检查清单
- [ ] `backup/bak_all_initialize/` 目录下 4 个 SQL 文件成功追加内容
- [ ] `app/common.php` 末尾成功追加 7 个公共函数
- [ ] 函数语法检查通过（`php -l app/common.php`）
- [ ] 执行 SQL 能成功创建 6 张新表

#### S1 完成检查清单
- [ ] `app/common/model/` 下 2 个模型文件创建成功
- [ ] `app/admin/controller/` 下 2 个控制器创建成功
- [ ] `app/admin/view/` 下 4 个视图文件创建成功
- [ ] `route/admin.php` 成功追加路由配置
- [ ] 后台可访问 AI 配置页面
- [ ] 后台可添加、编辑、删除 Prompt 模板

#### S2 完成检查清单
- [ ] `app/common/model/Keyword.php` 创建成功
- [ ] `app/admin/controller/Keyword.php` 创建成功
- [ ] `app/admin/view/keyword/` 下视图文件创建成功
- [ ] 后台可访问关键词管理页面
- [ ] 后台可添加、编辑、删除关键词
- [ ] 拼音 URL 自动生成正常

#### S3 完成检查清单
- [ ] `app/common/model/Page.php` 创建成功
- [ ] `app/admin/controller/Page.php` 创建成功
- [ ] `app/admin/view/page/` 下视图文件创建成功
- [ ] 后台可访问页面管理页面
- [ ] 可手动生成单页面
- [ ] 页面状态转换正常（草稿 ←→ 发布）

#### S4 完成检查清单
- [ ] `app/common/model/Task.php` 和 `TaskLog.php` 创建成功
- [ ] `app/admin/controller/Task.php` 创建成功
- [ ] `app/index/controller/Cron.php` 创建成功
- [ ] `app/admin/view/task/` 下视图文件创建成功
- [ ] 后台可访问任务管理页面
- [ ] Cron 接口可正常触发
- [ ] 任务执行日志记录正常

#### S5 完成检查清单
- [ ] `app/index/controller/Index.php` 成功修改
- [ ] `template/default/index/index.html` 成功修改
- [ ] `route/index.php` 成功追加路由配置
- [ ] `public/robots.txt` 成功修改
- [ ] 落地页 URL 可正常访问
- [ ] 首页展示最新页面列表

---

## 5. 技术决策记录

| 决策项 | 方案 | 理由 |
|--------|------|------|
| 公共函数位置 | 直接追加到 `app/common.php` | 遵循 XPHP 现有架构 |
| SQL 文件位置 | 追加到 `backup/bak_all_initialize/` 现有文件 | 遵循框架备份规范，不创建单独目录 |
| API Key 存储 | 明文存储 | 按用户要求，最简单方式 |
| 锁文件位置 | `runtime/cache/seo_lock_{type}.lock` | 统一存储位置 |
| 表前缀 | `xphp_` | 遵循框架默认 |
| 首页改造 | 现有模板直接修改 | 不新增文件 |
| 测试数据 | 后期自行添加 | 开发阶段不提供 |
| 落地页渲染 | 数据库读取，动态渲染 | 无单独模板，AI 生成直接输出 |

---

## 6. 参考文档

| 文档 | 位置 | 说明 |
|------|------|------|
| 统一需求文档 | `SEO自动化落地页系统-统一需求文档.md` | 完整需求说明 |
| S0 详细设计 | `SEO自动化落地页系统-S0-数据库与公共函数-详细设计.md` | 数据库与函数详情 |
| S1 详细设计 | `SEO自动化落地页系统-S1-AI配置管理-详细设计.md` | AI配置模块详情 |
| S2 详细设计 | `SEO自动化落地页系统-S2-关键词管理-详细设计.md` | 关键词模块详情 |
| S3 详细设计 | `SEO自动化落地页系统-S3-页面生成管理-详细设计.md` | 页面模块详情 |
| S4 详细设计 | `SEO自动化落地页系统-S4-定时任务管理-详细设计.md` | 任务模块详情 |
| S5 详细设计 | `SEO自动化落地页系统-S5-前台展示-详细设计.md` | 前台模块详情 |
| 开发提示词 | `SEO自动化落地页系统-开发提示词.md` | 各模块开发提示词 |

---

## 7. 重要注意事项

### 7.1 框架约束
- `model()` 函数分隔符是 `@`，不是 `.`（`model('common@keyword')`）
- PHP 8.1+ 子类方法签名必须完全兼容父类（LSP 严格检查）
- 后台视图必须包含完整 lyear 布局
- 模板修改后必须清除 `runtime/admin/view/` 编译缓存
- `APP_DEBUG=false` 时错误被完全隐藏

### 7.2 安全要求
- 前台只查询 `status=1` 的页面，草稿不可访问
- 落地页输出 CSP 头，禁止脚本执行
- Cron 触发需要密钥验证 + 60秒限频 + flock锁
- 批量生成并发控制（flock锁，单次最多3个）

### 7.3 SEO 要求
- 落地页 URL 格式：`/keyword/{pinyin}.html`
- 每个落地页必须输出 Canonical URL
- robots.txt 禁止后台访问，开放 keyword 目录
- Sitemap.xml 动态生成

