# 三级菜单改造方案

## 一、改造目标

将现有扁平菜单升级为支持**三级嵌套**的树形菜单系统。

- 一级菜单：功能模块入口，有图标无链接，点击展开二级菜单
- 二级菜单：功能子模块，有链接可跳转，可展开三级菜单
- 三级菜单：具体功能页面，有链接可跳转，无子菜单

## 二、数据库改造

### 2.1 修改文件

**文件路径**：`backup/bak_all_initialize/2_create_table.sql`

### 2.2 修改内容

在 `xphp_menu` 表定义中，`id` 字段之后添加 `pid` 字段：

```sql
-- 表结构: xphp_menu --
CREATE TABLE `xphp_menu` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `pid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID，0表示顶级菜单',  -- 新增此行
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
  `href` varchar(100) NOT NULL DEFAULT '' COMMENT '链接',
  `sign` varchar(20) NOT NULL DEFAULT '' COMMENT '标识',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '图标',
  `is_sys` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0可删1禁删',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='菜单表';
```

### 2.3 改造后字段说明

| 字段 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| id | smallint(5) | 自增 | 菜单ID |
| pid | smallint(5) | 0 | 父级ID，0=顶级菜单 |
| title | varchar(50) | - | 菜单标题 |
| href | varchar(100) | - | 链接地址 |
| sign | varchar(20) | - | 菜单标识 |
| icon | varchar(100) | - | 图标样式 |
| is_sys | tinyint(1) | 0 | 0可删/1禁删 |
| sort | int(11) | 0 | 排序权重 |
| update_time | int(10) | 0 | 更新时间 |
| status | tinyint(1) | 0 | 状态 |

## 三、PHP 后端改造

### 3.1 文件清单

| 文件路径 | 操作 | 说明 |
|----------|------|------|
| app/admin/model/Menu.php | 重写 | 验证规则 |
| app/admin/widget/Menu.php | 重写 | 树形构建 |
| app/admin/controller/Menu.php | 修改 | 父级菜单查询 |

### 3.2 Model 层改造

**文件**：`app/admin/model/Menu.php`

**改造要点**：

1. **验证规则调整**
   - 添加 `pid` 字段验证
   - 调整 `href` 验证：子级菜单必须填写链接

2. **新增验证方法**
   - `validateDepth()`：验证层级深度不超过三级
   - `validateCircular()`：检测循环引用
   - `getDepth()`：计算菜单深度（从1开始）

3. **新增删除保护**
   - `_before_delete()`：禁止删除有子菜单的节点

**核心代码逻辑**：

```php
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
}
```

### 3.3 Widget 层改造

**文件**：`app/admin/widget/Menu.php`

**改造要点**：

1. 新增 `buildTree()` 方法，服务端构建树形结构
2. 返回嵌套数组供前端渲染

**核心代码逻辑**：

```php
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
```

### 3.4 Controller 层改造

**文件**：`app/admin/controller/Menu.php`

**改造要点**：

1. 新增 `assignParentMenus()` 方法：为表单提供父级菜单选项
2. 新增 `getAllChildIds()` 方法：递归获取所有子级ID
3. 新增 `buildSelectTree()` 方法：构建下拉选项树
4. `add()` 和 `edit()` 方法调用父级菜单赋值

**核心代码逻辑**：

```php
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
```

## 四、前端视图改造

### 4.1 文件清单

| 文件路径 | 操作 | 说明 |
|----------|------|------|
| app/admin/view/public/sidebar.html | 重写 | 侧边栏三级菜单 |
| app/admin/view/menu/add.html | 修改 | 添加父级选择 |
| app/admin/view/menu/edit.html | 修改 | 添加父级选择 |

### 4.2 侧边栏模板

**文件**：`app/admin/view/public/sidebar.html`

**改造要点**：

- 按照框架规范编写 HTML 结构
- 使用 `.nav-item-has-subnav` 类名触发框架内置手风琴
- 三级嵌套结构
- 使用 `{:nav_active()}` 实现当前菜单高亮

**HTML 结构规范**：

```
带有子菜单的 <li>  →  添加 .nav-item-has-subnav 类名
子菜单容器 <ul>   →  添加 .nav-subnav 类名
点击触发元素       →  必须是 <a> 标签
```

**模板核心结构**：

```html
<ul class="nav-drawer">
  <!-- 管理中心入口（无子菜单） -->
  <li class="nav-item">
    <a href="{:url('index/index')}"><i class="mdi mdi-home-city-outline"></i><span>管理中心</span></a>
  </li>

  <!-- 一级菜单（有子菜单） -->
  <li class="nav-item nav-item-has-subnav{:nav_active($nav['sign'], ' active open')}">
    <a href="javascript:;"><i class="{$nav.icon}"></i><span>{$nav.title}</span></a>
    <ul class="nav-subnav">
      <!-- 二级菜单 -->
      {foreach $nav.children as $child}
      {if !empty($child.children)}
      <li class="nav-item nav-item-has-subnav">
        <a href="javascript:;"><span>{$child.title}</span></a>
        <ul class="nav-subnav">
          <!-- 三级菜单 -->
          {foreach $child.children as $grandchild}
          <li class="nav-item{:nav_active($grandchild['sign'], ' active')}">
            <a href="{:url($grandchild.href)}"><span>{$grandchild.title}</span></a>
          </li>
          {/foreach}
        </ul>
      </li>
      {else}
      <li class="nav-item{:nav_active($child['sign'], ' active')}">
        <a href="{:url($child.href)}"><span>{$child.title}</span></a>
      </li>
      {/if}
      {/foreach}
    </ul>
  </li>
