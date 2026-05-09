# 三级菜单改造方案

## 一、项目概述

### 1.1 项目背景

本项目是基于一鱼 PHP 框架（XPHP Framework）开发的后台管理系统，采用 MVC 架构模式，前端使用 Bootstrap 5 和 jQuery 构建响应式管理界面。项目当前使用 Light Year Admin Template v5 作为前端框架模板，该模板原生支持多级菜单嵌套和手风琴折叠效果。

当前后台菜单系统采用扁平化结构，所有菜单项均为一级菜单，缺乏层级关系和折叠交互功能，无法满足复杂业务系统的导航需求。本次改造旨在利用 Light Year Admin Template v5 原生的多级菜单功能，将现有的扁平菜单升级为支持三级嵌套的树形菜单系统。

### 1.2 技术栈分析

项目采用的技术栈包括：后端使用 PHP 8.1 及以上版本，框架为自主研发的一鱼 PHP 框架；数据库采用 MySQL 5.6 及以上版本；前端框架为 Bootstrap 5，配合 jQuery 实现交互效果；后台模板使用 Light Year Admin Template v5，该模板基于 Bootstrap 5 构建，原生支持多级侧边栏菜单。

### 1.3 改造目标

本次改造的核心目标是将现有的扁平菜单系统升级为支持三级嵌套的树形菜单系统。具体目标包括：利用 Light Year Admin Template v5 原生的侧边栏菜单结构实现三级菜单展示；保留并适配模板原有的手风琴折叠交互效果；调整菜单管理界面支持三级菜单的增删改查操作；确保改造过程不影响现有系统功能的正常运行。

## 二、现状分析

### 2.1 当前菜单表结构

当前菜单表 xphp_menu 采用单表设计，各字段定义如下：id 字段为主键自增，使用 smallint 无符号整数类型；title 字段存储菜单显示标题，最大长度 50 个字符；href 字段存储跳转链接地址，格式为控制器/方法；sign 字段为菜单唯一标识符，用于高亮当前菜单；icon 字段存储菜单图标类名；is_sys 字段标识是否为系统菜单，1 表示禁删；sort 字段控制菜单排序权重；update_time 字段存储更新时间戳；status 字段标识菜单状态，1 为启用。

现有表结构缺乏 pid 字段，无法表达菜单项之间的父子关系，所有菜单记录在同一平面上通过 sort 字段控制显示顺序。这种设计在菜单数量较少时足够使用，但当系统功能模块增多时局限性明显。

### 2.2 当前前端实现

当前侧边栏模板位于 app/admin/view/public/sidebar.html，通过 Widget 组件获取全部启用状态的菜单，以简单的无序列表形式渲染。每个菜单项都是独立的链接，点击即跳转。核心渲染逻辑使用 foreach 循环遍历菜单数组，每个菜单项包含图标和标题标签，点击 href 属性指定的 URL 实现页面跳转。

当前实现存在的主要问题包括：没有菜单分组概念，无法表达功能模块的归属关系；没有折叠展开功能，所有菜单始终平铺展示，占用大量屏幕空间；没有视觉层级区分，一级菜单和功能入口混在一起，用户难以快速定位目标功能。

### 2.3 当前后端逻辑

菜单 Widget 组件位于 app/admin/widget/Menu.php，负责从数据库获取菜单数据并返回给视图层。当前实现仅查询 status=1 的启用菜单记录，按 sort ASC 和 id ASC 排序后直接返回一维数组，不进行任何树形结构构建处理。

菜单模型位于 app/admin/model/Menu.php，定义了数据验证规则和自动处理逻辑。验证规则要求 title 为唯一的中文字符串、href 必须符合控制器/方法格式、sign 为唯一字符串、sort 为正数。自动处理逻辑在插入时将 status 字段默认值设为 1。

菜单控制器位于 app/admin/controller/Menu.php，继承自 Cp 基类，使用 $model 属性指定模型名称为 menu。基本的增删改查功能由 Cp 基类提供，控制器层仅需指定模型名称即可实现标准的 CRUD 操作。

### 2.4 权限控制现状

权限验证中间件位于 middleware/controller/CpAuth.php，仅检查用户是否登录以及用户等级是否达到 3 级。当前实现中，如果 session 中不存在 user 数据则跳转到登录页面；如果用户等级小于 3 则返回 403 无权限响应。权限控制未与菜单进行关联，无法实现基于角色的菜单可见性控制。

