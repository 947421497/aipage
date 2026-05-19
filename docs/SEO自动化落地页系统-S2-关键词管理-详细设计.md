
# S2：关键词管理 - 详细设计文档

&gt; **文档版本**: v1.0
&gt; **创建日期**: 2026-05-19
&gt; **模块编号**: S2
&gt; **模块名称**: 关键词管理

---

## 1. 功能需求分析

### 1.1 功能概述
管理关键词，支持 AI 拓词、CSV 导入导出、批量生成等功能。

### 1.2 依赖关系
- **依赖 S0**: 公共函数（to_pinyin()、generate_url_path()、ai_chat()）
- **依赖 S1**: AI 配置和 Prompt 模板

---

## 2. 数据模型设计

### 2.1 Keyword 模型
- **表名**: `xphp_keyword`
- **字段**: id, word, pinyin, source, group_id, status, has_page, create_time, update_time
- **索引**: uk_word, idx_pinyin, idx_status, idx_has_page, idx_source
- **模型位置**: app/common/model/Keyword.php

---

## 3. 控制器接口设计

### 3.1 Keyword 控制器
- **继承**: app\admin\controller\Cp
- **模型**: admin@keyword
- **功能**:
  - index(): 列表
  - add(): 新增
  - edit(): 编辑
  - del(): 删除
  - state(): 状态切换
  - batchToggle(): 批量启用/停用
  - importCsv(): CSV 导入
  - exportCsv(): CSV 导出
  - expand(): AI 拓词
  - batchGenerate(): 批量生成

---

## 4. 视图模板设计

### 4.1 文件清单
| 视图 | 路径 | 说明 |
|------|------|------|
| 列表页 | app/admin/view/keyword/index.html | 关键词列表 |
| 表单页 | app/admin/view/keyword/_form.html | 关键词表单 |

---

## 5. 业务流程说明

### 5.1 关键词添加流程
1. 输入关键词 word
2. 自动调用 to_pinyin() 转换
3. 调用 generate_url_path() 生成唯一 pinyin
4. 保存到数据库
5. has_page 初始值为 0

### 5.2 AI 拓词流程
1. 选择关键词
2. 调用 render_prompt() 渲染 expand 类型模板
3. 调用 ai_chat() 生成相关关键词
4. 展示候选词，标记已存在的词
5. 用户勾选要导入的词
6. 批量保存

### 5.3 CSV 导入导出
- **导入**: 2MB 限制，最多 1000 行
- **公式注入防护**: 移除等号开头
- **安全检查**: MIME 类型验证，随机重命名

---

## 6. 依赖关系说明

| 依赖 | 说明 |
|------|------|
| S0 公共函数 | to_pinyin()、generate_url_path()、ai_chat() |
| S1 AI 配置 | ai_chat() 使用 |
| S1 Prompt 模板 | expand 类型用于拓词 |
| Keyword 模型 | model('common@keyword') |

---

## 7. 安全考虑

- CSV 导入：2MB 限制，最多 1000 行
- 公式注入防护
- 删除限制：仅可删除 status=0 且 has_page=0 的关键词
- 关联完整性检查在控制器层执行

---

## 8. 文件清单

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| 创建模型 | app/common/model/Keyword.php | 关键词模型 |
| 创建控制器 | app/admin/controller/Keyword.php | 关键词控制器 |
| 创建视图 | app/admin/view/keyword/index.html | 列表页 |
| 创建视图 | app/admin/view/keyword/_form.html | 表单页 |
| 修改路由 | route/admin.php | 追加路由 |

---

## 9. 验证方法

- [ ] 后台可访问关键词管理页面
- [ ] 可添加、编辑、删除关键词
- [ ] 拼音 URL 自动生成正常
- [ ] 拼音冲突处理正常
- [ ] AI 拓词功能正常
- [ ] CSV 导入导出功能正常

---

## 10. 参考文档

- SEO 自动化落地页系统-统一需求文档.md（第 4.2 章、第 5 章）

