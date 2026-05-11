# 模板语法文档

## 目录

- [模板基础](#模板基础)
- [变量输出](#变量输出)
- [条件判断](#条件判断)
- [循环遍历](#循环遍历)
- [模板包含](#模板包含)
- [模板布局](#模板布局)
- [函数过滤器](#函数过滤器)
- [常用标签](#常用标签)

---

## 模板基础

### 模板文件位置

视图文件统一放置在应用的 view 目录下，按照控制器名称组织子目录结构。例如，admin 应用下 User 控制器的视图文件存放在 app/admin/view/user/ 目录中。index 方法对应的模板文件为 index.html，add 方法对应的模板文件为 add.html。这种约定俗成的组织方式使得模板文件与控制器方法的对应关系一目了然，便于维护和查找。

### 模板配置

模板引擎的行为可以在配置文件中进行调整，主要配置项包括左右定界符、编译缓存、模板后缀等。左定界符和右定界符定义了模板标签的边界符号，默认使用花括号作为定界符。模板后缀指定视图文件的文件扩展名，默认配置为 .html。编译缓存控制模板编译后的缓存文件存放位置，合理配置可以提升模板渲染性能。

### 模板渲染

控制器通过 view() 函数将数据传递给视图并完成渲染。view() 函数支持多种调用方式：不带参数时使用当前控制器和方法名称定位模板；传入字符串时指定要渲染的模板路径；传入数组时指定多个变量的值。控制器向视图传递的数据在模板中通过变量形式使用，变量名称即为数组的键名。

```php
// 控制器中渲染视图
public function index()
{
    $data = ['title' => '用户列表', 'list' => $users];
    return view('', $data);
}
```

```html
<!-- 模板中使用变量 -->
<h1>{$title}</h1>
<table>
    {foreach $list as $user}
    <tr>
        <td>{$user.username}</td>
    </tr>
    {/foreach}
</table>
```

---

## 变量输出

### 基本输出

模板中最基本的语法是变量输出，使用左右定界符包裹变量名称即可输出变量的值。变量可以是字符串、数字、数组等类型。如果是数组变量，使用点语法访问数组元素或对象属性。如果需要对变量进行函数处理，可以使用管道符 `|` 调用函数，如 `{$content|raw}` 会调用 PHP 的 `raw()` 函数处理变量值。

```html
<!-- 输出字符串变量 -->
<p>{$username}</p>

<!-- 输出数组元素 -->
<p>{$user.username}</p>
<p>{$user.email}</p>

<!-- 输出嵌套数组 -->
<p>{$data.profile.name}</p>

<!-- 使用函数处理变量 -->
<div>{$content|raw}</div>

<!-- 输出系统常量（双下划线包裹，无需花括号） -->
<p>__ROOT__</p>
<p>__STATIC__</p>
<p>__HOST__</p>
```

### 默认值输出

当变量不存在或值为空时，可以指定默认值进行输出。使用 `|default=默认值` 语法或 `??` 空合并运算符。这种方式可以避免页面显示空白或 undefined 字符串，提升用户体验。

```html
<!-- 使用 default 过滤器 -->
<p>{$username|default='匿名用户'}</p>

<!-- 使用 ?? 空合并运算符 -->
<p>{$count ?? 0}</p>
<p>{$user.nickname ?? '未设置'}</p>
```

### 运算符输出

模板支持三元运算符，可以在变量输出时进行简单条件判断。三元运算符使用 `{条件 ? 值1 : 值2}` 语法直接输出结果。

```html
<!-- 三元运算输出 -->
<p>{$status == 1 ? '正常' : '停用'}</p>

<!-- 使用 ?? 空合并运算符 -->
<p>{$username ?? '匿名用户'}</p>
<p>{$user.nickname ?? '未设置'}</p>
```

---

## 条件判断

### if 语句

条件语句用于根据不同条件显示不同的内容，提升模板的灵活性。if 语句必须以 /if 标签结束，标签之间是条件为真时输出的内容。elseif 和 else 标签用于处理多个条件分支。条件表达式支持多种比较运算符，包括等于、不等于、大于、小于等。

```html
<!-- 简单条件判断 -->
{if $user.status == 0:}
<p>停用用户</p>
{elseif $user.status == 1:}
<p>正常用户</p>
{else:}
<p>未知状态</p>
{/if}

<!-- 多条件组合 -->
{if $user.level > 0 && $user.status == 1:}
<p>VIP用户</p>
{/if}

<!-- 字符串比较 -->
{if $user.gender == '男':}
<p>男性用户</p>
{elseif $user.gender == '女':}
<p>女性用户</p>
{/if}
```

### 比较运算符

条件表达式中可以使用以下标准 PHP 比较运算符进行数据比较：等于（==）、不等于（!= 或 <>）、大于（>）、小于（<）、大于等于（>=）、小于等于（<=）。框架模板引擎使用标准 PHP 比较语法，无需学习额外的简写运算符。

```html
<!-- 使用比较运算符 -->
{if $score >= 60:}
<p>及格</p>
{/if}

<!-- 使用不等于运算符 -->
{if $user.status != 0:}
<p>用户已停用</p>
{/if}
```

---

## 循环遍历

### foreach 循环

foreach 标签用于遍历数组或集合，是模板中使用频率最高的循环结构。每次循环时，当前元素的值赋值给 as 后面的变量名，键名赋值给可选的 key 变量。循环体内容在 foreach 和 /foreach 标签之间定义，可以重复输出多条记录。

```html
<!-- 遍历列表 -->
{foreach $list as $vo}
<p>{$vo.username}</p>
{/foreach}

<!-- 同时获取键名和值 -->
{foreach $list as $key => $vo}
<p>{$key} - {$vo.name}</p>
{/foreach}

<!-- 遍历关联数组 -->
{foreach $nav as $field => $value}
<p>{$field}: {$value}</p>
{/foreach}

<!-- 判断数组是否为空 -->
{empty $list:}
<p>暂无数据</p>
{else:}
<p>有数据</p>
{/empty}
```

### 循环遍历

框架的模板引擎使用简洁的原生 PHP 语法处理循环遍历，无需学习特殊的模板标签。模板中的循环直接使用 PHP 代码实现，灵活性更高。

### 循环变量

循环结构中可以使用一些特殊变量获取循环的元信息，如数组长度、数组对象等。这些变量直接通过 `$list->toArray()` 或 `$list->links()` 等方法获取。

```html
<!-- 获取分页数据总数 -->
<p>共 {$list->total} 条记录</p>

<!-- 生成分页链接 -->
{$list->links()|raw}
```

> 注意：`|raw` 会调用 PHP 的 `raw()` 函数处理输出。如果项目中未定义 `raw()` 函数，分页链接可能需要使用 `{:echo($list->links())}` 语法输出。

---

## 模板包含

### include 标签

include 标签用于将其他模板文件包含到当前模板中，实现代码复用。公共的页面头部、底部、侧边栏等部分通常抽取为独立模板，通过 include 标签引入。include 标签会先加载被包含模板的内容，然后与当前模板合并处理。

```html
<!-- 包含公共头部 -->
{include file='public/_head.html'}

<!-- 包含其他控制器的模板 -->
{include file='user/sidebar.html'}

<!-- 带参数的包含（使用 [占位符] 语法） -->
<div>{include file='public/item.html' item=$data}</div>
```

### 传递变量

include 标签支持向被包含模板传递变量参数。使用花括号语法在标签内声明变量，被包含模板可以直接使用这些变量。这种方式使得公共模板可以接受不同参数，呈现不同内容。

```html
<!-- 传递变量（被包含模板中使用 [item] 占位符接收） -->
{include file='public/item.html' item=$data}

<!-- 传递多个变量 -->
{include file='public/card.html' title=$title content=$content}

<!-- 在被包含模板中使用变量 -->
<!-- public/item.html 中使用 [item] 占位符，渲染时替换为传入的值 -->
<div class="item">
    <h3>{$item.title}</h3>
    <p>{$item.content}</p>
</div>
```

---

## 模板布局

### 布局模板

模板布局是一种页面结构复用方案，将页面分为整体框架和内容区域两部分。布局模板定义页面的整体结构，内容区域使用占位符标记。具体页面的内容会替换占位符位置，实现统一布局下的个性化内容。

```html
<!-- layout.html 布局模板 -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$title|default='后台管理系统'}</title>
    {include file="public/_head" /}
</head>
<body>
    <div class="container">
        <header>{include file="public/_header" /}</header>
        <main>
            {__CONTENT__}
        </main>
        <footer>{include file="public/_footer" /}</footer>
    </div>
</body>
</html>
```

### 使用布局

子页面通过 layout 标签声明要使用的布局模板，框架会自动将页面内容嵌入到布局模板的指定位置。这种方式特别适合后台管理系统，保持所有页面具有统一的导航和布局结构。

```html
<!-- 使用布局 -->
{layout name="layout.html"}

<!-- 页面内容会自动替换 {__CONTENT__} 位置 -->
<div class="page-content">
    <h1>用户列表</h1>
    <table>...</table>
</div>
```

---

## 函数过滤器

### 内置函数

模板引擎内置了多个常用函数，可以在变量输出时直接调用。date 函数用于格式化时间戳为日期字符串，支持自定义日期格式。number_format 函数用于格式化数字，可以设置小数位数和千分位分隔符。strlen 函数计算字符串长度，empty 函数判断变量是否为空。

```html
<!-- 格式化日期 -->
<p>{$create_time|date='Y-m-d'}</p>
<p>{$create_time|date='Y年m月d日 H:i'}</p>

<!-- 格式化数字 -->
<p>{$price|number_format=2}</p>
<p>{$count|number_format=0}</p>

<!-- 字符串处理 -->
<p>{$name|trim}</p>
<p>{$content|substr=0,100}</p>
```

### 过滤器使用说明

模板引擎通过 `|函数名` 语法调用 PHP 函数处理变量值。每个变量只能调用一个过滤器函数。如果需要多步处理，建议在控制器中完成数据预处理后再传递给模板。

```html
<!-- 单个过滤器 -->
<p>{$content|trim}</p>

<!-- 带参数的过滤器 -->
<p>{$create_time|date='Y-m-d'}</p>
<p>{$price|number_format=2}</p>

<!-- 使用 ?? 空合并运算符提供默认值 -->
<p>{$user.nickname ?? '匿名用户'}</p>
```

### 自定义过滤器

开发者可以根据业务需求扩展自定义过滤器。过滤器函数放置在模块的 common.php 文件中，在模板中通过 `|函数名` 语法直接调用。定义后即可在模板中像使用内置过滤器一样使用自定义过滤器。

```php
// app/admin/common.php
function status($value)
{
    $status = [0 => '停用', 1 => '正常'];
    return $status[$value] ?? '未知';
}
```

```html
<!-- 模板中使用自定义过滤器 -->
<p>{$user.status|status}</p>
```

---

## 常用标签

### php 标签

php 标签允许在模板中执行简单的变量赋值操作。框架的模板引擎会解析这些 PHP 代码。这种方式可以处理简单的逻辑，但也应谨慎使用以免破坏模板的结构清晰性。

```html
<!-- 变量赋值 -->
{php $menu = widget('menu')->get()}

<!-- 使用三元运算符 -->
<span class="{if $vo['status']==1:}text-success{else:}text-secondary{/if}">
```

### literal 标签

literal 标签用于输出大括号等模板定界符本身，而不是作为模板语法解析。当页面内容中包含 JavaScript 代码或 CSS 代码，且这些代码中使用了与模板定界符相同的符号时，需要使用 literal 标签包裹。

```html
<!-- 输出 JavaScript 对象字面量 -->
{literal}
<script>
var config = {
    name: '{$name}',
    value: 100
};
</script>
{/literal}
```


