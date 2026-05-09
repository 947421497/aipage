# 三级菜单改造方案

## 一、项目概述

### 1.1 项目背景

本项目是基于一鱼 PHP 框架（XPHP Framework）开发的后台管理系统，采用 MVC 架构模式，前端使用 Bootstrap 5 和 jQuery 构建响应式管理界面。项目当前使用 Light Year Admin Template v5 作为前端框架模板，该模板原生支持多级菜单嵌套和手风琴折叠效果。

当前后台菜单系统采用扁平化结构，所有菜单项均为一级菜单，缺乏层级关系和折叠交互功能，无法满足复杂业务系统的导航需求。本次改造旨在利用框架原生的多级菜单功能，将现有的扁平菜单升级为支持三级嵌套的树形菜单系统。

### 1.2 技术栈分析

项目采用的技术栈包括：后端使用 PHP 8.1 及以上版本，框架为自主研发的一鱼 PHP 框架；数据库采用 MySQL 5.6 及以上版本；前端框架为 Bootstrap 5，配合 jQuery 实现交互效果；后台模板使用 Light Year Admin Template v5，该模板基于 Bootstrap 5 构建，原生支持多级侧边栏菜单和手风琴交互。

### 1.3 改造目标

本次改造的核心目标是将现有的扁平菜单系统升级为支持三级嵌套的树形菜单系统。具体目标包括：利用框架原生的侧边栏菜单结构实现三级菜单展示；直接使用框架内置的手风琴交互效果，无需编写额外 JavaScript；调整菜单管理界面支持三级菜单的增删改查操作；确保改造过程不影响现有系统功能的正常运行。

## 二、现状分析

### 2.1 当前菜单表结构

当前菜单表 xphp_menu 采用单表设计，各字段定义如下：id 字段为主键自增，使用 smallint 无符号整数类型；title 字段存储菜单显示标题，最大长度 50 个字符；href 字段存储跳转链接地址，格式为控制器/方法；sign 字段为菜单唯一标识符，用于高亮当前菜单；icon 字段存储菜单图标类名；is_sys 字段标识是否为系统菜单，1 表示禁删；sort 字段控制菜单排序权重；update_time 字段存储更新时间戳；status 字段标识菜单状态，1 为启用。

现有表结构缺乏 pid 字段，无法表达菜单项之间的父子关系，所有菜单记录在同一平面上通过 sort 字段控制显示顺序。

### 2.2 当前前端实现

当前侧边栏模板位于 app/admin/view/public/sidebar.html，通过 Widget 组件获取全部启用状态的菜单，以简单的无序列表形式渲染。每个菜单项都是独立的链接，点击即跳转。核心渲染逻辑使用 foreach 循环遍历菜单数组，每个菜单项包含图标和标题标签，点击 href 属性指定的 URL 实现页面跳转。

当前实现存在的主要问题包括：没有菜单分组概念，无法表达功能模块的归属关系；没有折叠展开功能，所有菜单始终平铺展示；没有视觉层级区分。

### 2.3 框架内置手风琴实现分析

经代码审查发现，框架已在 main.min.js 中内置了手风琴交互功能。框架手风琴的核心实现监听 `.nav-item-has-subnav > a` 的点击事件，当用户点击带有子菜单的菜单项时触发处理函数。框架通过 `$navHasSubnav.siblings().find('.nav-subnav:visible').slideUp(500).parent().removeClass('open')` 实现手风琴效果，自动关闭同级其他已展开的子菜单。框架通过 `$subnav.stop().slideToggle(300, ...)` 切换当前菜单的展开状态。

使用框架内置手风琴功能需要遵循以下 HTML 结构规范：带有子菜单的 `<li>` 标签需要添加 `.nav-item-has-subnav` 类名；子菜单容器使用 `<ul>` 标签并添加 `.nav-subnav` 类名；点击触发元素必须是 `<a>` 标签，位于 `.nav-item-has-subnav` 内的第一层。

## 三、目标分析

### 3.1 功能性需求

#### 3.1.1 三级菜单层级定义

一级菜单作为功能模块的入口，对应系统中相对独立的大功能区域。一级菜单不需要填写跳转链接，但必须有菜单标题、唯一标识和图标。一级菜单的主要作用是分组归纳下级功能，其本身不直接对应具体页面，点击时应展开其下的二级菜单列表。

