# SEO 自动化落地页系统 - 完整开发文档

> **文档版本**: v2.0
> **创建日期**: 2026-05-19
> **更新日期**: 2026-05-22
> **适用框架**: XPHP v6.1.1 | PHP >= 8.1

---

# 第一部分：项目总览

## 1. 项目目标

构建一套基于 AI 的自动化落地页生成系统，支持关键词驱动、自动生成符合 SEO 规范的 HTML 落地页，提供完整的内容管理、百度推送、定时任务等能力。

## 2. 技术架构

- **分层架构**: 后台（admin）+ 公共层（common）+ 前台（index）
- **框架**: XPHP v6.1.1
- **PHP 版本**: >= 8.1
- **数据库**: MySQL (InnoDB)

## 3. 文件组织

```
app/
├── common.php                          ← 公共函数（直接追加）
├── common/model/                       ← 跨应用模型
│   ├── AiConfig.php
│   ├── Prompt.php
│   ├── Keyword.php
│   ├── Page.php
│   ├── Task.php
│   └── TaskLog.php
├── admin/controller/                    ← 后台控制器
│   ├── AiConfig.php
│   ├── Prompt.php
│   ├── Keyword.php
│   ├── Page.php
│   └── Task.php
├── index/controller/                    ← 前台控制器
│   ├── Index.php (修改)
│   └── Cron.php
└── admin/view/                          ← 后台视图
    ├── ai_config/
    ├── prompt/
    ├── keyword/
    ├── page/
    └── task/

backup/bak_all_initialize/              ← SQL 文件（追加到现有文件）
├── 1_drop_table.sql                     （追加6张表DROP）
├── 2_create_table.sql                   （追加6张表CREATE）
├── 3_insert_xphp_config_part1.sql       （追加7条配置）
└── 3_insert_xphp_menu_part1.sql         （追加4个菜单）
```

## 4. 模块依赖关系

```
S0 (数据库与公共函数)
  ├──→ S1 (AI配置管理)
  │       └──→ S2 (关键词管理)
  │               └──→ S3 (页面生成管理)
  │                       ├──→ S4 (定时任务管理)
  │                       └──→ S5 (前台展示)
```

| 模块编号 | 模块名称 | 功能说明 | 预计复杂度 | 关键依赖 | 可交付成果 |
|----------|----------|----------|-----------|---------|----------|
| S0 | 数据库与公共函数 | SQL 文件 + 7个公共函数 | 低 | 无 | 6张新表 + 7个函数 |
| S1 | AI配置管理 | AI引擎配置 + Prompt模板管理 | 中 | S0 | 后台可配置AI + Prompt |
| S2 | 关键词管理 | 关键词 CRUD + AI拓词 + CSV导入导出 | 中 | S0, S1 | 后台可管理关键词 |
| S3 | 页面生成管理 | 页面生成 + 状态管理 + 预览 | 高 | S0, S1, S2 | 后台可生成页面 |
| S4 | 定时任务管理 | Cron触发 + 任务日志 + 批量生成 | 高 | S0, S3 | Cron可自动生成 |
| S5 | 前台展示 | 落地页路由 + 首页改造 + Sitemap | 中 | S2, S3 | 落地页可正常访问 |

## 5. 参考文档优先级

1. **主参考**: `SEO自动化落地页系统-统一需求文档.md` — 完整需求说明
2. **本文档**: `SEO自动化落地页系统-完整开发文档.md` — 模块详情 + 框架规范

## 6. 开发顺序

请严格按照以下顺序开发：S0 → S1 → S2 → S3 → S4 → S5

## 7. 技术决策记录

| 决策项 | 方案 | 理由 |
|--------|------|------|
| 公共函数位置 | 直接追加到 `app/common.php` | 遵循 XPHP 现有架构 |
| SQL 文件位置 | 追加到 `backup/bak_all_initialize/` 现有文件 | 遵循框架备份规范，不创建单独目录 |
| API Key 存储 | 明文存储 | 按用户要求，最简单方式 |
| 锁文件位置 | `runtime/cache/seo_lock_{type}.lock` | 统一存储位置 |
| 表前缀 | `xphp_` | 遵循框架默认 |
| 首页改造 | 现有模板直接修改 | 不新增文件 |
| 测试数据 | 后期自行添加 | 开发阶段不提供 |
| 落地页渲染 | 数据库读取，动态渲染 | 无单独模板，AI 生成直接输出 |

---

# 第二部分：XPHP 框架语法规范（基于源码）

> **重要**: XPHP 是自行开发的框架，语法与市面框架不同。以下所有规范均来自项目实际源码，不可猜测或套用其他框架。

## 1. 模板引擎语法

模板引擎配置文件：`config/template.php`。编译逻辑在 `xphp/core/Template.php`。

### 1.1 变量输出

```
{$var}                    → <?php echo e($var)?>           默认转义
{$var.key}                → <?php echo e($var['key'])?>    点号访问数组
{$var.key.key}            → 三级数组访问
{$var|raw}                → <?php echo $var?>              不转义输出
{$var.key|raw}            → 不转义的数组访问
{$var|default='xxx'}      → 空值时显示默认值
{$var ?? 'xxx'}           → null合并运算符
{$var.key|get_time_ago}   → 函数修饰符
{$var|date='Y-m-d'}       → 日期格式化
```

### 1.2 函数调用

