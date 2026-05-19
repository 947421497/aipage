
# S4：定时任务管理 - 详细设计文档

&gt; **文档版本**: v1.0
&gt; **创建日期**: 2026-05-19
&gt; **模块编号**: S4
&gt; **模块名称**: 定时任务管理

---

## 1. 功能需求分析

### 1.1 功能概述
管理定时任务和 Cron 触发，支持批量生成页面、百度推送、Sitemap 生成等功能。

### 1.2 依赖关系
- **依赖 S0**: 公共函数（ai_chat()、baidu_push()、flock 锁
- **依赖 S3**: 页面管理

---

## 2. 数据模型设计

### 2.1 Task 模型
- **表名**: `xphp_task`
- **字段**: id, name, type, cron_desc, timeout, last_run_time, last_run_status, last_run_msg, last_success_time, total_run, total_fail, consecutive_fail, status, create_time, update_time
- **索引**: uk_type, idx_status
- **模型位置**: app/common/model/Task.php

### 2.2 TaskLog 模型
- **表名**: `xphp_task_log`
- **字段**: id, task_id, status, result, start_time, end_time, duration, create_time, update_time
- **索引**: idx_task_id, idx_task_id_start_time, idx_start_time, idx_create_time
- **模型位置**: app/common/model/TaskLog.php

---

## 3. 控制器接口设计

### 3.1 Task 控制器（后台）
- **继承**: app\admin\controller\Cp
- **模型**: admin@task
- **功能**:
  - index(): 列表
  - add(): 新增
  - edit(): 编辑
  - del(): 删除
  - state(): 状态切换
  - log(): 执行日志
  - run(): 手动触发

### 3.2 Cron 控制器（前台）
- **独立**: 不继承 Cp
- **功能**:
  - index(): HTTP 触发入口
  - 密钥验证
  - 60 秒限频
  - flock 锁
  - 执行任务

---

## 4. 视图模板设计

### 4.1 文件清单
| 视图 | 路径 | 说明 |
|------|------|
| 列表页 | app/admin/view/task/index.html | 任务列表 |
| 表单页 | app/admin/view/task/_form.html | 任务表单 |
| 日志页 | app/admin/view/task/log.html | 执行日志 |

---

## 5. 业务流程说明

### 5.1 内置任务类型

| 类型 | 说明 |
|------|------|
| generate_page | 批量生成页面（每次最多 3 个） |
| push_baidu | 百度普通收录推送 |
| push_baidu_fast | 百度快速收录推送 |
| sitemap | 生成 sitemap.xml |
| clear_cache | 清理缓存、清理日志、修复 has_page |

### 5.2 Cron 触发流程
1. 访问 /cron/{cron_key}
2. 验证 cron_key 验证
3. IP 白名单验证（空则拒绝所有）
4. 60 秒限频
5. 获取 flock 锁（runtime/cache/seo_lock_{type}.lock）
6. 超时恢复：标记超时任务为失败
7. 执行所有启用的任务
8. 释放锁
9. 返回执行结果

### 5.3 并发控制
- **flock 锁**: 手动批量生成和 Cron 共享同一锁
- **获取锁失败**:
  - 手动：返回提示信息
  - Cron：记录跳过，标记成功

---

## 6. 依赖关系说明

| 依赖 | 说明 |
|------|------|
| S0 公共函数 | ai_chat()、baidu_push()、flock 锁 |
| S3 页面管理 | 页面生成 |
| Task 模型 | model('common@task') |
| TaskLog 模型 | model('common@task_log') |

---

## 7. 安全考虑

- Cron 触发：密钥验证（≥32 位）+ 60 秒限频 + IP 白名单
- flock 锁：防止并发执行
- 任务超时：set_time_limit(task.timeout)
- 日志清理：30 天前日志自动清理
- 删除限制：存在执行日志的任务不可删除

---

## 8. 文件清单

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| 创建模型 | app/common/model/Task.php | 任务模型 |
| 创建模型 | app/common/model/TaskLog.php | 任务日志模型 |
| 创建控制器 | app/admin/controller/Task.php | 任务控制器 |
| 创建控制器 | app/index/controller/Cron.php | Cron 触发控制器 |
| 创建视图 | app/admin/view/task/index.html | 列表页 |
| 创建视图 | app/admin/view/task/_form.html | 表单页 |
| 创建视图 | app/admin/view/task/log.html | 日志页 |
| 修改路由 | route/admin.php | 追加后台路由 |
| 修改路由 | route/index.php | 追加前台路由 |

---

## 9. 验证方法

- [ ] 后台可访问任务管理页面
- [ ] 可配置定时任务
- [ ] Cron 触发接口正常
- [ ] 任务执行日志正常
- [ ] flock 锁机制正常
- [ ] 百度推送功能正常
- [ ] Sitemap 生成正常

---

## 10. 参考文档

- SEO 自动化落地页系统-统一需求文档.md（第 4.4 章、第 5 章）

