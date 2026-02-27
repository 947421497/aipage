<?php
declare(strict_types=1);
namespace app\index\controller;
use xphp\core\Jump;
class Api
{
	use Jump;
    // 清除缓存
    public function clear()
    {
        cache_clear();
        cli('clear:runtime index');
        $this->success('清除缓存成功', '[history]');
    }
}