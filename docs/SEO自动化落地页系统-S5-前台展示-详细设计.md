
# S5：前台展示 - 详细设计文档

&gt; **文档版本**: v1.0
&gt; **创建日期**: 2026-05-19
&gt; **模块编号**: S5
&gt; **模块名称**: 前台展示

---

## 1. 功能需求分析

### 1.1 功能概述
实现前台落地页展示、首页改造、Sitemap 生成等功能。

### 1.2 依赖关系
- **依赖 S2**: 关键词管理
- **依赖 S3**: 页面管理

---

## 2. 数据模型设计

### 2.1 使用的模型
- **Keyword**: model('common@keyword') - 读取关键词信息
- **Page**: model('common@page') - 读取页面内容

---

## 3. 控制器接口设计

### 3.1 Index 控制器（修改）
- **现有**: app/index/controller/Index.php
- **修改内容**:
  - index(): 首页改造，展示最新页面
  - dispatch(): 落地页路由分发

### 3.2 新增方法

#### dispatch()
- **路由**: /keyword/{pinyin}.html
- **功能**:
  - 根据 pinyin 查询 page 表
  - 仅返回 status=1 的页面
  - 输出 CSP 头
  - 输出 Canonical URL
  - 原子递增 view_count
  - 页面缓存 1 小时
  - 追加相关推荐（5 条同站页面）

#### index()
- **功能**:
  - 展示最新 10 条已发布页面
  - 按 create_time 倒序
  - 缓存 1 小时
  - 页面发布/下线/删除时清除首页缓存

---

## 4. 视图模板设计

### 4.1 文件清单
| 视图 | 路径 | 说明 |
|------|------|------|
| 首页 | template/default/index/index.html | 改造现有首页 |
| robots.txt | public/robots.txt | 修改现有文件 |

### 4.2 robots.txt 内容
```
User-agent: *
Allow: /keyword/
Disallow: /admin/
Disallow: /cron/
Sitemap: {site_url}/sitemap.xml
```

---

## 5. 业务流程说明

### 5.1 落地页访问流程
1. 访问 /keyword/{pinyin}.html
2. 根据 pinyin 查询 page 表，url_path = pinyin
3. 检查 status=1，否则返回 404
4. 输出 CSP 头（禁止脚本执行）
5. 输出 Canonical URL
6. 原子递增 view_count
7. 输出页面 content
8. 追加相关推荐（5 条页面链接，在 &lt;/body&gt; 前）

### 5.2 Sitemap 生成
- **文件**: public/sitemap.xml
- **内容**: 所有 status=1 的页面
- **格式**: 标准 sitemap XML
- **脏标记**: 页面变更时设置 sitemap_dirty=true
- **任务**: 由 S4 的 sitemap 任务定时生成

---

## 6. 依赖关系说明

| 依赖 | 说明 |
|------|------|
| S2 关键词管理 | 关键词信息 |
| S3 页面管理 | 页面内容 |
| Keyword 模型 | model('common@keyword') |
| Page 模型 | model('common@page') |

---

## 7. 安全考虑

- 前台仅查询 status=1 的页面
- 草稿/不存在的页面返回 404
- CSP 头禁止脚本执行
- robots.txt 禁止爬虫访问后台和 cron
- view_count 原子递增

---

## 8. 文件清单

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| 修改控制器 | app/index/controller/Index.php | 追加 dispatch() 方法 |
| 修改视图 | template/default/index/index.html | 首页改造 |
| 修改文件 | public/robots.txt | SEO 配置 |
| 修改路由 | route/index.php | 追加落地页路由 |

---

## 9. 验证方法

- [ ] 落地页 URL 可正常访问
- [ ] 首页展示最新页面列表
- [ ] Sitemap.xml 可正常生成
- [ ] Canonical URL 正确输出
- [ ] CSP 头正确输出
- [ ] view_count 正确递增
- [ ] robots.txt 配置正确
- [ ] 草稿页返回 404

---

## 10. 参考文档

- SEO 自动化落地页系统-统一需求文档.md（第 4.5 章、第 4.6 章、第 7 章）

