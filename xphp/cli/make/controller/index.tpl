<?php
declare(strict_types=1);
namespace {{$namespace}}\controller;
use xphp\core\Jump;
class {{$class}}
{
	use Jump;
    public function index()
    {
        return view();
    }
    // 跳转信息示例(可删除)
    public function msg(int $ok = 0)
    {
        if ($ok == 1) {
            $this->success('操作成功提示信息', 'index/index');
        }
        $this->error('操作失败提示信息');
    }
}