```
{:url('index')}                              → <?php echo url('index')?>
{:url('edit',['id'=>$vo['id']])}             → 带参数的URL
{:form_select('name',$options,$selected)}    → 表单组件
{:form_radio('name',$options,$selected)}     → 单选组件
{:input('name','','clear_html')}             → 获取输入
{:site('site_name','默认值')}                 → 获取站点配置
{:echo($var)}                                → 不转义输出
{php $var = expression}                      → PHP赋值
```

### 1.3 控制结构

```
{if condition:}          → <?php if (condition):?>
{elseif condition:}      → <?php elseif (condition):?>
{else:}                  → <?php else:?>
{/if}                    → <?php endif?>
{empty $var:}            → <?php if (empty($var)):?>
{!empty $var:}           → <?php if (!empty($var)):?>
{/empty}                 → <?php endif?>       （empty也用{/empty}或{/if}关闭）
```

> **关键**: if/elseif/else 条件后必须加冒号 `:`，闭合标签是 `{/if}`。empty 闭合用 `{/empty}` 或 `{/if}` 均可。

### 1.4 循环

```
{foreach $list as $vo}                    → <?php foreach($list as $vo):?>
{foreach $list as $key => $vo}            → 带键名
{foreach $var['key'] as $vo}              → 数组元素遍历
{/foreach}                                → <?php endforeach?>
```

### 1.5 文件包含

```
{include file='public/_head.html'}
{include file='public/sidebar.html'}
{include file='public/_header.html'}
{include file='public/footer.html'}
```

### 1.6 常量输出

```
__STATIC__    → 静态资源路径
__ROOT__      → 站点根路径
__THEME__     → 当前主题
```

## 2. 后台视图模板标准结构

### 2.1 列表页（index.html）标准模板

```html
{include file='public/_head.html'}
</head>
<body>
<div id="lyear-preloader" class="loading">
  <div class="ctn-preloader">
    <div class="round_spinner">
      <div class="spinner"></div>
      <img src="__STATIC__/images/loading-logo.png" alt="">
    </div>
  </div>
</div>
<div class="lyear-layout-web">
  <div class="lyear-layout-container">
    {include file='public/sidebar.html'}
    {include file='public/_header.html'}

    <main class="lyear-layout-content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <div class="card">
              <header class="card-header"><div class="card-title">模块名称</div></header>
              <div class="card-body">

                <div class="card-btns mb-2-5">
                  <a href="javascript:openModal('{:url('add')}','新增XXX')" class="btn btn-primary me-1"><i class="mdi mdi-plus"></i> 新增</a>
                  <button type="button" class="btn btn-success me-1" onclick="actionConfirm('启用','{:url('state?params=status-1')}');"><i class="mdi mdi-check"></i> 启用</button>
                  <button type="button" class="btn btn-warning me-1" onclick="actionConfirm('停用','{:url('state?params=status-0')}');"><i class="mdi mdi-block-helper"></i> 停用</button>
                  <button type="button" class="btn btn-danger" onclick="actionConfirm('删除','{:url('del')}');"><i class="mdi mdi-window-close"></i> 删除</button>
                </div>

                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th><div class="form-check"><input class="form-check-input" type="checkbox" id="check-all" onclick="selectAll(this.checked)"><label class="form-check-label" for="check-all"></label></div></th>
                        <th>ID</th>
                        <!-- 其他列 -->
                        <th>状态</th>
                        <th>操作</th>
                      </tr>
                    </thead>
                    <tbody>
                    {foreach $list as $vo}
                      <tr>
                        <td><div class="form-check"><input type="checkbox" class="form-check-input ids" name="ids[]" value="{$vo.id}" id="ids-{$vo.id}"><label class="form-check-label" for="ids-{$vo.id}"></label></div></td>
                        <td>{$vo.id}</td>
                        <!-- 其他列 -->
                        <td>
                          {if $vo['status']==1:}
                          <a href="javascript:ajaxConfirm('{:url('state?params=status-0',['ids'=>$vo['id']])}','停用',true);" class="text-success" data-bs-toggle="tooltip" title="点击停用">已启用</a>
                          {else:}
                          <a href="javascript:ajaxConfirm('{:url('state?params=status-1',['ids'=>$vo['id']])}','启用',true);" class="text-secondary" data-bs-toggle="tooltip" title="点击启用">已停用</a>
                          {/if}
                        </td>
                        <td>
                          <div class="btn-group btn-group-sm">
                            <a class="btn btn-primary" href="javascript:openModal('{:url('edit',['id'=>$vo['id']])}','编辑XXX')">编辑</a>
                            <a class="btn btn-danger" href="javascript:ajaxConfirm('{:url('del',['ids'=>$vo['id']])}','删除',true);">删除</a>
                          </div>
                        </td>
                      </tr>
                    {/foreach}
                    </tbody>
                  </table>
                </div>
                {empty $list->toArray():}
                <p class="text-center text-muted py-3">暂无记录！</p>
                {else:}
                {$list->links()|raw}
                {/empty}

              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    {include file='public/footer.html'}
</body>
</html>
```

### 2.2 表单页（_form.html）标准模板

```html
<form class="site-form submit-ajax" action="{:url($is_edit ? 'edit' : 'add')}" method="post">
{if $is_edit:}
<input type="hidden" name="id" value="{$vo.id}" />
{/if}
<div class="mb-3">
  <label class="form-label" for="field">*字段名</label>
  <input type="text" class="form-control" id="field" name="field" placeholder="请输入" value="{$vo.field|default=''}" required />
</div>
<div class="mb-3">
  <button type="submit" class="btn btn-primary">{if $is_edit:}确定{else:}添加{/if}</button>
  <button type="button" class="btn btn-default" data-bs-dismiss="modal">取消</button>
</div>
</form>
```

