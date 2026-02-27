<?php
declare(strict_types=1);
namespace {{$namespace}}\controller;
use xphp\core\Jump;
class {{$class}}
{
	use Jump;
    public function __call(string $name, array $arguments)
    {
        $msg = $arguments[0] ?? '';
        $code = str_starts_with($name, '_') ? substr($name, 1) : 400;
        $this->error($msg, (int)$code);
    }
    public function _404(string $msg, array $args = [])
    {
        return view('public/_404')->with('msg', $msg);
    }
}