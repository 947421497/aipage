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