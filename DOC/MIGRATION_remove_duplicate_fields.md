# 数据库迁移脚本：移除冗余字段

## 迁移说明

本脚本用于从 `xphp_user` 表中移除冗余的 `gender`（性别）和 `qq_openid`（第三方登录标识）字段。

### 迁移前准备

1. **备份数据库**：执行迁移前务必先备份数据库
2. **检查依赖**：确保没有业务代码依赖这两个字段
3. **通知用户**：如果是生产环境，提前通知相关用户

### 迁移脚本

#### MySQL 迁移语句

```sql
-- ========================================
-- 移除 xphp_user 表中的冗余字段
-- 执行前请确保已备份数据库！
-- ========================================

-- 1. 检查字段是否存在
-- DESCRIBE xphp_user;

-- 2. 移除 gender 字段（性别）- 完全未使用
ALTER TABLE `xphp_user` DROP COLUMN `gender`;

-- 3. 移除 qq_openid 字段（第三方登录标识）- 完全未使用
ALTER TABLE `xphp_user` DROP COLUMN `qq_openid`;

-- 4. 验证修改结果
-- DESCRIBE xphp_user;
```

### 回滚脚本

如果需要回滚（恢复字段），执行以下脚本：

```sql
-- ========================================
-- 回滚脚本：恢复 gender 和 qq_openid 字段
-- ========================================

-- 1. 添加 gender 字段（性别）
ALTER TABLE `xphp_user` 
ADD COLUMN `gender` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '性别(0未知/1男/2女)' AFTER `bio`;

-- 2. 添加 qq_openid 字段（第三方登录标识）
ALTER TABLE `xphp_user` 
ADD COLUMN `qq_openid` varchar(50) NOT NULL DEFAULT '' COMMENT '第三方登录标识' AFTER `bio`;
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
AND COLUMN_NAME IN ('gender', 'qq_openid');

-- 3. 测试用户注册和登录功能
-- INSERT INTO xphp_user (username, password, nickname, create_time) VALUES ('test', '123', '测试', UNIX_TIMESTAMP());

-- 4. 测试用户查询
-- SELECT * FROM xphp_user WHERE username = 'test';
```

### PHP 代码兼容性

本次迁移涉及的代码修改：

#### 已修改的文件

| 文件路径 | 修改内容 |
|---------|---------|
| `/workspace/backup/bak_all_initialize/2_create_table.sql` | 表结构定义 - 已移除 gender 和 qq_openid 字段 |
| `/workspace/backup/bak_all_initialize/3_insert_xphp_user_part1.sql` | 初始化数据 - 已移除字段值 |
| `/workspace/app/common/model/User.php` | 模型验证规则 - 已移除 qq_openid 验证 |
| `/workspace/DOC/database.md` | 数据库文档 - 已更新字段说明 |

#### 模型文件修改详情

**修改前**：
```php
protected array $validate = [
    // ... 其他验证规则
    ['qq_openid', 'unique', 'openid已绑定', FV_VALUE, AC_BOTH],
];
```

**修改后**：
```php
protected array $validate = [
    // ... 其他验证规则（qq_openid 验证已移除）
];
```

### 注意事项

1. **不可逆操作**：字段删除后，数据无法恢复，请务必先备份
2. **依赖检查**：修改生产环境前，务必在测试环境验证
3. **缓存清理**：修改后需要清理框架缓存 `runtime/`
4. **代码同步**：确保代码和数据库结构保持一致

---

**执行时间**：2026-05-07  
**迁移版本**：v2.0  
**移除字段**：gender（性别）、qq_openid（第三方登录标识）
**保留字段**：avatar（头像）已保留