### 2.3 JS 交互函数（定义在 xphp-1.0.js）

| 函数 | 用途 | 示例 |
|------|------|------|
| `openModal(url, title, size)` | 弹窗加载表单页，size='sm'|'lg'|'xl' | `openModal('{:url(\'add\')}', '新增')` |
| `ajaxConfirm(url, action, refresh)` | 单条确认操作（状态切换/删除），refresh=true时刷新页面 | `ajaxConfirm('{:url(\'del\')}', '删除', true)` |
| `actionConfirm(action, url)` | 批量操作（勾选ids后POST） | `actionConfirm('删除', '{:url(\'del\')}')` |
| `selectAll(checked)` | 全选/取消全选 | `onclick="selectAll(this.checked)"` |
| `toast(msg, timer)` | 提示消息 | 自动调用 |

### 2.4 表单提交机制（submit-ajax）

表单使用 `class="site-form submit-ajax"`，xphp-1.0.js 自动拦截提交：
- 序列化表单数据，POST 到 action URL
- 按钮显示"提交中…"并禁用
- 成功后：toast 提示 + 关闭弹窗（如果在弹窗内）+ 刷新列表页
- 失败后：toast 错误信息

### 2.5 表单组件函数（定义在 app/common.php）

| 函数 | 签名 | 说明 |
|------|------|------|
| `form_select($name, $options, $selected, $attr)` | 下拉选择框 | `{:form_select('status', ['停用','启用'], $vo['status'], 'class="form-select"')}` |
| `form_radio($name, $options, $selected, $attr, $is_label)` | 单选按钮组 | `{:form_radio('is_active', ['0'=>'否','1'=>'是'], $vo['is_active'])}` |

## 3. 控制器规范

### 3.1 Cp 基类（app/admin/controller/Cp.php）

后台控制器必须继承 `Cp` 基类。Cp 提供的标准方法：

| 方法 | 签名 | 说明 |
|------|------|------|
| `index()` | `public function index()` | 列表，调用 `model($this->model)->paginate()` |
| `add(array $req)` | `public function add(array $req)` | 新增，POST时 `model($this->model)->save($req)` |
| `edit(int $id, array $req)` | `public function edit(int $id, array $req)` | 编辑 |
| `del(string $ids)` | `public function del(string $ids)` | 删除，仅删 status=0 的记录 |
| `state(string $ids, string $params)` | `public function state(string $ids, string $params)` | 状态切换 |

Cp 基类可覆盖的属性：

```php
protected string $model = '';           // 模型引用，如 'common@keyword'
protected string $order = 'id DESC';    // 列表排序
protected int $limit = 10;              // 每页条数（0=全部）
protected string $listFieldExcept = ''; // 列表排除字段
protected string $jumpUrl = 'index';    // 操作后跳转URL
protected array $stateList = ['status' => ['停用', '启用']]; // 状态配置
```

Cp 基类可覆盖的方法：

```php
protected function _where(): array                    // 列表查询条件
protected function _after_state(string $field, string $value, array $ids): void  // 状态切换后
```

### 3.2 控制器写法示例

```php
<?php
declare(strict_types=1);
namespace app\admin\controller;

class Keyword extends Cp
{
    protected string $model = 'common@keyword';

    protected function _where(): array
    {
        $where = [];
        $name = input('name', '', 'clear_html');
        if (!empty($name)) {
            $where[] = ['word', 'like', "%{$name}%"];
        }
        return $where;
    }

    public function expand(int $id)
    {
        // 自定义方法
    }
}
```

> **关键**: `$model` 属性使用 `common@xxx` 格式，因为新模块的模型全部放在 `app/common/model/` 目录下。

## 4. 模型规范

### 4.1 模型基类属性（xphp/core/Model.php）

```php
protected string $table = '';              // 表名（不含前缀）
protected string $pk = '';                 // 主键
protected string $tag = '';                // 缓存标识（用于widget重载）
protected array $allowFill = ['*'];        // 允许填充字段
protected array $denyFill = [];            // 禁止填充字段
protected string $autoTimeType = 'int';    // 自动时间类型：int|date|datetime|timestamp
protected string $createTime = 'create_time';
protected string $updateTime = 'update_time';
protected array $validate = [];            // 验证规则
protected array $auto = [];                // 自动处理
protected array $filter = [];              // 自动过滤
protected array $errors = [];              // 错误信息
```

### 4.2 模型写法示例

```php
<?php
declare(strict_types=1);
namespace app\common\model;
use xphp\core\Model;

class Keyword extends Model
{
    protected string $table = 'keyword';
    protected string $pk = 'id';
    protected array $validate = [
        ['word', 'required|unique', '关键词必须|关键词已存在', FV_MUST, AC_BOTH],
    ];
    protected array $auto = [
        ['status', '1', 'string', FV_MUST, AC_INSERT],
    ];
    protected array $filter = [
        ['pinyin', FV_EMPTY, AC_UPDATE],
    ];
}
```

### 4.3 验证规则格式

```php
[字段名, 规则, 错误提示, 验证条件, 场景]
```

- **验证条件**: `FV_MUST`(必须)、`FV_VALUE`(有值时)、`FV_ISSET`(存在时)、`FV_EMPTY`(空时)、`FV_UNSET`(不存在时)
- **场景**: `AC_INSERT`(新增)、`AC_UPDATE`(更新)、`AC_BOTH`(两者)

