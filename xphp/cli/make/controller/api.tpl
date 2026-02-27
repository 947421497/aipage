<?php
declare(strict_types=1);
namespace {{$namespace}}\controller;
use xphp\core\Jump;
class {{$class}}
{
	use Jump;
    // 清除缓存
    public function clear()
    {
        cache_clear();
        cli('clear:runtime {{$app|default='index'}}');
        $this->success('清除缓存成功', '[history]');
    }
}