二级菜单作为功能子模块的入口，对应一级菜单下的具体功能分类。二级菜单需要有跳转链接，用户点击后可进入对应功能页面。如果二级菜单下还有三级菜单，则点击时应展开三级菜单列表。

三级菜单作为具体功能操作的入口，是菜单树的最底层，对应最终的操作页面。三级菜单需要有跳转链接，点击后跳转到具体功能页面。

#### 3.1.2 手风琴交互效果

框架原生支持手风琴折叠效果，无需编写额外 JavaScript 代码。当用户点击某个带有子菜单的菜单项时，如果此时有其他同级菜单的子菜单处于展开状态，则自动折叠这些已展开的子菜单。

#### 3.1.3 菜单数据管理

需要提供菜单的增删改查功能，支持选择父级菜单。添加新菜单时应能选择该菜单的父级，顶级菜单则不选择父级。系统应限制最多支持三级嵌套。

## 四、技术方案设计

### 4.1 数据库改造

#### 4.1.1 表结构变更

在现有 xphp_menu 表基础上新增 pid 字段（父级 ID），用于建立菜单项之间的父子关系。该字段为无符号小整数类型，默认为 0 表示顶级菜单。

修改后的建表语句如下：

```sql
CREATE TABLE `xphp_menu` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单ID',
  `pid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID，0表示顶级菜单',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '菜单标题',
  `href` varchar(100) NOT NULL DEFAULT '' COMMENT '链接地址，空表示父级菜单',
  `sign` varchar(20) NOT NULL DEFAULT '' COMMENT '菜单标识',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单图标',
  `is_sys` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '系统菜单(0可删/1禁删)',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序权重',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态(0正常/1禁用)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='菜单表';
```

#### 4.1.2 数据迁移策略

现有菜单数据的 pid 字段统一设置为 0，表示这些菜单都是顶级菜单。迁移过程中不对现有数据做任何修改，只新增字段并设置默认值。

迁移步骤如下：首先备份现有数据，然后使用 ALTER TABLE 语句添加 pid 字段，最后验证迁移结果。

### 4.2 后端逻辑改造

#### 4.2.1 菜单 Widget 组件改造

现有的菜单 Widget 组件位于 app/admin/widget/Menu.php，需要改造为支持树形结构构建。

改造后的完整代码如下：

```php
<?php
declare(strict_types=1);
namespace app\admin\widget;
use xphp\core\Widget;

class Menu extends Widget
{
    protected string $tag = 'menu';
    protected int $expire = 0;

    public function set($id = '', array $options = [])
    {
        $menus = db('menu')
            ->where('status=1')
            ->order('sort ASC, id ASC')
            ->select();

        return $this->buildTree($menus);
    }

    protected function buildTree(array $menus): array
    {
        $items = [];
        foreach ($menus as $menu) {
            $items[$menu['id']] = $menu;
            $items[$menu['id']]['children'] = [];
        }

        $tree = [];
        foreach ($items as $id => $item) {
            if ($item['pid'] == 0) {
                $tree[] = &$items[$id];
            } elseif (isset($items[$item['pid']])) {
                $items[$item['pid']]['children'][] = &$items[$id];
            } else {
                $tree[] = &$items[$id];
            }
        }

        return $tree;
    }
}
```

树形构建算法说明如下：首先将全部菜单数据按 ID 建立索引，形成关联数组；然后为每个菜单项初始化空的 children 数组；接着从 PID 为 0 的顶级菜单开始，将子菜单挂载到父级菜单的 children 属性下；对于孤立节点（父级不存在），将其降级为顶级菜单处理；最后返回构建好的树形结构。

#### 4.2.2 菜单模型验证规则调整

菜单模型位于 app/admin/model/Menu.php，需要增加层级验证逻辑。

改造后的完整代码如下：

```php
<?php
declare(strict_types=1);
namespace app\admin\model;
use xphp\core\Model;

class Menu extends Model
{
    protected string $table = 'menu';
    protected string $pk = 'id';

    protected array $validate = [
        ['title', 'chs_alpha_num|length:1,50', '标题格式错误|标题长度需在1-50字符', FV_MUST, AC_BOTH],
        ['sign', 'string|length:1,20|unique', '标识格式错误|标识长度需在1-20字符|标识已存在', FV_MUST, AC_BOTH],
        ['sort', 'number', '排序值为正数', FV_MUST, AC_BOTH],
        ['pid', 'number', '父级ID格式错误', FV_MUST, AC_BOTH],
    ];