### 4.4 自动处理格式

```php
[字段名, 规则, 处理方式, 验证条件, 场景]
```

- **处理方式**: `string`(直接赋值)、`field`(等同字段)、`method`(模型方法)、`function`(函数调用)

### 4.5 模型生命周期钩子

```php
protected function _before_insert(array &$data): void
protected function _before_update(array &$data): void
protected function _before_delete(array $data): void
protected function _after_insert(array $data): void
protected function _after_update(array $before, array $after): void
protected function _after_delete(array $data): void
```

## 5. 框架核心函数

| 函数 | 说明 |
|------|------|
| `model('common@keyword')` | 获取模型实例，`@` 分隔应用名和模型名 |
| `db('table_name')` | 获取数据表操作对象 |
| `pdo()` | 获取数据库连接对象，用于事务 `pdo()->trans(fn() => ...)` |
| `cache('name', $value, $expire)` | 缓存管理 |
| `cache_make('name', fn() => ..., $expire)` | 缓存获取或创建 |
| `cache_clear('path')` | 清除缓存 |
| `encrypt($string)` / `decrypt($string)` | 加密解密 |
| `url('controller/action', $params)` | URL 生成 |
| `input('name', $default, $batchFunc)` | 获取请求输入 |
| `site('key', 'default')` | 获取站点配置 |
| `widget('menu')->get()` | 获取Widget缓存数据 |
| `widget_reload($tag)` | 重载Widget缓存 |
| `validate($rules, $data)` | 数据验证 |
| `ids_filter($ids, $to_array)` | ID过滤 |
| `form_select($name, $options, $selected, $attr)` | 下拉选择框HTML生成 |
| `form_radio($name, $options, $selected, $attr, $is_label)` | 单选按钮组HTML生成 |

## 6. 框架常量

| 常量 | 说明 |
|------|------|
| `IS_AJAX` | 是否AJAX请求 |
| `IS_POST` | 是否POST请求 |
| `IS_CLI` | 是否命令行 |
| `APP_DEBUG` | 调试模式 |
| `ROOT_PATH` | 项目根路径 |
| `RUNTIME_PATH` | 运行时路径 |
| `VIEW_PATH` | 视图路径 |
| `APP_NAME` | 当前应用名 |
| `AC_INSERT` | 新增场景常量 |
| `AC_UPDATE` | 更新场景常量 |
| `AC_BOTH` | 两者场景常量 |
| `FV_MUST` | 必须验证 |
| `FV_VALUE` | 有值验证 |
| `FV_ISSET` | 存在验证 |
| `FV_EMPTY` | 空值验证 |

## 7. 路由配置

路由文件：`route/admin.php`（后台）、`route/index.php`（前台）。

当前路由文件返回空数组，框架使用默认的 `/控制器/方法` 路由规则。如需自定义路由，在对应路由文件中添加：

```php
return [
    'keyword/{pinyin}.html' => 'index/dispatch',
    'cron/{key}' => 'cron/index',
];
```

## 8. 框架约束与注意事项

1. **model() 分隔符**: 使用 `@`，不是 `.`（`model('common@keyword')`）
2. **PHP 8.1+ LSP**: 子类方法签名必须完全兼容父类
3. **模板编译缓存**: 模板修改后必须清除 `runtime/admin/view/` 编译缓存
4. **APP_DEBUG=false**: 错误被完全隐藏
5. **表前缀**: `xphp_`，模型中 `$table` 不含前缀
6. **自动时间**: 模型默认自动写入 `create_time` 和 `update_time`（int类型）
7. **删除限制**: Cp 基类 `del()` 方法只删除 `status=0` 的记录

---

# 第三部分：S0 - 数据库与公共函数

## 1. 功能概述

本模块负责数据库准备和公共函数实现，为后续所有模块提供基础支持。包含：
- 6 张新表的创建和初始数据
- 7 个核心公共函数

**依赖关系**: 无依赖，本模块为最基础层

## 2. 数据模型设计

### 2.1 表清单

| 表名 | 说明 | 字段数 | 位置 |
|------|------|--------|------|
| `xphp_ai_config` | AI 接入配置 | 15 | 2_create_table.sql |
| `xphp_prompt` | 提示词模板 | 9 | 2_create_table.sql |
| `xphp_keyword` | 关键词 | 9 | 2_create_table.sql |
| `xphp_page` | 落地页 | 15 | 2_create_table.sql |
| `xphp_task` | 定时任务 | 15 | 2_create_table.sql |
| `xphp_task_log` | 任务执行日志 | 9 | 2_create_table.sql |

### 2.2 SQL 文件结构

> **注意**: 所有 SQL 直接追加到 `backup/bak_all_initialize/` 目录下的现有文件中。

#### backup/bak_all_initialize/1_drop_table.sql（追加）

在现有内容末尾追加 6 张表的 DROP 语句：
```sql
-- 清空表: xphp_ai_config
DROP TABLE IF EXISTS `xphp_ai_config`;
-- <fen> --
-- 清空表: xphp_prompt
DROP TABLE IF EXISTS `xphp_prompt`;
-- <fen> --
-- 清空表: xphp_keyword
DROP TABLE IF EXISTS `xphp_keyword`;
-- <fen> --
-- 清空表: xphp_page
DROP TABLE IF EXISTS `xphp_page`;
-- <fen> --
-- 清空表: xphp_task
DROP TABLE IF EXISTS `xphp_task`;
-- <fen> --
-- 清空表: xphp_task_log
DROP TABLE IF EXISTS `xphp_task_log`;
-- <fen> --
```

