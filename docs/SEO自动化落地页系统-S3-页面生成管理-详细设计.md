
# S3：页面生成管理 - 详细设计文档

&gt; **文档版本**: v1.0
&gt; **创建日期**: 2026-05-19
&gt; **模块编号**: S3
&gt; **模块名称**: 页面生成管理

---

## 1. 功能需求分析

### 1.1 功能概述
管理落地页生成和状态控制，支持 AI 生成、预览、状态切换等功能。

### 1.2 依赖关系
- **依赖 S0**: 公共函数（ai_chat()、filter_landing_html()、parse_seo_meta()、render_prompt()）
- **依赖 S1**: AI 配置和 Prompt 模板
- **依赖 S2**: 关键词管理

---

## 2. 数据模型设计

### 2.1 Page 模型
- **表名**: `xphp_page`
- **字段**: id, keyword_id, url_path, title, keywords, description, content, ai_config_id, prompt_id, status, view_count, is_pushed_normal, is_pushed_fast, create_time, update_time
- **索引**: idx_keyword_id, idx_status, idx_status_is_pushed_normal, uk_url_path
- **模型位置**: app/common/model/Page.php

---

## 3. 控制器接口设计

### 3.1 Page 控制器
- **继承**: app\admin\controller\Cp
- **模型**: admin@page
- **功能**:
  - index(): 列表
  - add(): 新增
  - edit(): 编辑
  - del(): 删除
  - state(): 状态切换
  - preview(): 预览
  - generate(): 手动生成
  - rewrite(): AI 重写

---

## 4. 视图模板设计

### 4.1 文件清单
| 视图 | 路径 | 说明 |
|------|------|------|
| 列表页 | app/admin/view/page/index.html | 页面列表 |
| 表单页 | app/admin/view/page/_form.html | 页面表单 |
| 预览页 | app/admin/view/page/preview.html | 页面预览 |

---

## 5. 业务流程说明

### 5.1 页面生成流程
1. 选择关键词（has_page=0）
2. 调用 render_prompt() 渲染 page 类型模板
3. 调用 ai_chat() 生成内容
4. 检查内容长度（&lt;500 视为失败）
5. 调用 parse_seo_meta() 解析 SEO 元数据
6. 调用 filter_landing_html() 安全过滤
7. 自动包裹 HTML（不含 &lt;html&gt; 的话）
8. 同一事务内：
   - 保存 page 到数据库（status=0）
   - 更新 keyword.has_page=1
9. 提交事务

### 5.2 状态机
```
[无页面] → AI生成 → 草稿(0)
   ↑                       ↓
   ↓ AI重写 ←  ←  ←  ←  发布(1)
                       ↓
                     下线 → 草稿
```

状态切换规则：
- **草稿 → 发布**: 重置 is_pushed_normal、is_pushed_fast，锁定 url_path
- **发布 → 下线**: 可下线，url_path 保持
- **草稿 → 删除**: 可直接删除
- **发布 → 删除**: 需先下线

---

## 6. 依赖关系说明

| 依赖 | 说明 |
|------|------|
| S0 公共函数 | ai_chat()、filter_landing_html()、parse_seo_meta()、render_prompt() |
| S1 AI 配置 | ai_chat() 使用 |
| S1 Prompt 模板 | page 类型用于生成 |
| S2 关键词 | 关键词关联 |
| Page 模型 | model('common@page') |

---

## 7. 安全考虑

- 落地页内容经过 filter_landing_html() 9 步安全过滤
- 发布后 url_path 不可修改（前端 readonly + 后端强制拒绝）
- 页面增删改时自动清除前台缓存
- 内容质量控制：&lt;500 字符视为失败

---

## 8. 文件清单

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| 创建模型 | app/common/model/Page.php | 页面模型 |
| 创建控制器 | app/admin/controller/Page.php | 页面控制器 |
| 创建视图 | app/admin/view/page/index.html | 列表页 |
| 创建视图 | app/admin/view/page/_form.html | 表单页 |
| 创建视图 | app/admin/view/page/preview.html | 预览页 |
| 修改路由 | route/admin.php | 追加路由 |

---

## 9. 验证方法

- [ ] 后台可访问页面管理页面
- [ ] 可手动生成单页面
- [ ] 页面状态转换正常
- [ ] 页面预览功能正常
- [ ] SEO 元数据解析正常
- [ ] has_page 一致性保证

---

## 10. 参考文档

- SEO 自动化落地页系统-统一需求文档.md（第 4.3 章、第 5 章）