</ul>
```

### 4.3 添加菜单页面

**文件**：`app/admin/view/menu/add.html`

**新增字段**：

```html
<div class="mb-3">
  <label class="form-label" for="pid">父级菜单</label>
  <select class="form-select" id="pid" name="pid">
    <option value="0">顶级菜单</option>
    {foreach $parentMenus as $pm}
    <option value="{$pm.id}">{$pm.title}</option>
    {/foreach}
  </select>
</div>
```

### 4.4 编辑菜单页面

**文件**：`app/admin/view/menu/edit.html`

**新增字段**：

```html
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
```

## 五、手风琴交互说明

框架已在 `public/static/js/main.min.js` 中内置了完整的手风琴交互功能。

**触发条件**：

1. `<li>` 标签添加 `.nav-item-has-subnav` 类名
2. 子菜单容器 `<ul>` 添加 `.nav-subnav` 类名
3. 点击触发元素必须是 `<a>` 标签

**框架自动处理**：

- 手风琴效果（关闭同级已展开菜单）
- 展开/收起动画
- 自动滚动

**本次改造无需编写任何 JavaScript 代码**。

## 六、数据结构

### 6.1 后端返回的树形结构

```php
[
    [
        'id' => 1,
        'pid' => 0,
        'title' => '用户管理',
        'href' => '',
        'sign' => 'user',
        'icon' => 'mdi mdi-account',
        'children' => [
            [
                'id' => 5,
                'pid' => 1,
                'title' => '用户列表',
                'href' => 'user/index',
                'sign' => 'user_list',
                'children' => [
                    [
                        'id' => 10,
                        'pid' => 5,
                        'title' => '添加用户',
                        'href' => 'user/add',
                        'sign' => 'user_add',
                        'children' => []
                    ]
                ]
            ]
        ]
    ]
]
```

## 七、安全机制

| 机制 | 说明 |
|------|------|
| 层级验证 | 最多只能创建三级菜单 |
| 循环检测 | 禁止形成父子循环引用 |
| 删除保护 | 有子菜单的节点禁止删除 |
| 编辑过滤 | 自身及子级不出现在父级选项中 |

## 八、文件变更汇总

| 文件 | 操作类型 |
|------|----------|
| backup/bak_all_initialize/2_create_table.sql | 修改 |
| app/admin/model/Menu.php | 重写 |
| app/admin/widget/Menu.php | 重写 |
| app/admin/controller/Menu.php | 修改 |
| app/admin/view/public/sidebar.html | 重写 |
| app/admin/view/menu/add.html | 修改 |
| app/admin/view/menu/edit.html | 修改 |
| public/static/js/main.min.js | 无需修改 |

## 九、实施步骤

1. **备份现有数据库**
2. **修改 `backup/bak_all_initialize/2_create_table.sql`**，添加 `pid` 字段
3. **重新安装系统**，执行新的 SQL 初始化数据库
4. **修改 PHP 后端文件**：model、widget、controller
5. **修改前端视图文件**：sidebar、add、edit
6. **测试验证功能**

## 十、向后兼容

- 现有菜单 `pid=0`，自动成为顶级菜单
- 无需额外数据迁移
- 原有功能不受影响
