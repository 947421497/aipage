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