## 三、目标分析

### 3.1 功能性需求

#### 3.1.1 三级菜单层级定义

一级菜单作为功能模块的入口，对应系统中相对独立的大功能区域。一级菜单不需要填写跳转链接，但必须有菜单标题、唯一标识和图标。一级菜单的主要作用是分组归纳下级功能，其本身不直接对应具体页面，点击时应展开其下的二级菜单列表。

二级菜单作为功能子模块的入口，对应一级菜单下的具体功能分类。二级菜单需要有跳转链接，用户点击后可进入对应功能页面。如果二级菜单下还有三级菜单，则点击时应展开三级菜单列表。

三级菜单作为具体功能操作的入口，是菜单树的最底层，对应最终的操作页面。三级菜单需要有跳转链接，点击后跳转到具体功能页面。

#### 3.1.2 手风琴交互效果

Light Year Admin Template v5 原生支持手风琴折叠效果。当用户点击某个一级菜单时，如果此时有其他一级菜单的二级菜单处于展开状态，则自动折叠这些已展开的二级菜单。用户应能清晰感知当前展开的是哪个功能模块，便于快速定位和切换。

#### 3.1.3 菜单数据管理

需要提供菜单的增删改查功能，支持选择父级菜单。添加新菜单时应能选择该菜单的父级，顶级菜单则不选择父级。系统应限制最多支持三级嵌套，即只能为二级菜单选择三级菜单作为子菜单，不能无限嵌套。菜单列表应展示层级关系，可通过缩进清晰展示上下级关系。

### 3.2 非功能性需求

#### 3.2.1 性能要求

菜单数据的读取和树形结构构建应在服务器端完成，避免在前端进行复杂的递归处理。菜单数据应支持按需获取，只查询当前用户有权限访问的菜单记录。树形构建算法应高效，对于百级菜单应能在毫秒级完成处理。

#### 3.2.2 兼容性要求

改造应保持与现有系统的向后兼容。现有的单级菜单数据应能平滑迁移到新的三级结构中。菜单管理界面的操作习惯应与现有后台保持一致，降低用户学习成本。

#### 3.2.3 可维护性要求

菜单层级关系应通过数据字段明确表达，便于后续扩展和维护。菜单渲染逻辑应模块化，便于根据需求调整样式和交互。代码应遵循框架的编码规范，保持与项目其他部分的一致性。

## 四、技术方案设计

### 4.1 数据库改造

#### 4.1.1 表结构变更

在现有 xphp_menu 表基础上新增 pid 字段（父级 ID），用于建立菜单项之间的父子关系。该字段为无符号小整数类型，默认为 0 表示顶级菜单。为支持更深的层级扩展和未来可能的四级菜单需求，字段类型选用 smallint unsigned 而非 tinyint unsigned。

具体字段变更如下：新增 pid 字段，类型为 smallint unsigned，默认值为 0，注释为“父级 ID，0 表示顶级菜单”；现有数据中 pid 字段默认值设为 0，保持原有菜单的一级菜单属性。

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
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`),
  KEY `idx_status` (`status`),
  KEY `idx_sign` (`sign`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='菜单表';
```

#### 4.1.2 索引设计

新增 pid 字段后应创建索引以优化基于父级的查询性能。新增的索引包括：idx_pid 索引用于加速查询某父级下的所有子菜单；保留原有的 idx_sign 唯一索引确保菜单标识唯一性；新增 idx_status 索引用于筛选正常状态的菜单记录。

#### 4.1.3 数据迁移策略

现有菜单数据的 pid 字段统一设置为 0，表示这些菜单都是顶级菜单。迁移过程中不对现有数据做任何修改，只新增字段并设置默认值。这样可以确保迁移过程安全可靠，即使出现问题也可以快速回滚。

### 4.2 后端逻辑改造

#### 4.2.1 菜单 Widget 组件改造

现有的菜单 Widget 组件位于 app/admin/widget/Menu.php，需要改造为支持树形结构构建。改造后的组件应从数据库获取全部启用状态的菜单记录，然后在 PHP 端完成树形结构的递归构建，最后将构建好的树形数据返回给视图层。