    protected array $auto = [
        ['status', '1', 'string', FV_MUST, AC_INSERT],
    ];

    protected function _before_insert(array $data): array
    {
        $this->validateHref($data);
        $this->validateDepth($data);
        return $data;
    }

    protected function _before_update(array $data): array
    {
        $this->validateHref($data);
        $this->validateDepth($data);
        $this->validateCircular($data);
        return $data;
    }

    protected function validateHref(array $data): void
    {
        if (!empty($data['pid']) && empty($data['href'])) {
            halt('子级菜单必须填写链接地址');
        }
    }

    protected function validateDepth(array $data): void
    {
        if (!empty($data['pid'])) {
            $parent = $this->find($data['pid']);
            if ($parent) {
                $depth = $this->getDepth($parent);
                if ($depth >= 3) {
                    halt('最多只能创建三级菜单');
                }
            }
        }
    }

    protected function getDepth(array $menu): int
    {
        $depth = 1;
        $pid = $menu['pid'] ?? 0;
        while ($pid > 0 && $depth < 100) {
            $parent = $this->find($pid);
            if ($parent && isset($parent['pid'])) {
                $pid = $parent['pid'];
                $depth++;
            } else {
                break;
            }
        }
        return $depth;
    }

    protected function validateCircular(array $data): void
    {
        if (!isset($data['id']) || !isset($data['pid'])) {
            return;
        }

        $checkId = $data['pid'];
        $targetId = $data['id'];
        $visited = [$targetId];

        while ($checkId > 0) {
            if (in_array($checkId, $visited)) {
                halt('检测到循环引用，禁止操作');
            }
            $visited[] = $checkId;
            $parent = $this->find($checkId);
            if ($parent) {
                $checkId = $parent['pid'] ?? 0;
            } else {
                break;
            }
        }
    }

    protected function _before_delete(array $data): void
    {
        $hasChildren = db('menu')->where('pid', $data['id'])->count();
        if ($hasChildren > 0) {
            halt('存在子菜单，请先删除子菜单');
        }
        $this->db = $this->db->where('status=0 AND is_sys=0');
    }
}
```

验证规则说明如下：validateHref 方法检查子级菜单必须填写链接地址，顶级菜单的链接地址可留空；validateDepth 方法通过 getDepth 获取父级的深度，如果父级深度已达三级（depth >= 3），则禁止创建子菜单；validateCircular 方法检测循环引用，通过 visited 数组记录已访问的节点，防止死循环和循环引用问题；_before_delete 方法在删除前检查是否存在子菜单，如果存在则禁止删除。

#### 4.2.3 菜单控制器增强

菜单控制器位于 app/admin/controller/Menu.php，需要增加父级菜单的选择逻辑。

改造后的完整代码如下：

```php
<?php
declare(strict_types=1);
namespace app\admin\controller;

class Menu extends Cp
{
    protected string $model = 'menu';
    protected string $order = 'pid ASC, sort ASC, id ASC';

    protected function _where(): array
    {
        return [];
    }

    public function add()
    {
        $this->assignParentMenus();
        return parent::add();
    }

    public function edit(int $id)
    {
        $this->assignParentMenus($id);
        return parent::edit($id);
    }

    protected function assignParentMenus(int $excludeId = 0): void
    {
        $menus = model('menu')
            ->where('status=1')
            ->where('id', '<>', $excludeId)
            ->order('pid ASC, sort ASC, id ASC')
            ->select();

        if ($excludeId > 0) {
            $excludeIds = $this->getAllChildIds($excludeId);
            $menus = array_filter($menus, function ($menu) use ($excludeIds) {
                return !in_array($menu['id'], $excludeIds);
            });
        }

        $tree = $this->buildSelectTree(array_values($menus));
        view()->with('parentMenus', $tree);
    }

    protected function getAllChildIds(int $pid): array
    {
        $ids = [];
        $children = model('menu')->where('pid', $pid)->column('id');
        foreach ($children as $childId) {
            $ids[] = $childId;
            $ids = array_merge($ids, $this->getAllChildIds($childId));
        }
        return $ids;
    }

