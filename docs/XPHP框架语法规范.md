# XPHP 框架语法规范（基于源码 v6.1.1）

> **适用版本**: XPHP v6.1.1 | PHP >= 8.1
> **说明**: 以下所有规范均来自项目实际源码，不可猜测或套用其他框架。

---

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

---

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
| `openModal(url, title, size)` | 弹窗加载表单页，size='sm'\|'lg'\|'xl' | `openModal('{:url(\'add\')}', '新增')` |
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

---

## 3. 控制器规范

### 3.1 Cp 基类（app/admin/controller/Cp.php）

后台控制器必须继承 `Cp` 基类。Cp 提供的标准方法：

| 方法 | 签名 | 说明 |
|------|------|------|
| `index()` | `public function index()` | 列表，调用 `model($this->model)->paginate()` |
| `add(array $req)` | `public function add(array $req)` | 新增，POST时 `model($this->model)->save($req)` |
| `edit(int $id, array $req)` | `public function edit(int $id, array $req)` | 编辑 |
| `del(string $ids)` | `public function del(string $ids)` | 删除，仅删 status=0 的记录。`select()` 返回普通数组而非模型对象集合，需用 `find()` + `del($id)` 逐个删除，`$id` 需强制转换为 `(int)$id` |
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

---

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

---

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

---

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

---

## 7. 路由配置

路由文件：`route/admin.php`（后台）、`route/index.php`（前台）。

### 7.1 后台路由

后台使用框架默认自动路由（`/控制器/方法`），无需额外配置：

```php
// route/admin.php
return [];
```

### 7.2 前台路由

前台需在 `route/index.php` 中添加自定义路由：

```php
return [
    'keyword/([a-zA-Z0-9\-_%+]+)' => 'index/dispatch/path/$1',
    'cron/([a-zA-Z0-9\-_]+)'      => 'cron/index/key/$1',
];
```

---

## 8. 框架约束与注意事项

1. **model() 分隔符**: 使用 `@`，不是 `.`（`model('common@keyword')`）
2. **PHP 8.1+ LSP**: 子类方法签名必须完全兼容父类
3. **模板编译缓存**: 模板修改后必须清除 `runtime/admin/view/` 编译缓存
4. **APP_DEBUG=false**: 错误被完全隐藏
5. **表前缀**: `xphp_`，模型中 `$table` 不含前缀
6. **自动时间**: 模型默认自动写入 `create_time` 和 `update_time`（int类型）
7. **删除限制**: Cp 基类 `del()` 方法只删除 `status=0` 的记录。`select()` 返回普通数组而非模型对象集合，需用 `find()` + `del($id)` 逐个删除，`$id` 需强制转换为 `(int)$id`
8. **`|default` 修饰符限制**: 仅在独立变量输出时有效（`{$vo.key|default='x'}`），在函数参数内无效（`{:form_radio('name',$vo['key']|default='x')}`）。解决方案：用 `{php $val = !empty($vo['key']) ? $vo['key'] : 'x';}` 预处理
9. **`?:` 短三元运算符不被支持**: `{:func() ?: 'default'}` 会报语法错误。改用 `{if func():}{:func()}{else:}default{/if}`
10. **`{:CONSTANT}` 不被编译**: 无括号的表达式不匹配模板正则。改为 `{:echo(CONSTANT)}`
11. **`{literal}` 块内模板标签原样输出**: `{:url()}` 在 `{literal}` 中不被编译。用 `{/literal}{:url(...)}{literal}` 包裹
12. **模态框表单中不能使用 `<script>` 标签**: `openModal()` 使用 `stripScripts()` 移除所有 `<script>` 标签。改用内联 `onchange`/`onclick` 事件处理器
13. **验证规则中不能使用含 `|` 的正则**: 框架用 `explode('|', $rules)` 分割验证规则，正则中的 `|` 会被误分割。改用自定义验证方法