树形构建算法核心思路如下：首先将全部菜单数据按 ID 建立索引，形成关联数组；然后遍历所有菜单，将每个菜单的引用存入以其父级 ID 为键的临时数组中；最后从 PID 为 0 的顶级菜单开始，递归地将子菜单挂载到父级菜单的 children 属性下。

改造后的 Widget 代码应包含以下要点：设置缓存有效期为 0 表示不缓存，确保菜单变更能即时生效；查询条件为 status=1 表示只获取启用状态的菜单；查询结果按 sort ASC、id ASC 排序，确保同级菜单按设定的顺序显示；树形构建过程在服务端完成，减轻前端处理负担。

具体实现代码如下：

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
            } else {
                if (isset($items[$item['pid']])) {
                    $items[$item['pid']]['children'][] = &$items[$id];
                }
            }
        }

        return $tree;
    }
}
```

#### 4.2.2 菜单模型验证规则调整

菜单模型位于 app/admin/model/Menu.php，自动验证规则需要相应调整。对于 href 字段的验证规则，一级菜单（PID 为 0）的 href 可以为空，而二级和三级菜单的 href 必须符合控制器/方法的格式。建议将验证规则修改为条件验证。

修改后的模型代码如下：

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
        ['title', 'chs_alpha_num|unique', '标题格式错误|标题已存在', FV_MUST, AC_BOTH],
        ['sign', 'string|unique', '标识格式错误|标识已存在', FV_MUST, AC_BOTH],
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
        if (empty($data['pid']) && !empty($data['href'])) {
            halt('顶级菜单不需要填写链接地址');
        }
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
                if ($depth >= 2) {
                    halt('最多只能创建三级菜单');
                }
            }
        }
    }

    protected function getDepth(array $menu): int
    {
        $depth = 0;
        $pid = $menu['pid'] ?? 0;
        while ($pid > 0 && $depth < 10) {
            $parent = $this->find($pid);
            if ($parent) {
                $pid = $parent['pid'] ?? 0;
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

        while ($checkId > 0) {
            if ($checkId == $targetId) {
                halt('不能将菜单的父级设置为自己或自己的子级');
            }
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
        $this->db = $this->db->where('status=0 AND is_sys=0');
    }
}
```

#### 4.2.3 菜单控制器增强

菜单控制器位于 app/admin/controller/Menu.php，继承自 Cp 基类，基本的 CRUD 功能由基类提供。需要在菜单控制器中添加父级菜单的选择逻辑，为添加和编辑页面提供父级菜单列表数据。

