# XPHP 框架项目文档

## 文档目录

1. [数据库文档](./database.md) - 数据库结构、表设计、字段说明
2. [开发文档](./development.md) - 开发规范、编码规范、调试指南
3. [项目架构](./architecture.md) - 架构设计、模块说明、核心流程
4. [模板语法](./template.md) - 模板标签、变量输出、条件判断、循环
5. [项目依赖](./dependencies.md) - Composer依赖、系统要求、扩展列表
6. [快速开始](./quickstart.md) - 安装部署、配置说明、常见问题

---

## 项目概述

**项目名称**: 一鱼快速构架 (XStart)  
**框架版本**: XPHP v6.1.x  
**核心框架**: XPHP Framework  
**许可证**: Apache-2.0  
**作者**: 无念 (24203741@qq.com)  
**官网**: https://www.xphp.net  

### 主要特性

- 开箱即用，内置用户管理、菜单管理、配置管理等常用模块
- 代码生成，一键生成 MVC 代码和数据表
- 模块化设计，支持多应用和多主题
- 丰富的扩展功能：验证码、文件上传、图片缩略图、邮件发送
- 基于 PHP 8.1+，支持强类型、命名空间等现代特性
- 美观的后台界面，基于光年(Light Year Admin v5)后台模板

### 技术栈

| 类别 | 技术 | 版本 |
|------|------|------|
| 后端框架 | XPHP | v6.1.x |
| 编程语言 | PHP | 8.1 ~ 8.5 |
| 数据库 | MySQL | 5.6 ~ 8.0 |
| 前端框架 | Bootstrap | v5.1.3 |
| JavaScript | jQuery | v3.6.0 |
| 图标库 | Material Design Icons | v6.5.95 |
| 数据表格 | Bootstrap Table | v1.x |
| 后台模板 | Light Year Admin | v5 |

---

## 目录结构

```
/workspace/
├── app/                    # 应用目录
│   ├── admin/              # 后台应用
│   │   ├── command/        # 命令类模板
│   │   ├── config/         # 应用配置
│   │   ├── controller/     # 控制器
│   │   ├── model/          # 模型
│   │   ├── view/           # 视图
│   │   └── widget/         # 组件
│   ├── index/              # 前台应用
│   ├── install/            # 安装应用
│   └── common.php          # 公共函数
├── backup/                  # 数据备份目录
├── config/                  # 配置文件目录
├── DOC/                    # 项目文档目录
├── extend/                  # 扩展类库
│   ├── captcha/            # 验证码
│   ├── email/              # 邮件发送
│   ├── thumb/              # 图片缩略图
│   └── upload/             # 文件上传
├── middleware/              # 中间件目录
├── public/                  # Web根目录
│   ├── static/              # 静态资源
│   └── uploads/             # 上传文件
├── route/                   # 路由定义
├── template/                # 前台模板
├── xphp/                    # 框架核心
│   ├── cli/                 # 命令行工具
│   ├── core/                # 核心类库
│   └── tpl/                 # 模板文件
├── composer.json            # Composer配置
└── xphpcli                  # CLI入口
```
