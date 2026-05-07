# 数据库迁移脚本：移除积分和余额字段

## 迁移说明

本脚本用于从 `xphp_user` 表中移除 `money`（余额）和 `score`（积分）字段。

### 迁移前准备

1. **备份数据库**：执行迁移前务必先备份数据库
2. **检查依赖**：确保没有业务代码依赖这两个字段
3. **通知用户**：如果是生产环境，提前通知相关用户

### 迁移脚本

#### MySQL 迁移语句

```sql
-- ========================================
-- 移除 xphp_user 表中的 money 和 score 字段
-- 执行前请确保已备份数据库！
-- ========================================

-- 1. 检查字段是否存在
-- DESCRIBE xphp_user;

-- 2. 移除 money 字段（余额）
ALTER TABLE `xphp_user` DROP COLUMN `money`;

-- 3. 移除 score 字段（积分）
ALTER TABLE `xphp_user` DROP COLUMN `score`;

-- 4. 验证修改结果
-- DESCRIBE xphp_user;
```

### 回滚脚本

如果需要回滚（恢复字段），执行以下脚本：

```sql
-- ========================================
-- 回滚脚本：恢复 money 和 score 字段
-- ========================================

-- 1. 添加 money 字段（余额）
ALTER TABLE `xphp_user` 
ADD COLUMN `money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '余额' AFTER `level`;

-- 2. 添加 score 字段（积分）
ALTER TABLE `xphp_user` 
ADD COLUMN `score` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '积分' AFTER `money`;
```

### 迁移验证

迁移完成后，执行以下验证：

```sql
-- 1. 查看表结构
DESCRIBE xphp_user;

-- 2. 确认字段已移除（应该返回空结果）
SELECT * FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'xphp_user' 
AND COLUMN_NAME IN ('money', 'score');

-- 3. 测试用户注册和登录功能
-- INSERT INTO xphp_user (username, password, nickname, create_time) VALUES ('test', '123', '测试', UNIX_TIMESTAMP());

-- 4. 测试用户查询
-- SELECT * FROM xphp_user WHERE username = 'test';
```

### PHP 代码兼容性

如果项目中仍有代码引用这两个字段，需要同步修改：

#### 需要检查的文件类型

1. **PHP 模型文件**：查找 `User::where('score', ...)` 等查询
2. **控制器文件**：查找 `request()->post('money')` 等数据获取
3. **视图文件**：查找 `{$user.money}` 等变量输出
4. **API 接口**：查找返回这两个字段的 JSON 响应

#### 建议的修改方式

如果控制器或模型中使用了这两个字段：

```php
// 旧代码（需要移除）
$user->money = 100;
$user->score += 10;
$user->save();

// 新代码（移除后）
// 积分和余额功能已移除，如需恢复请参考历史版本
```

### 注意事项

1. **不可逆操作**：字段删除后，数据无法恢复，请务必先备份
2. **依赖检查**：修改生产环境前，务必在测试环境验证
3. **缓存清理**：修改后需要清理框架缓存 `runtime/`
4. **代码同步**：确保代码和数据库结构保持一致

### 相关文件清单

本次移除涉及的文件：

| 文件路径 | 修改内容 |
|---------|---------|
| `/workspace/backup/bak_all_initialize/2_create_table.sql` | 表结构定义 |
| `/workspace/backup/bak_all_initialize/3_insert_xphp_user_part1.sql` | 初始化数据 |
| `/workspace/DOC/database.md` | 数据库文档 |
| `/workspace/DOC/development.md` | 开发文档 |

---

**执行时间**：2026-05-07  
**执行人**：AI Assistant  
**迁移版本**：v1.0
