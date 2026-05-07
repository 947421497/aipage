# 一鱼快速构架 (XStart)

[![License](https://img.shields.io/badge/license-Apache--2.0-blue.svg)]()
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.1-8892BF.svg)]()

一鱼快速构架基于 XPHP 框架开发，包含用户模块和简易后台，适用于快速开发小型网站应用。

## 特性

- **开箱即用**：内置用户管理、菜单管理、配置管理等常用模块
- **代码生成**：一键生成 MVC 代码和数据表，大幅提升开发效率
- **模块化设计**：清晰的应用目录结构，支持多应用和多主题
- **丰富的扩展**：验证码、文件上传、图片缩略图、邮件发送等常用功能
- **现代化技术栈**：基于 PHP 8.1+，支持强类型、命名空间等现代特性
- **美观的后台界面**：基于光年(Light Year Admin v5)后台模板

## 环境要求

- **PHP 环境**：PHP 8.1 ~ PHP 8.5
- **数据库**：MySQL 5.6 ~ MySQL 8.0
- **PHP 扩展**：json、pdo、mbstring、gd、openssl、curl、ctype

## 技术栈

### 后端框架

- **XPHP v6.1.x**：核心框架
- **ORM**：数据库操作支持
- **Middleware**：中间件机制
- **CLI**：命令行工具

### 前端资源

- **Bootstrap v5.1.3**：前端 UI 框架
- **jQuery v3.6.0**：JavaScript 库
- **Material Design Icons v6.5.95**：图标库
- **Bootstrap Table v1.x**：数据表格组件
- **FullCalendar v4.3.1**：日历组件
- **Moment.js v2.25.3**：日期处理库
- **WebUploader v0.1.5**：文件上传组件
- **Chart.js v3.9.1**：图表库
- **光年(Light Year Admin v5)**：后台管理系统模板

## 快速开始

### 安装步骤

#### 宝塔面板安装

1. 添加站点：设置域名（已解析到 IP 的域名或 IP:端口），创建 MySQL 数据库
2. 上传并解压安装包到网站目录
3. 设置 → 网站目录 → 运行目录设置为 `/public`
4. 设置 → 伪静态，添加以下规则：

```nginx
location / {
    if (!-e $request_filename) {
        rewrite  ^(.*)$  /index.php/$1  last;
    }
}
```

5. 修改 `config/database.php` 中的数据库配置，或重命名 `env.example.env` 为 `.env` 并修改其中的数据库配置
6. 访问域名，按照安装向导完成安装
7. （可选）设置定时任务：`php xphpcli [应用@]命令类:方法 参数值`

#### PHPStudy 安装

1. 创建网站：添加域名（如 `www.xphp.io`），勾选创建数据库
2. 解压安装包到网站目录
3. 修改网站根目录到 `/public`
4. 设置伪静态规则（同上）
5. 重命名 `env.example.env` 为 `.env` 并修改其中数据库配置
6. 访问域名完成安装

### Apache 伪静态规则

```apache
<IfModule mod_rewrite.c>
  Options +FollowSymlinks -Multiviews
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^(.*)$ index.php? [L,E=PATH_INFO:$1]
</IfModule>
```

## 功能模块

### 安装模块

- 未安装时自动跳转安装界面
- 一键安装，自动创建数据表和初始数据

### 前台模块

- **首页展示**：系统简介
- **关于页面**：关于我们
- **用户模块**：登录、注册、修改资料、修改密码、退出

### 后台模块

- **用户管理**：添加、修改、删除、启用、停用、修改等级
- **网站配置**：添加、修改、删除、生成配置文件
- **菜单管理**：添加、修改、删除、启用、停用
- **数据备份**：备份、还原、下载备份、删除备份
- **代码生成**：一键生成 MVC 代码和数据表
- **个人资料**：修改资料、修改密码

## 核心功能

### MVC 架构

```
app/
├── admin/          # 后台应用
│   ├── controller/ # 控制器
│   ├── model/      # 模型
│   └── view/       # 视图
├── index/          # 前台应用
└── install/        # 安装应用
```

### 中间件机制

支持全局中间件和控制器中间件，内置：

- **Auth**：前台用户认证
- **CpAuth**：后台管理员认证
- **Csrf**：CSRF 防护

### 扩展功能

位于 `extend/` 目录，包含：

- **Captcha**：验证码生成
- **Upload**：文件上传处理
- **Thumb**：图片缩略图生成
- **Smtp**：邮件发送

### CLI 命令行工具

支持通过命令行执行任务：

```bash
# 生成模型
php xphpcli make:model admin@user id _def -f

# 生成控制器
php xphpcli make:ctrl admin@user _def -f

# 生成视图
php xphpcli make:view admin@user index index -f

# 清理缓存
php xphpcli clear
```

### 缓存支持

- **File Cache**：文件缓存
- **Redis Cache**：Redis 缓存

### Session 支持

- **File Session**：文件存储
- **Redis Session**：Redis 存储

## 目录结构

```
.
├── app/              # 应用目录
├── backup/           # 数据备份目录
├── config/           # 配置文件目录
├── extend/           # 扩展类库目录
├── middleware/       # 中间件目录
├── public/           # 公共资源目录（Web 根目录）
│   ├── static/       # 静态资源
│   └── uploads/      # 上传文件
├── route/            # 路由定义目录
├── template/         # 前台模板目录
└── xphp/             # 框架核心目录
```

## 常见问题

### 清理缓存

如遇到错误，请删除 `runtime` 目录下所有文件。

### 数据库连接失败

检查 `config/database.php` 或 `.env` 文件中的数据库配置是否正确。

### 伪静态不生效

确保 Web 服务器已正确配置伪静态规则，并重启 Web 服务器。

## 演示地址

- 演示站点：http://xstart.xphp.net

## 技术支持

- **框架官网**：https://www.xphp.net
- **QQ 群 1**：325825297
- **QQ 群 2**：16008861
- **作者**：无念 (24203741@qq.com)

## 许可证

本项目基于 Apache-2.0 协议开源，详见 [LICENSE.txt](./LICENSE.txt)。