#### backup/bak_all_initialize/2_create_table.sql（追加）

在现有内容末尾追加 6 张表的 CREATE 语句（完整内容请参考统一需求文档）：
- `xphp_ai_config`（15 字段）
- `xphp_prompt`（9 字段）
- `xphp_keyword`（9 字段）
- `xphp_page`（15 字段）
- `xphp_task`（15 字段）
- `xphp_task_log`（9 字段）

每条用 `-- <fen> --` 分隔。

#### backup/bak_all_initialize/3_insert_xphp_config_part1.sql（追加）

在现有内容末尾追加 7 条 config 配置项：
- site_name
- site_url
- baidu_site
- baidu_token
- baidu_fast_token
- cron_key（自动生成 32 位随机字符）
- cron_allowed_ips

#### backup/bak_all_initialize/3_insert_xphp_menu_part1.sql（追加）

在现有内容末尾追加 4 个后台菜单：
- 关键词管理 (keyword/index)
- 页面管理 (page/index)
- AI引擎 (ai_config/index)
- 定时任务 (task/index)

## 3. 公共函数设计

### 3.1 函数清单

| 函数名 | 文件位置 | 功能 |
|--------|---------|------|
| `ai_chat()` | app/common.php | AI 调用统一入口 |
| `filter_landing_html()` | app/common.php | HTML 安全过滤 |
| `to_pinyin()` | app/common.php | 中文转拼音 |
| `generate_url_path()` | app/common.php | URL 路径生成 |
| `baidu_push()` | app/common.php | 百度收录推送 |
| `parse_seo_meta()` | app/common.php | SEO 元数据解析 |
| `render_prompt()` | app/common.php | 提示词渲染 |

### 3.2 函数签名详解

#### 1. ai_chat()
```php
function ai_chat(string $prompt, ?int $config_id = null, ?string $system = null, int $timeout = 60): array
```
- **功能**: AI 调用统一入口，支持 OpenAI/Anthropic/Ollama 协议
- **返回**: 成功返回 `['ok'=>true, 'content'=>'...', 'config_id'=>5]`
- **失败返回**: `['ok'=>false, 'error'=>'...', 'code'=>'...']`

#### 2. filter_landing_html()
```php
function filter_landing_html(string $html): string
```
- **功能**: HTML 安全过滤 + 结构规范化
- **处理步骤**:
  1. 移除空字节和 Unicode 控制字符
  2. 循环 html_entity_decode
  3. 移除危险标签
  4. 保留 `<style>`，移除危险样式
  5. 移除 on* 事件属性
  6. 移除 javascript: 协议
  7. 仅允许 `<img>` 的 data: 协议
  8. 对 style 属性执行过滤
  9. 不含 `<html>` 则自动包裹

#### 3. to_pinyin()
```php
function to_pinyin(string $text): string
```
- **功能**: 中文转拼音
- **实现**: 使用 `Transliterator::create('Any-Latin; Latin-ASCII; Lower()')`
- **Fallback**: intl 不可用 → PHP 拼音库 → page-{md5($text)}

#### 4. generate_url_path()
```php
function generate_url_path(string $keyword): string
```
- **功能**: 生成唯一 URL 路径段
- **处理**:
  1. to_pinyin() 转换
  2. 合并连字符，去首尾
  3. 截断 180 字符
  4. 检查冲突，追加数字后缀

#### 5. baidu_push()
```php
function baidu_push(string $type, array $urls): array
```
- **功能**: 百度收录推送
- **参数**: $type = 'normal' | 'fast'
- **返回**: `['ok'=>true, 'success'=>5, 'fail'=>0, 'detail'=>'...']`

#### 6. parse_seo_meta()
```php
function parse_seo_meta(string $html): array
```
- **功能**: 从 HTML 中解析 SEO 元数据
- **优先级**: 1. 注释标注 2. HTML 标签 3. 兜底策略
- **返回**: `['title'=>'...', 'keywords'=>'...', 'description'=>'...']`

#### 7. render_prompt()
```php
function render_prompt(string $template, array $vars): string
```
- **功能**: 提示词变量渲染
- **支持变量**: `{keyword}`、`{site_name}`、`{site_url}`、`{date}`、`{time}`
- **实现**: `str_replace(array_keys($vars), array_values($vars), $template)`

## 4. 文件清单

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| **修改文件** | backup/bak_all_initialize/1_drop_table.sql | 追加6张表的 DROP |
| **修改文件** | backup/bak_all_initialize/2_create_table.sql | 追加6张表的 CREATE |
| **修改文件** | backup/bak_all_initialize/3_insert_xphp_config_part1.sql | 追加7条配置 |
| **修改文件** | backup/bak_all_initialize/3_insert_xphp_menu_part1.sql | 追加4个菜单 |
| **修改文件** | app/common.php | 末尾追加 7 个函数 |

## 5. 开发注意事项

1. **SQL 格式**: 严格遵循现有 `backup/bak_all_initialize/` 目录下的格式，追加到现有文件末尾
2. **公共函数位置**: 必须追加在 `app/common.php` **文件末尾**
3. **依赖框架函数**: 使用框架提供的 `db()`、`cache()`、`encrypt()`、`decrypt()` 等函数
4. **安全性**: API Key 明文存储（按用户要求）
5. **flock 锁**: 锁文件路径为 `RUNTIME_PATH . '/cache/seo_lock_{type}.lock'`

## 6. 验证方法

