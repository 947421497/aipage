# 开发文档

## 目录

- [开发规范](#开发规范)
- [控制器开发](#控制器开发)
- [模型开发](#模型开发)
- [视图开发](#视图开发)
- [中间件开发](#中间件开发)
- [CLI命令开发](#cli命令开发)
- [扩展开发](#扩展开发)
- [代码生成器](#代码生成器)
- [调试指南](#调试指南)

---

## 开发规范

### 命名规范

为确保代码的一致性和可维护性，本框架制定以下命名规范。遵循统一的命名约定可以使代码更加清晰，便于团队协作和后期维护。

#### 文件命名

控制器的文件命名采用首字母大写的驼峰式命名方式，文件名必须与类名保持一致。例如，UserController.php 对应 UserController 类，LoginController.php 对应 LoginController 类。视图文件统一使用小写字母命名，多个单词之间使用下划线分隔，如 user_list.html、login_form.html。模型文件采用首字母大写的驼峰式命名，如 User.php、Menu.php。

#### 类命名

所有类名必须使用命名空间，并且遵循 PSR-4 自动加载规范。类的命名采用首字母大写的驼峰式命名方式，命名空间与实际目录结构保持一致。例如，app\admin\controller 命名空间下的 Index 类完整名称为 app\admin\controller\Index，app\index\controller 命名空间下的 User 类完整名称为 app\index\controller\User。

#### 方法命名

控制器中的操作方法统一使用小写字母命名，多个单词之间使用下划线分隔或采用驼峰式命名。建议使用动作式的命名方式，如 index、login、register、save、delete 等。模型中的方法命名应清晰表达其功能，如 findByUsername、getUserList、updateStatus 等。

#### 变量命名

变量命名应具有描述性，使用小写字母命名，多个单词之间使用下划线分隔。临时变量可使用简短的命名，如 $i、$j 用于循环计数，$data、$result 用于存储数据。类成员变量使用 $this->propertyName 的形式，静态变量使用 self::$propertyName 的形式。

### 编码规范

#### PHP代码规范

所有 PHP 文件必须在文件开头声明命名空间，禁止使用裸函数或裸类。所有类文件必须使用 strict_types 声明，即在文件顶部添加 declare(strict_types=1)。条件语句和循环语句的代码块必须使用大括号包裹，即使只有一行代码也应保持一致。字符串拼接优先使用单引号，只有在需要变量解析或转义字符时才使用双引号。

#### 注释规范

类和方法必须添加文档注释（PHPDoc），使用 /** */ 格式。注释应包含类或方法的简要说明、参数说明和返回值说明。关键业务逻辑处应添加注释说明，便于后期维护。单行注释使用 // 格式，位于代码上方或行尾。临时调试代码和待完成代码应添加相应标记，如 // TODO、// FIXME 等。

#### 缩进与空格

代码缩进统一使用 4 个空格，禁止使用 Tab 字符。所有二元运算符两侧必须添加空格，如 $a = $b + $c。函数调用时参数列表中逗号后面必须添加空格，前面不需要空格。数组定义中元素之间使用逗号分隔，最后一个元素后面可以添加逗号（尾随逗号）。

### 目录结构规范

#### 应用目录

每个应用（admin、index、install 等）应保持统一的目录结构。controller 目录存放所有控制器文件，model 目录存放所有模型文件，view 目录存放所有视图文件。config 目录存放应用级别的配置文件，command 目录存放应用特有的命令行工具。common.php 文件用于定义应用级别的公共函数。

#### 控制器规范

控制器文件应放置在 app/{应用名}/controller/ 目录下，每个控制器对应一个文件。控制器类必须继承基础类或实现相应接口。控制器的公共方法对应一个 URL 操作，方法名称应与 URL 中的操作名称对应。控制器中应避免编写复杂的业务逻辑，业务逻辑应下沉到模型层。

#### 模型规范

模型文件应放置在 app/{应用名}/model/ 目录下。模型类应继承框架的 Model 基类，以获得数据库操作能力。模型名称应与数据表名称对应，如 User 模型对应 xphp_user 表。模型中定义数据表的字段信息，以便框架进行数据验证和自动完成。

---

## 控制器开发

### 控制器基础

控制器是 MVC 架构中的核心组件，负责接收用户请求、调用模型处理业务逻辑、选择视图渲染响应。控制器位于 app/{应用名}/controller/ 目录下，每个控制器对应一个独立的 PHP 文件。控制器的命名空间为 app\{应用名}\controller，类名必须与文件名保持一致，并且首字母大写。

#### 创建控制器

首先创建控制器文件，文件名应与类名匹配。例如，创建 UserController.php 文件，对应的类名为 UserController。在文件中声明正确的命名空间，然后定义类并继承基础控制器类。基础控制器通常提供常用的方法，如 success、error、redirect 等。

```php
<?php
declare(strict_types=1);

namespace app\admin\controller;

class User extends Cp
{
    protected string $model = 'common.user';
    protected string $order = 'status DESC,id DESC';

    protected function _where(): array
    {
        $where = [];
        $name = input('name', '', 'clear_html');
        if (!empty($name)) {
            $where[] = ['username|nickname', 'like', '%' . $name . '%'];
        }
        $level = input('level', 0, 'intval');
        if ($level > 0) {
            $where[] = ['level', '=', $level];
        }
        return $where;
    }
}
```

Cp 基类控制器已自动实现 index、add、edit、del、state 等方法，无需手动编写。如果需要自定义控制器方法，注意方法参数需要声明类型：

```php
<?php
declare(strict_types=1);

namespace app\admin\controller;

class Menu extends Cp
{
    protected string $model = 'menu';
    protected int $limit = 0;

    public function index()
    {
        $list = \app\admin\model\Menu::getTree();
        return view()->with('list', $list);
    }

    public function add(array $req)
    {
        if ($this->isPost()) {
            $r = pdo()->trans(function () use ($req) {
                model($this->model)->save($req);
            });
            $this->_jump(['添加成功', '添加失败'], $r, $this->jumpUrl);
        }
        return view();
    }
}
```

### 请求处理

框架提供统一的请求处理机制，通过 `input()` 助手函数获取请求参数。请求实例封装了 GET、POST、PUT、DELETE 等各种请求方式的数据，并提供了便捷的数据获取方法。开发者应优先使用 `input()` 函数来获取用户提交的数据，而不是直接访问 `$_GET`、`$_POST` 等超全局变量。

#### 获取请求数据

框架提供统一的请求处理机制，通过 `input()` 助手函数获取请求参数。开发者应优先使用 `input()` 函数来获取用户提交的数据，而不是直接访问 `$_GET`、`$_POST` 等超全局变量。使用 `$this->isPost()` 方法判断当前请求是否为 POST 请求。

```php
// 获取指定参数，带默认值和过滤
$id = input('id', 0, 'intval');
$name = input('name', '', 'clear_html');

// 判断请求类型
if ($this->isPost()) {
    $data = input('post.');
}

// 获取所有参数
$params = input();
```

#### 响应处理

控制器方法的返回值将作为响应内容发送给客户端。框架支持多种响应方式，包括返回视图、返回 JSON、页面跳转等。使用 view() 函数渲染视图并返回，使用 json() 函数返回 JSON 格式数据，使用 redirect() 函数进行页面跳转。success 和 error 方法封装了常用的操作结果反馈，自动处理跳转和提示信息。

```php
// 返回视图
return view();
return view('user/list');
return view('', ['data' => $data]);

// 返回JSON
return json(['code' => 0, 'msg' => 'success', 'data' => $data]);

// 页面跳转（Cp基类中使用 _jump 方法）
$this->_jump(['操作成功', '操作失败'], $result, $this->jumpUrl);
```

### 路由与URL

框架采用约定俗成的路由方式，URL 与控制器、方法的对应关系遵循一定规则。默认情况下，URL 的第一段为控制器名称（不含 Controller 后缀），第二段为方法名称，其余部分作为参数传递。例如，/admin/user/edit/id/1 对应 admin 应用下 User 控制器的 edit 方法，id 参数为 1。

#### URL 生成

使用 url() 助手函数可以生成带正确路径和参数的 URL。该函数自动处理路由配置、伪静态后缀、域名等问题。生成后台管理 URL 时，应使用 admin 前缀标识应用路径。url() 函数支持路由别名和参数绑定，使 URL 更加简洁美观。

```php
// 生成当前模块的URL
echo url('user/index');  // /index/user/index.html
echo url('user/edit', ['id' => 1]);  // /index/user/edit/id/1.html

// 生成指定模块的URL
echo url('admin/user/index');  // /admin/user/index.html
echo url('admin/user/edit', ['id' => $id]);  // /admin/user/edit/id/5.html
```

---

## 模型开发

### 模型基础

模型是数据访问层的抽象，负责与数据库进行交互。模型封装了数据的增删改查操作，提供面向对象的数据访问接口。每个模型对应数据库中的一张表，模型的属性和方法与表结构密切相关。良好的模型设计可以大大简化数据操作，提高代码的可复用性。

#### 创建模型

模型文件放置在 app/{应用名}/model/ 目录下，文件名与类名对应。模型类需要继承框架的 Model 基类。模型中可以定义表名、主键名、时间戳字段等属性，覆盖模型的默认行为。模型中的方法用于封装各种数据操作，包括条件查询、数据验证、业务逻辑等。

```php
<?php
declare(strict_types=1);

namespace app\admin\model;

use xphp\core\Model;

class User extends Model
{
    protected string $table = 'user';
    protected string $pk = 'id';
    protected array $validate = [
        ['username', 'username|unique', '用户名4-12位|用户名已存在', FV_MUST, AC_INSERT],
        ['nickname', 'required|unique', '昵称必须|昵称已存在', FV_MUST, AC_BOTH],
        ['password', 'required', '请输入密码', FV_MUST, AC_INSERT],
    ];
    protected array $auto = [
        ['password', 'setPwd', 'method', FV_VALUE, AC_BOTH],
        ['level', '1', 'string', FV_MUST, AC_INSERT],
        ['status', '1', 'string', FV_MUST, AC_INSERT],
    ];
}
```

### 数据库操作

框架提供链式查询构建器，支持灵活的数据查询和操作。查询构建器的方法可以连续调用，形成完整的查询语句。所有查询方法都会返回查询构建器实例或结果集，需要调用 select、find、insert、update、delete 等方法执行最终操作。

#### 查询操作

使用 where() 方法添加查询条件，支持多种条件表达式。field() 方法指定要查询的字段，默认为全部字段。order() 方法指定排序规则，paginate() 方法实现分页查询。limit() 方法限制返回记录数量，find() 方法返回单条记录，select() 方法返回记录集合。

```php
// 查询单条记录
$user = model('common.user')->find(1);
$user = db('user')->where('username', 'admin')->find();

// 查询多条记录
$list = db('user')->where('status', 1)->select();
$list = db('user')->where('level', '>=', 1)->order('id', 'desc')->select();

// 条件查询
$list = db('user')->where('status', 1)->where('level', '>', 0)->select();
$list = db('user')->where('id', 'in', [1, 2, 3])->select();
$list = db('user')->where('username', 'like', '%admin%')->select();

// 统计查询
$count = db('user')->where('status', 1)->count();
$maxId = db('user')->max('id');

// 分页查询
$list = model('common.user')->paginate(10);
```

#### 新增操作

使用 save() 方法可以新增单条记录，该方法会自动判断是插入还是更新。insert() 方法用于批量插入数据，insertGetId() 方法在插入后返回自增 ID。save() 方法接收数组或模型实例数据，自动处理时间戳字段。

```php
// 使用模型新增
$user = model('common.user');
$user->save([
    'username' => 'test',
    'nickname' => '测试用户',
    'password' => '123456',
]);

// 使用 db 助手函数
db('user')->insert([
    'username' => 'test2',
    'nickname' => '测试用户2',
    'password' => md5('123456'),
    'create_time' => time()
]);
```

#### 更新操作

使用 save() 方法更新已存在的模型实例，或者使用 update() 方法直接更新数据。更新操作默认会更新 update_time 字段（如果模型配置了时间戳）。where() 条件必须明确指定，避免更新错误的数据。

```php
// 更新模型实例
$user = model('common.user')->find(1);
$user->save(['nickname' => '新昵称']);

// 使用 db 助手函数更新
db('user')->where('id', 1)->update(['nickname' => '新昵称']);
db('user')->where('status', 0)->update(['status' => 1]);
```

#### 删除操作

使用 delete() 方法删除模型实例，使用 destroy() 方法删除指定 ID 的记录。删除操作应该谨慎执行，建议在删除前进行必要的权限验证和数据备份。软删除可以通过添加删除标识字段实现，而不是真正从数据库中移除记录。

```php
// 删除模型实例
$user = model('common.user')->find(1);
$user->del();

// 使用 db 助手函数条件删除
db('user')->where('status', 0)->delete();
```

---

## 视图开发

### 视图基础

视图负责将数据渲染成用户可见的页面内容。视图文件通常放置在 app/{应用名}/view/ 目录下，与控制器目录结构对应。控制器通过 view() 函数将数据传递给视图，视图文件使用模板语法解析变量并生成最终的 HTML 代码。良好的视图设计应该关注用户体验和页面性能，避免在视图中编写复杂的业务逻辑。

#### 创建视图

视图文件使用 .html 作为文件扩展名，放在对应的控制器目录下。例如，User 控制器的 index 方法对应 view/user/index.html，add 方法对应 view/user/add.html。视图中可以使用框架提供的模板语法，包括变量输出、条件判断、循环遍历、模板包含等功能。

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$title} - 用户管理</title>
    {include file="public/_head" /}
</head>
<body>
    {include file="public/_header" /}
    
    <div class="container-fluid">
        <div class="row">
            {include file="public/sidebar" /}
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h1>{$data.title}</h1>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>昵称</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $list as $item}
                            <tr>
                                <td>{$item.id}</td>
                                <td>{$item.username}</td>
                                <td>{$item.nickname}</td>
                                <td>{if $item.status == 1:}正常{else:}停用{/if}</td>
                                <td>
                                    <a href="{:url('edit', ['id' => $item.id])}">编辑</a>
                                    <a href="javascript:;" onclick="deleteConfirm({$item.id})">删除</a>
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                {$page}
            </main>
        </div>
    </div>
    
    {include file="public/footer" /}
</body>
</html>
```

### 模板布局

框架支持模板布局功能，可以在多个页面中复用统一的页面结构。布局模板使用 {__CONTENT__} 占位符表示子页面内容的位置。子页面通过 {layout} 标签声明要使用的布局模板，实现页面的统一管理和维护。

```html
<!-- 布局模板 layout.html -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$title|default='后台管理系统'}</title>
    {include file="public/_head" /}
</head>
<body>
    {include file="public/sidebar" /}
    <main class="main-content">
        {__CONTENT__}
    </main>
    {include file="public/footer" /}
</body>
</html>

<!-- 子页面 -->
{layout name="layout" /}
<div class="page-content">
    <h2>用户列表</h2>
    {$list}
</div>
```

### 公共模板

公共模板用于存放页面头部、底部、侧边栏等公共部分。通过 include 标签可以将公共模板包含到页面中。公共模板中的变量会自动继承当前页面的变量，也可以传递额外的变量参数。这种方式可以避免代码重复，提高模板的可维护性。

---

## 中间件开发

### 中间件基础

中间件是请求处理流程中的拦截器，可以在请求到达控制器之前或响应返回给客户端之前进行预处理。中间件常用于身份认证、权限验证、CSRF 防护、日志记录等场景。框架支持全局中间件和应用级中间件，全局中间件在所有请求之前执行，应用级中间件在特定控制器或方法执行。

#### 创建中间件

中间件文件放置在 middleware/ 目录下，类名应与文件名对应。中间件类需要实现 handle 方法，该方法接收请求和下一个中间件或控制器的回调函数。handle 方法必须返回响应对象，可以在其中修改请求数据或拦截非法请求。

```php
<?php
declare(strict_types=1);

namespace middleware\controller;

use Closure;

class Auth
{
    public function run(Closure $next): void
    {
        if (!session('?user')) {
            if (IS_AJAX) {
                halt('', 401);
            }
            header('Location:' . url('user/login'));
            exit();
        }
        $next();
    }
}
```

### 注册中间件

中间件需要在配置文件中注册才能生效。全局中间件在 middleware.php 配置文件中定义，会在所有请求之前执行。控制器级中间件在控制器的 $middleware 属性中定义，只对当前控制器生效。路由级中间件在路由配置中定义，可以精细控制中间件的适用范围。

```php
<?php
// config/middleware.php
return [
    // 控制器中间件
    'controller' => [
        'auth' => [
            \middleware\controller\Auth::class, // 前台登录验证
        ],
        'cp_auth' => [
            \middleware\controller\CpAuth::class, // 后台验证
        ],
    ],
    // 全局中间件
    'common' => [
        \middleware\Boot::class, // 框架启动
    ],
    // 框架中间件
    'framework' => [
        'controller_start' => [], // 控制器开始
        'database_query' => [],   // 查询sql
        'database_execute' => [], // 执行sql
    ],
];
```

控制器中使用中间件，只需在 Cp 基类中配置 `$middleware` 属性：

```php
class Cp
{
    protected string $middleware = 'cp_auth'; // 后台验证
}
```

---

## CLI命令开发

### 命令行工具基础

框架提供命令行工具 xphpcli，用于执行定时任务、数据处理、代码生成等操作。命令行工具通过终端执行，不依赖 Web 服务器。命令采用 命名空间\类名:方法名 的格式调用，可以指定应用名称前缀来定位特定应用的命令。

#### 创建命令

命令类放置在 app/{应用名}/command/ 目录下，目录结构与控制器类似。命令类需要继承框架的 Command 基类，并实现 cli() 抽象方法作为命令执行的入口。

```php
<?php
declare(strict_types=1);

namespace app\admin\command;

use xphp\cli\Command;

class Test extends Command
{
    public function cli(): bool
    {
        $this->success('Test command executed!');
        return true;
    }
}
```

#### 执行命令

使用 php xphpcli 命令名称 来执行命令。命令名称格式为 `make:方法名`，如 `make:model`、`make:ctrl` 等。命令支持参数和选项的传递，参数在命令名称后按顺序传递，`-f` 选项表示强制覆盖已存在的文件。

```bash
# 查看所有可用命令
php xphpcli

# 生成模型
php xphpcli make:model admin@user

# 生成控制器
php xphpcli make:ctrl admin@user _def -f

# 生成视图
php xphpcli make:view admin@user index

# 清除缓存
php xphpcli clear
```

---

## 扩展开发

### 扩展基础

扩展用于封装可复用的功能模块，如验证码、文件上传、图片处理、邮件发送等。扩展文件放置在 extend/ 目录下，采用命名空间的组织方式。扩展类应该是自包含的，不依赖框架的具体实现，以便在其他项目中复用。

#### 创建扩展

扩展类应该遵循单一职责原则，每个扩展类只完成一个特定的功能。类名应清晰表达其功能，如 Captcha 用于验证码、Upload 用于文件上传。扩展类可以通过构造函数接收配置参数，也可以使用静态方法提供便捷调用。

```php
<?php
declare(strict_types=1);

namespace extend\captcha;

class Captcha
{
    private $config;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'length' => 4,
            'width' => 120,
            'height' => 40,
        ], $config);
    }
    
    public function generate(): array
    {
        $code = $this->createCode();
        $image = $this->createImage($code);
        return [
            'code' => $code,
            'image' => $image,
        ];
    }
    
    public function check(string $code, string $id = ''): bool
    {
        $key = 'captcha_' . $id;
        $sessionCode = session($key);
        session($key, null);
        return strtolower($code) === strtolower($sessionCode);
    }
}
```

---

## 代码生成器

### 生成器基础

框架内置代码生成器，可以根据数据表结构一键生成控制器、模型、视图的完整代码。代码生成器通过命令行工具执行，大大提高了开发效率。生成的代码遵循框架的规范，可以直接使用，也可以根据需要进行定制修改。

#### 生成模型

使用 make:model 命令可以生成模型文件。命令参数依次为应用名、模型名、主键名、模板类型。生成的模型文件包含基本的数据库操作方法，可以在此基础上添加业务逻辑。

```bash
# 生成基础模型
php xphpcli make:model admin@user

# 生成带配置的模型
php xphpcli make:model admin@user id _def

# 强制覆盖已存在的文件
php xphpcli make:model admin@user id _def -f
```

#### 生成控制器

使用 make:ctrl 命令可以生成控制器文件。生成的控制器包含增删改查的标准操作方法，以及对应的视图调用代码。

```bash
# 生成基础控制器
php xphpcli make:ctrl admin@user

# 生成带模板的控制器
php xphpcli make:ctrl admin@user _def

# 强制覆盖
php xphpcli make:ctrl admin@user _def -f
```

#### 生成视图

使用 make:view 命令可以生成视图文件。生成的视图包含表单页面、列表页面、编辑页面等标准模板。

```bash
# 生成视图
php xphpcli make:view admin@user index

# 生成增删改查视图
php xphpcli make:view admin@user index add edit

# 指定模板
php xphpcli make:view admin@user index index -f
```

#### 生成数据表

使用 make:table 命令可以生成数据表的 SQL 语句。命令参数包括表名、字段定义等信息。

```bash
# 生成用户表
php xphpcli make:table test_user id username varchar20 nickname varchar20

# 指定更多字段
php xphpcli make:table test_user id name varchar50 email varchar100 mobile char11 status tinyint1 create_time int10
```

---

## 调试指南

### 开启调试模式

在应用配置文件中设置 debug 选项为 true 可以开启调试模式。调试模式下，框架会显示详细的错误信息，包括文件路径、行号、错误原因等。trace 选项可以开启调试工具栏，显示请求信息、数据库查询、运行时间等调试数据。

```php
<?php
// config/app.php
return [
    'debug' => true,
    'trace' => true,
    // 其他配置...
];
```

### 日志查看

框架内置日志功能，记录程序运行过程中的关键信息。日志文件存放在 runtime/ 目录下的对应应用目录中。日志按日期分割，便于查找特定时间段的运行记录。可以通过配置开启不同级别的日志记录，包括 debug、info、warning、error 等。

```php
// 记录日志
\xphp\core\Log::info('用户登录成功', ['user_id' => $userId]);
\xphp\core\Log::error('数据库连接失败', ['error' => $e->getMessage()]);
```

### 常见问题排查

数据库连接失败时，应检查 config/database.php 或 .env 文件中的数据库配置是否正确，包括主机地址、端口号、数据库名、用户名、密码等。页面404错误通常是路由配置问题或控制器方法不存在，应检查 URL 与控制器、方法的对应关系。模板变量未输出时，应检查变量名称是否正确，以及变量是否已通过 view() 函数传递给视图。

清理缓存可以解决很多奇怪的错误，删除 runtime/ 目录下对应应用的所有文件即可。缓存包括编译后的模板文件、配置文件缓存、数据缓存等。
