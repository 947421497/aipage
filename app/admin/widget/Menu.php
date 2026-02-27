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
        return db('menu')->where('status=1')->order('sort ASC,id ASC')->select();
    }
}