- [ ] SQL 文件语法检查通过
- [ ] `php -l app/common.php` 语法检查通过
- [ ] 执行 SQL 能成功创建 6 张新表
- [ ] 各公共函数可正常调用

> 参考章节：统一需求文档 第 5-6 章

---

# 第四部分：S1 - AI配置管理

## 1. 功能概述

管理 AI 配置和提示词模板，为 AI 调用提供支持。
- AI 配置管理：CRUD、厂商预设、连接测试、轮询容错
- Prompt 模板管理：CRUD、类型区分、激活机制、变量支持

**依赖关系**: 依赖 S0 公共函数（ai_chat()、render_prompt()）

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

## 3. 控制器接口设计

### 3.1 AiConfig 控制器
- **继承**: app\admin\controller\Cp
- **模型**: common@ai_config
- **功能**:
  - index(): 列表
  - add(): 新增
  - edit(): 编辑
  - del(): 删除
  - state(): 状态切换
  - test(): 连接测试

### 3.2 Prompt 控制器
- **继承**: app\admin\controller\Cp
- **模型**: common@prompt
- **功能**:
  - index(): 列表
  - add(): 新增
  - edit(): 编辑
  - del(): 删除
  - state(): 状态切换
  - activate(): 激活模板

## 4. 视图模板设计

| 视图 | 路径 | 说明 |
|------|------|------|
| 列表页 | app/admin/view/ai_config/index.html | AI 配置列表 |
| 表单页 | app/admin/view/ai_config/_form.html | AI 配置表单 |
| 列表页 | app/admin/view/prompt/index.html | Prompt 列表 |
| 表单页 | app/admin/view/prompt/_form.html | Prompt 表单 |

### 视图规范
- 必须包含完整 lyear 布局（_head.html、sidebar.html、_header.html、footer.html）
- 使用框架提供的表单组件（form_select、form_radio）
- 状态切换使用 ajaxConfirm
- 新增/编辑使用 openModal 弹窗加载 _form.html

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

## 6. 依赖关系说明

| 依赖 | 说明 |
|------|------|
| S0 公共函数 | ai_chat()、render_prompt() |
| AiConfig 模型 | model('common@ai_config') |
| Prompt 模型 | model('common@prompt') |

## 7. 安全考虑

- API Key 明文存储（按用户要求）
- 连接测试 3 秒限频缓存
- 更新 AI 配置时，空 API Key 保留原值（Ollama 除外）
- 厂商预设提供 11 种，可扩展

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

## 9. 开发注意事项

1. **模型位置**: 放在 `app/common/model/`，使用 `model('common@ai_config')` 引用
2. **Cp 基类**: 继承 Cp 基类，方法签名必须完全兼容
3. **视图布局**: 必须包含完整的 lyear 布局（_head.html、sidebar.html、_header.html、footer.html）
4. **API Key**: 明文存储，更新时空值保留原值（Ollama 除外）
5. **厂商预设**: 提供 11 种预设，选择后自动填充

## 10. 验证方法

- [ ] 后台可访问 AI 配置页面
- [ ] 可添加、编辑、删除 AI 配置
- [ ] 连接测试功能正常
- [ ] 可管理 Prompt 模板
- [ ] Prompt 激活机制正常

> 参考章节：统一需求文档 第 4.1 章、第 5 章

---

# 第五部分：S2 - 关键词管理

## 1. 功能概述

管理关键词，支持 AI 拓词、CSV 导入导出、批量生成等功能。

**依赖关系**:
- 依赖 S0 公共函数（to_pinyin()、generate_url_path()、ai_chat()）
- 依赖 S1 AI 配置和 Prompt 模板

## 2. 数据模型设计

### 2.1 Keyword 模型
- **表名**: `xphp_keyword`
- **字段**: id, word, pinyin, source, group_id, status, has_page, create_time, update_time
- **索引**: uk_word, idx_pinyin, idx_status, idx_has_page, idx_source
- **模型位置**: app/common/model/Keyword.php

## 3. 控制器接口设计

### 3.1 Keyword 控制器
- **继承**: app\admin\controller\Cp
- **模型**: common@keyword
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

## 4. 视图模板设计

| 视图 | 路径 | 说明 |
|------|------|------|
| 列表页 | app/admin/view/keyword/index.html | 关键词列表 |
| 表单页 | app/admin/view/keyword/_form.html | 关键词表单 |

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

## 6. 依赖关系说明

| 依赖 | 说明 |
|------|------|
| S0 公共函数 | to_pinyin()、generate_url_path()、ai_chat() |
| S1 AI 配置 | ai_chat() 使用 |
| S1 Prompt 模板 | expand 类型用于拓词 |
| Keyword 模型 | model('common@keyword') |

## 7. 安全考虑

- CSV 导入：2MB 限制，最多 1000 行
- 公式注入防护
- 删除限制：仅可删除 status=0 且 has_page=0 的关键词
- 关联完整性检查在控制器层执行

## 8. 文件清单

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| 创建模型 | app/common/model/Keyword.php | 关键词模型 |
| 创建控制器 | app/admin/controller/Keyword.php | 关键词控制器 |
| 创建视图 | app/admin/view/keyword/index.html | 列表页 |
| 创建视图 | app/admin/view/keyword/_form.html | 表单页 |
| 修改路由 | route/admin.php | 追加路由 |

## 9. 开发注意事项