    protected function buildSelectTree(array $menus, int $pid = 0, int $level = 0): array
    {
        $result = [];
        $prefix = str_repeat('　├─ ', $level);

        foreach ($menus as $menu) {
            if ($menu['pid'] == $pid) {
                $item = [
                    'id' => $menu['id'],
                    'title' => $prefix . $menu['title'],
                    'pid' => $menu['pid'],
                    'level' => $level
                ];
                $result[] = $item;
                $children = $this->buildSelectTree($menus, $menu['id'], $level + 1);
                $result = array_merge($result, $children);
            }
        }

        return $result;
    }
}
```

控制器逻辑说明如下：assignParentMenus 方法为表单提供父级菜单选择数据，参数 excludeId 用于在编辑时排除自身及其子级；getAllChildIds 方法递归获取指定菜单的所有子级 ID，用于在编辑时过滤不可选的父级选项；buildSelectTree 方法构建用于下拉选择的扁平树结构，使用缩进前缀区分层级。

### 4.3 前端界面改造

#### 4.3.1 框架内置手风琴使用说明

框架已在 main.min.js 中内置了完整的手风琴交互功能，只需按照框架要求的 HTML 结构编写菜单即可自动获得手风琴效果。框架要求的 HTML 结构规范包括：带有子菜单的 `<li>` 标签需要添加 `.nav-item-has-subnav` 类名；子菜单容器使用 `<ul>` 标签并添加 `.nav-subnav` 类名；点击触发元素必须是 `<a>` 标签，位于 `.nav-item-has-subnav` 内的第一层。

#### 4.3.2 侧边栏模板改造

侧边栏模板位于 app/admin/view/public/sidebar.html，需要按照框架规范重写。

改造后的完整代码如下：

```html
<aside class="lyear-layout-sidebar">
  <div id="logo" class="sidebar-header">
    <a href="{:url('index/index')}">
      <img src="__STATIC__/images/logo-sidebar.png" title="{:site('site_name','后台管理')}" alt="{:site('site_name','后台管理')}" />
    </a>
  </div>
  <div class="lyear-layout-sidebar-info lyear-scroll">
    <nav class="sidebar-main">
      <ul class="nav-drawer">
        <li class="nav-item{:nav_active('index', ' active')}">
          <a href="{:url('index/index')}">
            <i class="mdi mdi-home-city-outline"></i>
            <span>管理中心</span>
          </a>
        </li>
        {php $menu = widget('menu')->get()}
        {foreach $menu as $nav}
        <li class="nav-item nav-item-has-subnav{:nav_active($nav['sign'], ' active open')}">
          <a href="javascript:;">
            <i class="{$nav.icon}"></i>
            <span>{$nav.title}</span>
          </a>
          <ul class="nav-subnav">
            {foreach $nav.children as $child}
            {if !empty($child.children)}
            <li class="nav-item nav-item-has-subnav{:nav_active($child['sign'], ' active open')}">
              <a href="javascript:;">
                <span>{$child.title}</span>
              </a>
              <ul class="nav-subnav">
                {foreach $child.children as $grandchild}
                <li class="nav-item{:nav_active($grandchild['sign'], ' active')}">
                  <a href="{:url($grandchild.href)}">
                    <span>{$grandchild.title}</span>
                  </a>
                </li>
                {/foreach}
              </ul>
            </li>
            {else}
            <li class="nav-item{:nav_active($child['sign'], ' active')}">
              <a href="{:url($child.href)}">
                <span>{$child.title}</span>
              </a>
            </li>
            {/if}
            {/foreach}
          </ul>
        </li>
        {/foreach}
      </ul>
    </nav>
    <div class="sidebar-footer">
      <p class="copyright">
        <span>Copyright &copy; {:date('Y')}. </span>
        <a target="_blank" href="{:site('site_link', 'https://xphp.net')}">{:site('site_name','一鱼PHP框架')}</a>
      </p>
    </div>
  </div>
