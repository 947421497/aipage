# 项目依赖文档

## 目录

- [Composer 依赖](#composer-依赖)
- [PHP 扩展依赖](#php-扩展依赖)
- [前端资源依赖](#前端资源依赖)
- [数据库依赖](#数据库依赖)
- [服务器环境要求](#服务器环境要求)

---

## Composer 依赖

### composer.json 配置

一鱼快速构架使用 Composer 管理 PHP 依赖包。composer.json 文件定义了项目的基本信息和依赖关系。

```json
{
  "name": "xphpnet/xphp",
  "description": "XPHP Framework",
  "type": "project",
  "keywords": [
    "XPHP",
    "一鱼框架",
    "framework",
    "ORM"
  ],
  "homepage": "https://xphp.net",
  "license": "Apache-2.0",
  "authors": [
    {
      "name": "无念",
      "email": "24203741@qq.com"
    }
  ],
  "minimum-stability": "stable",
  "require": {
    "php": ">=8.1.0",
    "ext-json": "*",
    "ext-pdo": "*",
    "ext-mbstring": "*",
    "ext-gd": "*",
    "ext-openssl": "*",
    "ext-curl": "*",
    "ext-ctype": "*"
  },
  "autoload": {
    "psr-4": {
      "app\\": "app",
      "xphp\\": "xphp",
      "middleware\\": "middleware",
      "extend\\": "extend"
    }
  }
}
```

### 核心依赖

框架的核心依赖非常精简，主要依赖 PHP 语言本身和必要的扩展。框架采用原生 PDO 封装数据库操作，不依赖任何第三方 ORM 库。框架手动实现自动加载，不强制依赖 Composer 的自动加载机制。这种精简的依赖设计使得框架可以灵活部署，适应各种服务器环境。

### 可选依赖

以下扩展是可选的，可以根据项目需求选择性安装。Redis 扩展用于 Redis 缓存和会话支持，需要 PHP 安装 redis 扩展。Swoole 扩展用于 Swoole 协程支持，可以提升框架在高并发场景下的性能。这些扩展不是框架运行的必须依赖，不安装也能正常运行。

> 注意：实际 composer.json 中未定义 `suggest` 字段，以下为建议配置。如需使用 Redis 缓存或 Swoole，请手动安装对应 PHP 扩展。

```json
{
  "suggest": {
    "ext-redis": "Redis support for caching and session",
    "ext-swoole": "Swoole support for high performance"
  }
}
```

### 安装依赖

使用 Composer 安装项目依赖前，需要确保服务器已安装 Composer 工具。执行 composer install 命令安装 composer.json 中定义的所有依赖。安装前会自动检测 PHP 版本和扩展是否满足要求。如果网络较慢，可以使用国内镜像源加速下载。

```bash
# 安装所有依赖
composer install

# 更新依赖到最新版本
composer update

# 安装时使用国内镜像
composer config repo.packagist composer https://packagist.phpcomposer.com
composer install

# 验证依赖完整性
composer validate
```

---

## PHP 扩展依赖

### 必须扩展

以下 PHP 扩展是框架运行的必须依赖，缺少任何一个都将导致框架无法正常运行。

json 扩展是 PHP 操作 JSON 数据的基础，框架的 API 响应、数据验证等功能都依赖 JSON 编码和解码。pdo 扩展是数据库连接的抽象层，框架使用 PDO 连接 MySQL 数据库，提供统一的数据库操作接口。mbstring 扩展用于多字节字符串处理，框架内部大量使用字符串函数，正确处理中文等非 ASCII 字符依赖此扩展。gd 扩展用于图片处理，验证码生成、图片缩略图等功能需要 GD 库支持。

### 安全相关扩展

以下扩展虽然不直接参与框架运行，但强烈建议安装以提升系统安全性。openssl 扩展用于数据加密和安全通信，包括 HTTPS 加密连接、API 签名验证等场景。ctype 扩展用于字符类型检查，框架的部分数据验证逻辑依赖此扩展。hash 扩展用于各种哈希算法，包括密码加密、文件完整性校验等。

```bash
# 检查已安装的扩展
php -m

# 检查特定扩展
php -m | grep json
php -m | grep pdo

# 查看扩展详细信息
php -i | grep -A 10 "json"
```

### 扩展安装方法

不同的服务器环境和操作系统，PHP 扩展的安装方法有所不同。

使用包管理器安装扩展适用于宝塔面板、军哥 LNMP 等一键部署环境。在宝塔面板的软件管理中找到对应版本的 PHP，点击设置 - 安装扩展，勾选需要的扩展后安装。使用 Docker 部署时，在 Dockerfile 中添加扩展安装指令。

编译安装扩展适用于需要自定义配置的服务器场景。以安装 gd 扩展为例，首先安装必要的依赖库，然后编译安装 PHP 扩展，最后在 php.ini 中启用扩展。

```bash
# Ubuntu/Debian 安装 gd 扩展
sudo apt-get install php-gd
sudo service php-fpm restart

# CentOS 安装 gd 扩展
sudo yum install php-gd
sudo systemctl restart php-fpm

# 编译安装（通用）
cd /path/to/php-source/ext/gd
phpize
./configure --with-php-config=/path/to/php-config
make && make install
echo "extension=gd.so" >> /path/to/php.ini
```

---

## 前端资源依赖

### CSS 框架

框架前端使用 Bootstrap 5.1.3 作为主要的 CSS 框架，提供响应式布局、栅格系统、基础组件等功能。Bootstrap 将页面分为 12 列的栅格系统，配合断点机制实现移动端到桌面端的自适应布局。框架内置了 Bootstrap 的所有核心样式和组件，包括按钮、表单、卡片、导航、模态框等常用元素。开发者可以在此基础上进行样式定制或替换。

### JavaScript 库

jQuery 3.6.0 是前端交互的基础 JavaScript 库，提供了便捷的 DOM 操作、事件处理、Ajax 请求等功能。框架的前端脚本大量使用 jQuery API，与 Bootstrap 的 JavaScript 组件无缝配合。Moment.js 2.25.3 用于日期和时间处理，支持日期格式化、解析、计算等操作。FullCalendar 4.3.1 用于日历组件开发，支持月视图、周视图、日程管理等功能。

### UI 组件

Bootstrap Table 1.x 是强大的数据表格组件，支持排序、分页、搜索、行选择、固定列等丰富功能。配合扩展插件可以实现导出、编辑、打印等高级功能。Chart.js 3.9.1 用于图表展示，支持折线图、柱状图、饼图、雷达图等多种图表类型。WebUploader 0.1.5 是文件上传组件，支持分片上传、断点续传、拖拽上传等高级特性。

### 图标与字体

Material Design Icons 6.5.95 是框架使用的图标库，提供超过 5000 个精心设计的图标。所有图标使用统一的 SVG 格式，支持 CSS 样式控制图标大小和颜色。框架的后台界面使用 MDI 图标，图标类名以 mdi- 前缀开头，如 mdi-view-dashboard 表示仪表盘图标。

---

## 数据库依赖

### MySQL 版本要求

框架推荐使用 MySQL 5.6 及以上版本。MySQL 5.6 引入了许多性能优化和新的功能特性，能够更好地支持现代 Web 应用。MySQL 5.7 带来了 JSON 数据类型支持、改进的全文搜索等功能，值得推荐使用。MySQL 8.0 是最新稳定版本，提供了窗口函数、CTE 公用表表达式、改进的性能等特性。

### 字符集要求

框架要求数据库使用 utf8mb4 字符集，这是 MySQL 5.5.3 及以上版本支持的 UTF-8 编码方案。相比旧的 utf8 字符集，utf8mb4 能够存储包括 emoji 表情符号在内的所有 Unicode 字符。表的排序规则建议使用 utf8mb4_general_ci 或 utf8mb4_unicode_ci，前者性能更好，后者排序更准确。

```sql
-- 创建数据库时指定字符集
CREATE DATABASE `xphp_db` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_general_ci;

-- 创建表时指定字符集
CREATE TABLE `xphp_user` (
  ...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 表引擎要求

框架要求所有数据表使用 InnoDB 存储引擎。InnoDB 支持事务处理、行级锁、外键约束等高级特性，是处理高并发读写场景的最佳选择。InnoDB 的行级锁机制可以大幅提升并发性能，事务支持保证数据操作的原子性。框架不推荐使用 MyISAM 引擎，虽然其查询性能略优，但在并发写入、数据安全方面存在明显不足。

---

## 服务器环境要求

### Web 服务器

框架支持多种 Web 服务器环境，推荐使用 Nginx 或 Apache。

Nginx 是高性能的 HTTP 服务器和反向代理服务器，配置简单、资源占用低。配合 PHP-FPM 可以高效处理 PHP 请求。一鱼快速构架提供了 Nginx 伪静态规则配置文件 nginx.htaccess，可以直接导入使用。

Apache 是成熟稳定的 Web 服务器，mod_rewrite 模块提供强大的 URL 重写能力。Apache 的 .htaccess 文件可以在不修改服务器配置的情况下控制目录级别的伪静态规则。框架同样提供了 Apache 的伪静态规则。

### PHP 版本要求

框架最低要求 PHP 8.1 版本，充分利用了 PHP 8.x 的强类型、Match 表达式、联合类型等新特性。推荐使用 PHP 8.2 或更高版本，可以获得更好的性能和更多的语言特性支持。框架在设计时考虑了 PHP 8.1 到 8.5 之间的兼容性，确保在这些版本上都能正常运行。

```bash
# 检查 PHP 版本
php -v

# 查看 PHP 详细信息
php -i

# 检查 PHP 配置路径
php --ini
```

### 目录权限要求

服务器需要配置正确的文件权限以确保框架正常运行。runtime 目录需要 Web 服务器有写入权限，用于存储缓存文件、会话文件、日志文件等。public/uploads 目录需要写入权限，用于存储用户上传的文件。其他应用目录和文件建议设置为只读权限，由 Web 服务器的运行用户可读即可。

```bash
# 设置 runtime 目录权限
chmod -R 755 runtime
chown -R www-data:www-data runtime

# 设置上传目录权限
chmod -R 755 public/uploads
chown -R www-data:www-data public/uploads
```

### 内存和超时配置

对于大型应用或复杂查询，建议适当调整 PHP 的内存限制和执行超时时间。memory_limit 建议设置为 128M 或更高，确保复杂操作不会因内存不足而中断。max_execution_time 建议设置为 300 秒或更高，给长时间运行的脚本足够的时间完成执行。