1. **拼音生成**: 添加关键词时自动调用 `to_pinyin()` 和 `generate_url_path()`
2. **冲突处理**: `generate_url_path()` 自动处理拼音冲突，追加数字后缀
3. **删除检查**: 控制器层检查关联完整性（框架 _before_delete 不检查 errors）
4. **CSV 安全**: 文件大小限制、MIME 类型验证、随机重命名、公式注入防护
5. **拓词提示**: 使用 prompt 模板的 `expand` 类型

## 10. 验证方法

- [ ] 后台可访问关键词管理页面
- [ ] 可添加、编辑、删除关键词
- [ ] 拼音 URL 自动生成正常
- [ ] 拼音冲突处理正常
- [ ] AI 拓词功能正常
- [ ] CSV 导入导出功能正常

> 参考章节：统一需求文档 第 4.2 章、第 5 章

---

# 第六部分：S3 - 页面生成管理

## 1. 功能概述

管理落地页生成和状态控制，支持 AI 生成、预览、状态切换等功能。

**依赖关系**:
- 依赖 S0 公共函数（ai_chat()、filter_landing_html()、parse_seo_meta()、render_prompt()）
- 依赖 S1 AI 配置和 Prompt 模板
- 依赖 S2 关键词管理

## 2. 数据模型设计

### 2.1 Page 模型
- **表名**: `xphp_page`
- **字段**: id, keyword_id, url_path, title, keywords, description, content, ai_config_id, prompt_id, status, view_count, is_pushed_normal, is_pushed_fast, create_time, update_time
- **索引**: idx_keyword_id, idx_status, idx_status_is_pushed_normal, uk_url_path
- **模型位置**: app/common/model/Page.php

## 3. 控制器接口设计

### 3.1 Page 控制器
- **继承**: app\admin\controller\Cp
- **模型**: common@page
- **功能**:
  - index(): 列表
  - add(): 新增
  - edit(): 编辑
  - del(): 删除
  - state(): 状态切换
  - preview(): 预览
  - generate(): 手动生成
  - rewrite(): AI 重写

## 4. 视图模板设计

| 视图 | 路径 | 说明 |
|------|------|------|
| 列表页 | app/admin/view/page/index.html | 页面列表 |
| 表单页 | app/admin/view/page/_form.html | 页面表单 |
| 预览页 | app/admin/view/page/preview.html | 页面预览 |

## 5. 业务流程说明

### 5.1 页面生成流程
1. 选择关键词（has_page=0）
2. 调用 render_prompt() 渲染 page 类型模板
3. 调用 ai_chat() 生成内容
4. 检查内容长度（<500 视为失败）
5. 调用 parse_seo_meta() 解析 SEO 元数据
6. 调用 filter_landing_html() 安全过滤
7. 自动包裹 HTML（不含 `<html>` 的话）
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

## 6. 依赖关系说明

| 依赖 | 说明 |
|------|------|
| S0 公共函数 | ai_chat()、filter_landing_html()、parse_seo_meta()、render_prompt() |
| S1 AI 配置 | ai_chat() 使用 |
| S1 Prompt 模板 | page 类型用于生成 |
| S2 关键词 | 关键词关联 |
| Page 模型 | model('common@page') |

## 7. 安全考虑

- 落地页内容经过 filter_landing_html() 9 步安全过滤
- 发布后 url_path 不可修改（前端 readonly + 后端强制拒绝）
- 页面增删改时自动清除前台缓存
- 内容质量控制：<500 字符视为失败

## 8. 文件清单

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| 创建模型 | app/common/model/Page.php | 页面模型 |
| 创建控制器 | app/admin/controller/Page.php | 页面控制器 |
| 创建视图 | app/admin/view/page/index.html | 列表页 |
| 创建视图 | app/admin/view/page/_form.html | 表单页 |
| 创建视图 | app/admin/view/page/preview.html | 预览页 |
| 修改路由 | route/admin.php | 追加路由 |

## 9. 开发注意事项

1. **URL 不可变性**: 发布后 `url_path` 后端强制拒绝修改
2. **事务一致性**: 页面创建/删除时，同一事务内同步更新 `keyword.has_page`
3. **内容质量**: 落地页生成内容 < 500 字符视为失败（调用方检查）
4. **提示词注入**: Prompt 模板中使用 `<keyword>{keyword}</keyword>` 标签包裹
5. **缓存清理**: 页面增删改时自动清除前台缓存

## 10. 验证方法

- [ ] 后台可访问页面管理页面
- [ ] 可手动生成单页面
- [ ] 页面状态转换正常
- [ ] 页面预览功能正常
- [ ] SEO 元数据解析正常
- [ ] has_page 一致性保证

> 参考章节：统一需求文档 第 4.3 章、第 5 章

---

# 第七部分：S4 - 定时任务管理

## 1. 功能概述

管理定时任务和 Cron 触发，支持批量生成页面、百度推送、Sitemap 生成等功能。

**依赖关系**:
- 依赖 S0 公共函数（ai_chat()、baidu_push()）
- 依赖 S3 页面管理

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

## 3. 控制器接口设计

### 3.1 Task 控制器（后台）
- **继承**: app\admin\controller\Cp
- **模型**: common@task
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

## 4. 视图模板设计

| 视图 | 路径 | 说明 |
|------|------|------|
| 列表页 | app/admin/view/task/index.html | 任务列表 |
| 表单页 | app/admin/view/task/_form.html | 任务表单 |
| 日志页 | app/admin/view/task/log.html | 执行日志 |

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

## 6. 依赖关系说明