</aside>
```

模板结构说明如下：第一层结构为管理中心入口，使用 `.nav-item` 类，不带子菜单，点击直接跳转；第二层结构为一级菜单，使用 `.nav-item.nav-item-has-subnav` 类，包含图标和标题，点击时不跳转页面而是触发框架手风琴展开二级菜单；第三层结构为二级菜单，根据是否有三级子菜单决定渲染方式，有三级时使用 `.nav-item-has-subnav` 触发展开，没有三级时直接渲染为跳转链接；第四层结构为三级菜单，使用 `.nav-item` 类，没有子菜单容器，点击时跳转到对应页面。

### 4.4 菜单管理界面改造

#### 4.4.1 菜单列表页面

菜单列表页面位于 app/admin/view/menu/index.html，需要调整表格列以展示层级关系。列表排序应调整为按 pid ASC、sort ASC、id ASC 排序，确保同一父级下的子菜单相邻显示。

#### 4.4.2 添加菜单页面

添加页面位于 app/admin/view/menu/add.html，需要新增父级菜单选择字段。

改造后的完整代码如下：

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
              <header class="card-header"><div class="card-title">添加菜单</div></header>
              <div class="card-body">
                <div class="alert alert-light">
                  可用图标：<a href="https://pictogrammers.com/library/mdi/" target="_blank">https://pictogrammers.com/library/mdi/</a>
                </div>
                <form class="site-form submit-ajax" action="{:url('add')}" method="post">
                  <div class="mb-3">
                    <label class="form-label" for="pid">父级菜单</label>
                    <select class="form-select" id="pid" name="pid">
                      <option value="0">顶级菜单</option>
                      {foreach $parentMenus as $pm}
                      <option value="{$pm.id}">{$pm.title}</option>
                      {/foreach}
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="title">*标题</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="请输入标题" value="" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="href">链接</label>
                    <input type="text" class="form-control" id="href" name="href" placeholder="控制器/方法，父级菜单可不填" value="" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="sign">*标识</label>
                    <input type="text" class="form-control" id="sign" name="sign" placeholder="请输入标识" value="" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="icon">*图标</label>
                    <input type="text" class="form-control" id="icon" name="icon" placeholder="图标样式" value="mdi mdi-tag" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="sort">*排序</label>
                    <input type="text" class="form-control" id="sort" name="sort" placeholder="请输入排序值" value="100" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label">*禁删</label>
                    {:form_radio('is_sys', ['否', '是'], 0)}
                  </div>
                  <div class="mb-3">
                    <button type="submit" class="btn btn-primary">添加</button>
                    <button type="button" class="btn btn-default" onclick="javascript:history.back(-1);return false;">返回</button>
                  </div>
                </form>
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

#### 4.4.3 编辑菜单页面

编辑页面位于 app/admin/view/menu/edit.html，需要增加父级菜单选择字段。

改造后的完整代码如下：

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
              <header class="card-header"><div class="card-title">修改菜单</div></header>
              <div class="card-body">
                <div class="alert alert-light">
                  可用图标：<a href="https://pictogrammers.com/library/mdi/" target="_blank">https://pictogrammers.com/library/mdi/</a>
                </div>
                <form class="site-form submit-ajax" action="{:url('edit')}" method="post">
                  <input type="hidden" name="id" value="{$vo.id}" />
                  <div class="mb-3">
                    <label class="form-label" for="pid">父级菜单</label>
                    <select class="form-select" id="pid" name="pid">
                      <option value="0" {if $vo.pid==0}selected{/if}>顶级菜单</option>
                      {foreach $parentMenus as $pm}
                      {if $pm.id!=$vo.id}
                      <option value="{$pm.id}" {if $vo.pid==$pm.id}selected{/if}>{$pm.title}</option>
                      {/if}
                      {/foreach}
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="title">*标题</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="请输入标题" value="{$vo.title}" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="href">链接</label>
                    <input type="text" class="form-control" id="href" name="href" placeholder="控制器/方法，父级菜单可不填" value="{$vo.href}" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="sign">*标识</label>
                    <input type="text" class="form-control" id="sign" name="sign" placeholder="请输入标识" value="{$vo.sign}" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="icon">*图标</label>
                    <input type="text" class="form-control" id="icon" name="icon" placeholder="图标样式" value="{$vo.icon}" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="sort">*排序</label>
                    <input type="text" class="form-control" id="sort" name="sort" placeholder="请输入排序值" value="{$vo.sort}" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label">*禁删</label>
                    {:form_radio('is_sys', ['否', '是'], $vo['is_sys'])}
                  </div>
                  <div class="mb-3">
                    <button type="submit" class="btn btn-primary">修改</button>
                    <button type="button" class="btn btn-default" onclick="javascript:history.back(-1);return false;">返回</button>
                  </div>
                </form>
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

编辑页面特殊处理说明如下：编辑页面在循环父级选项时排除了自身（$pm.id != $vo.id），防止将自己设为父级；在删除菜单前会检查是否存在子菜单，如果存在则禁止删除。

## 五、文件变更清单

### 5.1 数据库文件

需要修改的数据库相关文件位于 backup/bak_all_initialize/ 目录下。2_create_table.sql 文件需要添加 pid 字段的表结构定义，作为安装初始化使用。

### 5.2 PHP 后端文件

需要修改的 PHP 后端文件包括：app/admin/model/Menu.php 文件需要调整验证规则以支持一级菜单 href 可为空，增加层级深度验证和循环引用验证；app/admin/widget/Menu.php 文件需要重写菜单数据获取逻辑，增加树形结构构建功能；app/admin/controller/Menu.php 文件需要增加获取父级菜单列表的方法，为表单提供父级选择数据。

### 5.3 视图模板文件

需要修改的视图模板文件包括：app/admin/view/public/sidebar.html 文件需要按照框架规范重写，实现三级嵌套菜单结构；app/admin/view/menu/add.html 文件需要增加父级菜单选择下拉框；app/admin/view/menu/edit.html 文件需要增加父级菜单选择下拉框并设置当前值为默认选项。

### 5.4 静态资源文件

本次改造无需修改任何静态资源文件，框架已内置手风琴交互功能，只需按照规范编写 HTML 结构即可。

## 六、测试验证计划

### 6.1 功能测试用例

功能测试应覆盖以下场景：添加顶级菜单并验证其正确显示在侧边栏顶部；添加二级菜单并验证其正确挂载到对应的一级菜单下；添加三级菜单并验证其正确挂载到对应的二级菜单下；验证最多只能添加三级菜单，无法创建四级菜单；验证手风琴效果，点击一级菜单时其他已展开的菜单正确折叠；验证当前菜单高亮功能，根据当前访问的 URL 正确显示 active 状态；验证循环引用检测，尝试将菜单的父级设为其子级时应报错；验证删除保护，尝试删除有子菜单的菜单时应报错。

### 6.2 数据完整性测试

数据完整性测试应覆盖以下场景：编辑菜单时验证自身和子级不会出现在父级选项中；删除菜单时验证其子菜单的处理方式；禁用菜单时验证其子菜单的显示状态。

### 6.3 兼容性测试

兼容性测试应覆盖以下场景：现有数据库迁移后原有菜单是否正常显示；新增菜单功能与现有功能是否冲突；不同浏览器下菜单样式和交互是否正常。

## 七、实施注意事项

### 7.1 关于 JavaScript

本次改造不需要编写任何 JavaScript 代码。框架已在 main.min.js 中内置了完整的手风琴交互功能，只需按照框架要求的 HTML 结构编写菜单即可。框架自动处理点击事件、动画效果、手风琴逻辑和自动滚动。

### 7.2 HTML 结构规范

必须严格遵循框架的 HTML 结构规范，否则手风琴功能无法正常工作。关键点包括：带有子菜单的 `<li>` 必须添加 `.nav-item-has-subnav` 类名；子菜单容器必须使用 `<ul>` 标签并添加 `.nav-subnav` 类名；点击触发元素必须是 `<a>` 标签。

### 7.3 数据库修改范围

本次改造的数据库修改仅涉及备份文件，不直接修改生产数据库。用户需通过重新安装系统的方式完成数据库初始化。如果系统已上线运行，需要先备份现有数据库，然后导出菜单数据，修改备份 SQL 文件，执行修改后的 SQL 完成数据库升级。

### 7.4 向后兼容性

菜单表新增的 pid 字段默认值为 0，这意味着现有菜单记录在改造后仍被视为顶级菜单，不需要额外的数据迁移工作。

### 7.5 安全性说明

本次改造包含以下安全措施：层级深度验证确保最多只能创建三级菜单；循环引用检测防止形成死循环；删除保护确保有子菜单的菜单不能被直接删除；字段长度限制防止恶意输入。

### 7.6 性能说明

树形结构在服务端构建后一次性返回，减少前端处理量。对于菜单数量较少的场景（小于 100 个），性能影响可忽略不计。