修改后的控制器代码如下：

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
        $this->assignParentMenus();
        return parent::edit($id);
    }

    protected function assignParentMenus(): void
    {
        $menus = model('menu')
            ->where('status=1')
            ->order('pid ASC, sort ASC, id ASC')
            ->select();

        $tree = $this->buildSelectTree($menus);
        view()->with('parentMenus', $tree);
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

### 4.3 前端界面改造

#### 4.3.1 Light Year Admin Template v5 菜单结构分析

Light Year Admin Template v5 原生支持多级侧边栏菜单，其菜单结构采用嵌套的 ul-li 列表实现。典型的一级菜单包含可点击的链接和可选的子菜单容器，二级菜单同样包含链接和可能的子菜单容器，三级菜单为最终的功能入口，不包含子菜单容器。

模板的手风琴效果通过 JavaScript 控制实现。当点击带有子菜单的一级菜单时，JavaScript 会检查其他同级菜单的子菜单是否处于展开状态，如果处于展开状态则自动折叠它们，然后切换当前菜单的展开状态。

模板的样式区分通过 CSS 类名实现。has-submenu 类标识含有子菜单的菜单项；open 类表示菜单处于展开状态；nav-submenu 类用于标识子菜单容器；不同层级通过 padding-left 和字体大小等样式属性实现视觉区分。

#### 4.3.2 侧边栏模板改造

侧边栏模板位于 app/admin/view/public/sidebar.html，需要完全重写以适配 Light Year Admin Template v5 的原生菜单结构。改造后的模板应使用多层无序列表嵌套，外层 ul 表示菜单容器，内层 li 表示单个菜单项，li 内部的 ul.nav-submenu 表示该菜单的子菜单。

具体模板结构如下：

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
        {php $menu=widget('menu')->get()}
        {foreach $menu as $nav}
        <li class="nav-item has-submenu{:nav_active($nav['sign'], ' active open')}">
          <a href="javascript:;" class="submenu-toggle">
            <i class="{$nav.icon}"></i>
            <span>{$nav.title}</span>
            <i class="mdi mdi-chevron-down"></i>
          </a>
          <ul class="nav-submenu">
            {foreach $nav.children as $child}
            <li class="nav-item has-submenu{:nav_active($child['sign'], ' active open')}">
              <a href="javascript:;" class="submenu-toggle">
                <span>{$child.title}</span>
              </a>
              {if !empty($child.children)}
              <ul class="nav-submenu">
                {foreach $child.children as $grandchild}
                <li class="nav-item{:nav_active($grandchild['sign'], ' active')}">
                  <a href="{:url($grandchild.href)}">
                    <span>{$grandchild.title}</span>
                  </a>
                </li>
                {/foreach}
              </ul>
              {else}
              <ul class="nav-submenu">
                <li class="nav-item{:nav_active($child['sign'], ' active')}">
                  <a href="{:url($child.href)}">
                    <span>{$child.title}</span>
                  </a>
                </li>
              </ul>
              {/if}
            </li>
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

#### 4.3.3 菜单样式设计

不同层级菜单应通过样式实现视觉区分。一级菜单项保持原有风格，使用较大的图标和粗体标题；左侧 padding 设置为标准尺寸不需要额外缩进；背景色和字体颜色保持模板默认风格。

二级菜单项通过左侧增加缩进来表现层级关系，padding-left 值应比一级菜单多 16 至 24 像素；字体大小可略小于一级菜单；图标可使用圆点或短横线等小图标代替。

三级菜单项的缩进应比二级更多，padding-left 值继续增加 16 至 24 像素；字体大小可进一步缩小。

选中状态样式应继承 Light Year Admin Template v5 的 open 类效果，使用主题色背景和白色文字。

#### 4.3.4 手风琴交互实现

手风琴效果通过 JavaScript 实现，核心逻辑是点击一级菜单时先关闭其他已展开的一级菜单，然后切换当前菜单的展开状态。

在 public/static/js/main.min.js 中添加或修改以下 JavaScript 代码：

```javascript
$(function() {
    $('.sidebar-main').on('click', '.submenu-toggle', function(e) {
        e.preventDefault();
        var $this = $(this);
        var $parent = $this.closest('li.has-submenu');
        var $siblings = $parent.siblings('li.has-submenu');

        $siblings.each(function() {
            var $sibling = $(this);
            if ($sibling.hasClass('open')) {
                $sibling.removeClass('open');
                $sibling.find('> ul.nav-submenu').slideUp(200);
            }
        });

        if ($parent.hasClass('open')) {
            $parent.removeClass('open');
            $parent.find('> ul.nav-submenu').slideUp(200);
        } else {
            $parent.addClass('open');
            $parent.find('> ul.nav-submenu').slideDown(200);
        }
    });

    var currentSign = $('.sidebar-main .nav-item.active').closest('li.has-submenu').find('> a.submenu-toggle').text().trim();
    if (currentSign) {
        $('.sidebar-main .nav-item.active').closest('li.has-submenu').each(function() {
            $(this).addClass('open');
            $(this).parents('li.has-submenu').addClass('open');
        });
    }
});
```

## 五、菜单管理界面改造

### 5.1 菜单列表页面

菜单列表页面位于 app/admin/view/menu/index.html，需要调整表格列以展示层级关系。可通过在标题列中通过缩进和前缀表现层级关系，或者增加父级菜单列显示每个菜单的父级菜单名称。

列表排序应调整为按 pid ASC、sort ASC、id ASC 排序，确保同一父级下的子菜单相邻显示，并且按设定的顺序排列。

### 5.2 添加菜单页面

添加页面位于 app/admin/view/menu/add.html，需要新增父级菜单选择字段。该字段应使用下拉选择框实现，选项包括“顶级菜单”和所有现有菜单项。顶级菜单的 PID 为 0，二级菜单的 PID 为已存在的顶级菜单 ID。

修改后的添加页面表单应包含以下字段：

```html
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
    <label class="form-label" for="icon">图标</label>
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
```

### 5.3 编辑菜单页面

编辑页面位于 app/admin/view/menu/edit.html，同样需要增加父级菜单选择字段。默认值应设置为当前菜单的 PID 值。需要注意的是，编辑菜单时不允许将菜单的父级设置为自己或自己的子级，以避免循环引用问题。服务端应验证提交的父级 ID 不是当前菜单 ID，也不属于当前菜单的子菜单树。

修改后的编辑页面表单应包含以下字段：

```html
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
    <label class="form-label" for="icon">图标</label>
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
```

## 六、文件变更清单

### 6.1 数据库文件

需要修改的数据库相关文件位于 backup/bak_all_initialize/ 目录下。2_create_table.sql 文件需要添加 pid 字段的表结构定义，作为安装初始化使用；3_insert_xphp_menu_part1.sql 文件中的菜单数据需要调整，为现有菜单设置 pid=0 表示顶级菜单。

### 6.2 PHP 后端文件

需要修改的 PHP 后端文件包括：app/admin/model/Menu.php 文件需要调整 href 字段的验证规则以支持一级菜单 href 可为空，增加层级深度验证和循环引用验证；app/admin/widget/Menu.php 文件需要重写菜单数据获取逻辑，增加树形结构构建功能；app/admin/controller/Menu.php 文件需要增加获取父级菜单列表的方法，为表单提供父级选择数据。

### 6.3 视图模板文件

需要修改的视图模板文件包括：app/admin/view/public/sidebar.html 文件需要完全重写，实现三级嵌套菜单结构，适配 Light Year Admin Template v5 的原生菜单格式；app/admin/view/menu/index.html 文件需要调整列表展示，可选增加父级菜单列或层级缩进显示；app/admin/view/menu/add.html 文件需要增加父级菜单选择下拉框；app/admin/view/menu/edit.html 文件需要增加父级菜单选择下拉框并设置当前值为默认选项。

### 6.4 静态资源文件

需要新增或修改的静态资源文件位于 public/static/ 目录下。public/static/js/main.min.js 文件需要增加手风琴交互的 JavaScript 代码，参考 Light Year Admin Template v5 的实现方式。

## 七、测试验证计划

### 7.1 功能测试用例

功能测试应覆盖以下场景：添加顶级菜单并验证其正确显示在侧边栏顶部；添加二级菜单并验证其正确挂载到对应的一级菜单下；添加三级菜单并验证其正确挂载到对应的二级菜单下；验证最多只能添加三级菜单，无法创建四级菜单；验证手风琴效果，点击一级菜单时其他已展开的菜单正确折叠；验证当前菜单高亮功能，根据当前访问的 URL 正确显示 active 状态。

### 7.2 数据完整性测试

数据完整性测试应覆盖以下场景：编辑菜单的父级时验证不能设置为自身或子级；删除菜单时验证其子菜单的处理方式，可选择一并删除或阻止删除；禁用菜单时验证其子菜单的显示状态。

### 7.3 兼容性测试

兼容性测试应覆盖以下场景：现有数据库迁移后原有菜单是否正常显示；新增菜单功能与现有功能是否冲突；不同浏览器下菜单样式和交互是否正常。

## 八、实施注意事项

### 8.1 数据库修改范围

本次改造的数据库修改仅涉及备份文件，不直接修改生产数据库。用户需通过重新安装系统的方式完成数据库初始化。如果系统已上线运行，需要先备份现有数据库，然后导出菜单数据，修改备份 SQL 文件，执行修改后的 SQL 完成数据库升级。

### 8.2 向后兼容性

菜单表新增的 pid 字段默认值为 0，这意味着现有菜单记录在改造后仍被视为顶级菜单，不需要额外的数据迁移工作。菜单 Widget 的 get 方法返回数据结构从一维数组变为嵌套的树形数组，前端模板需要相应调整以适配新数据结构。

### 8.3 性能考虑

树形结构构建在服务端完成后一次性返回，减少前端处理量。菜单数据可根据实际需求决定是否启用缓存，在菜单变更频繁时建议保持不缓存以确保实时性。

### 8.4 扩展性预留

虽然本次改造明确限制为三级菜单，但数据库字段设计已为可能的四级菜单扩展预留空间。如果未来需要支持更多层级，只需修改前端模板和菜单层级限制逻辑，数据库结构无需变更。