| 依赖 | 说明 |
|------|------|
| S0 公共函数 | ai_chat()、baidu_push() |
| S3 页面管理 | 页面生成 |
| Task 模型 | model('common@task') |
| TaskLog 模型 | model('common@task_log') |

## 7. 安全考虑

- Cron 触发：密钥验证（≥32 位）+ 60 秒限频 + IP 白名单
- flock 锁：防止并发执行
- 任务超时：set_time_limit(task.timeout)
- 日志清理：30 天前日志自动清理
- 删除限制：存在执行日志的任务不可删除

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

## 9. 开发注意事项

1. **任务唯一性**: `type` 字段 UNIQUE，每种任务全局仅一条
2. **超时控制**: `set_time_limit(task.timeout)`
3. **日志清理**: 30 天前日志自动清理
4. **Sitemap 脏标记**: 页面发布/下线/删除时设置缓存 `sitemap_dirty=true`
5. **手动触发**: 后台支持手动触发单任务执行

## 10. 验证方法

- [ ] 后台可访问任务管理页面
- [ ] 可配置定时任务
- [ ] Cron 触发接口正常
- [ ] 任务执行日志正常
- [ ] flock 锁机制正常
- [ ] 百度推送功能正常
- [ ] Sitemap 生成正常

> 参考章节：统一需求文档 第 4.4 章、第 5 章

---

# 第八部分：S5 - 前台展示

## 1. 功能概述

实现前台落地页展示、首页改造、Sitemap 生成等功能。

**依赖关系**:
- 依赖 S2 关键词管理
- 依赖 S3 页面管理

## 2. 数据模型设计

### 2.1 使用的模型
- **Keyword**: model('common@keyword') - 读取关键词信息
- **Page**: model('common@page') - 读取页面内容

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

## 4. 视图模板设计

| 视图 | 路径 | 说明 |
|------|------|------|
| 首页 | template/default/index/index.html | 改造现有首页 |
| robots.txt | public/robots.txt | 修改现有文件 |

### robots.txt 内容
```
User-agent: *
Allow: /keyword/
Disallow: /admin/
Disallow: /cron/
Sitemap: {site_url}/sitemap.xml
```

## 5. 业务流程说明

### 5.1 落地页访问流程
1. 访问 /keyword/{pinyin}.html
2. 根据 pinyin 查询 page 表，url_path = pinyin
3. 检查 status=1，否则返回 404
4. 输出 CSP 头（禁止脚本执行）
5. 输出 Canonical URL
6. 原子递增 view_count
7. 输出页面 content
8. 追加相关推荐（5 条页面链接，在 `</body>` 前）

### 5.2 Sitemap 生成
- **文件**: public/sitemap.xml
- **内容**: 所有 status=1 的页面
- **格式**: 标准 sitemap XML
- **脏标记**: 页面变更时设置 sitemap_dirty=true
- **任务**: 由 S4 的 sitemap 任务定时生成

## 6. 依赖关系说明

| 依赖 | 说明 |
|------|------|
| S2 关键词管理 | 关键词信息 |
| S3 页面管理 | 页面内容 |
| Keyword 模型 | model('common@keyword') |
| Page 模型 | model('common@page') |

## 7. 安全考虑

- 前台仅查询 status=1 的页面
- 草稿/不存在的页面返回 404
- CSP 头禁止脚本执行
- robots.txt 禁止爬虫访问后台和 cron
- view_count 原子递增

## 8. 文件清单

| 操作 | 文件路径 | 说明 |
|------|---------|------|
| 修改控制器 | app/index/controller/Index.php | 追加 dispatch() 方法 |
| 修改视图 | template/default/index/index.html | 首页改造 |
| 修改文件 | public/robots.txt | SEO 配置 |
| 修改路由 | route/index.php | 追加落地页路由 |

## 9. 开发注意事项

1. **落地页渲染**: 从数据库读取 `content` 字段直接输出，无单独模板
2. **相关推荐**: 页面底部自动追加 5 条同站已发布页面链接
3. **CSP 头**: 必须输出，禁止脚本执行
4. **404 处理**: 页面不存在或 status=0 时，返回 HTTP 404 状态码 + 简单提示页
5. **首页缓存**: 页面发布/下线/删除时清除首页缓存

## 10. 验证方法

- [ ] 落地页 URL 可正常访问
- [ ] 首页展示最新页面列表
- [ ] Sitemap.xml 可正常生成
- [ ] Canonical URL 正确输出
- [ ] CSP 头正确输出
- [ ] view_count 正确递增
- [ ] robots.txt 配置正确
- [ ] 草稿页返回 404

> 参考章节：统一需求文档 第 4.5 章、第 4.6 章、第 7 章

---

# 第九部分：安全与SEO要求汇总

## 1. 安全要求

- 前台只查询 `status=1` 的页面，草稿不可访问
- 落地页输出 CSP 头，禁止脚本执行
- Cron 触发需要密钥验证 + 60秒限频 + flock锁
- 批量生成并发控制（flock锁，单次最多3个）
- CSV 导入：2MB 限制、MIME 验证、公式注入防护
- 删除限制：关键词需 status=0 且 has_page=0；页面需先下线；有日志的任务不可删

## 2. SEO 要求

- 落地页 URL 格式：`/keyword/{pinyin}.html`
- 每个落地页必须输出 Canonical URL
- robots.txt 禁止后台访问，开放 keyword 目录
- Sitemap.xml 动态生成（脏标记策略）

---

# 第十部分：参考文档

| 文档 | 说明 |
|------|------|
| SEO 自动化落地页系统-统一需求文档.md | 完整需求说明 |
