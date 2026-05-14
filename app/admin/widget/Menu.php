<?php
declare(strict_types=1);
namespace app\admin\widget;
use xphp\core\Widget;
use app\admin\model\Menu as MenuModel;

class Menu extends Widget
{
    protected string $tag = 'menu';
    protected int $expire = 0;

    public function set($id = '', array $options = [])
    {
        return MenuModel::getTree(true);
    }
}
