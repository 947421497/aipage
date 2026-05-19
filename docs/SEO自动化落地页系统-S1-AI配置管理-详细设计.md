
# S1：AI配置管理 - 详细设计文档

&gt; **文档版本**: v1.0
&gt; **创建日期**: 2026-05-19
&gt; **模块编号**: S1
&gt; **模块名称**: AI配置管理

---

## 1. 功能需求分析

### 1.1 功能概述
管理 AI 配置和提示词模板，为 AI 调用提供支持。
- AI 配置管理：CRUD、厂商预设、连接测试、轮询容错
- Prompt 模板管理：CRUD、类型区分、激活机制、变量支持

### 1.2 依赖关系
- **依赖 S0**: 公共函数（ai_chat()、render_prompt()）

---

## 2. 数据模型设计

### 2.1 AiConfig 模型
- **表名**: `xphp_ai_config`
- **字段**: id, name, api_type, api_url, api_key, model, max_tokens, temperature, max_retries, retry_interval, verify_ssl, call_count, status, create_time, update_time
- **索引**: idx_status
- **模型位置**: app/common/model/AiConfig.php

### 2.2 Prompt 模型
- **表名**: `xphp_prompt`
- **字段**: id, name, type, direction, content, is_active, status, create_time, update_time
- **索引**: idx_type, idx_type_direction, idx_is_active
- **模型位置**: app/common/model/Prompt.php

---

## 3. 控制器接口设计

### 3.1 AiConfig 控制器
- **继承**: app\admin\controller\Cp
- **模型**: admin@ai_config
- **功能**:
  - index(): 列表
  - add(): 新增
  - edit(): 编辑
  - del(): 删除
  - state(): 状态切换
  - test(): 连接测试

### 3.2 Prompt 控制器
- **继承**: app\admin\controller\Cp
- **模型**: admin@prompt
- **功能**:
  - index(): 列表
  - add(): 新增
  - edit(): 编辑
  - del(): 删除
  - state(): 状态切换
  - activate(): 激活模板

---

## 4. 视图模板设计

### 4.1 文件清单
| 视图 | 路径 | 说明 |
|------|------|------|
| 列表页 | app/admin/view/ai_config/index.html | AI 配置列表 |
| 表单页 | app/admin/view/ai_config/_form.html | AI 配置表单 |
| 列表页 | app/admin/view/prompt/index.html | Prompt 列表 |
| 表单页 | app/admin/view/prompt/_form.html | Prompt 表单 |

### 4.2 视图规范
- 必须包含完整 lyear 布局（_head.html、sidebar.html、_header.html、footer.html）
- 使用框架提供的表单组件
- 状态切换使用 ajaxConfirm

---

## 5. 业务流程说明

### 5.1 AI 配置管理流程
1. 选择厂商预设（11 种可选）
2. 自动填充协议类型、API URL、默认模型、SSL 验证设置
3. 填写 API Key（Ollama 可选）
4. 保存配置
5. 可选：测试连接（3 秒限频）

### 5.2 Prompt 激活机制
1. 选择要激活的模板
2. 同一事务内：同 type+direction 的所有模板 is_active 设为 0
3. 当前模板 is_active 设为 1
4. 提交事务

---

## 6. 依赖关系说明

| 依赖 | 说明 |
|------|------|
| S0 公共函数 | ai_chat()、render_prompt() |
| AiConfig 模型 | model('common@ai_config') |
| Prompt 模型 | model('common@prompt') |

---

## 7. 安全考虑

- API Key 明文存储（按用户要求）
- 连接测试 3 秒限频缓存
- 更新 AI 配置时，空 API Key 保留原值（Ollama 除外）
- 厂商预设提供 11 种，可扩展

---

## 8. 文件清单

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| 创建模型 | app/common/model/AiConfig.php | AI 配置模型 |
| 创建模型 | app/common/model/Prompt.php | Prompt 模型 |
| 创建控制器 | app/admin/controller/AiConfig.php | AI 配置控制器 |
| 创建控制器 | app/admin/controller/Prompt.php | Prompt 控制器 |
| 创建视图 | app/admin/view/ai_config/index.html | 列表页 |
| 创建视图 | app/admin/view/ai_config/_form.html | 表单页 |
| 创建视图 | app/admin/view/prompt/index.html | 列表页 |
| 创建视图 | app/admin/view/prompt/_form.html | 表单页 |
| 修改路由 | route/admin.php | 追加路由 |

---

## 9. 验证方法

- [ ] 后台可访问 AI 配置页面
- [ ] 可添加、编辑、删除 AI 配置
- [ ] 连接测试功能正常
- [ ] 可管理 Prompt 模板
- [ ] Prompt 激活机制正常

---

## 10. 参考文档

- SEO 自动化落地页系统-统一需求文档.md（第 4.1 章、第 5 章）

