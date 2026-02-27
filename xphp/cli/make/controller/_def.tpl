<?php
declare(strict_types=1);
namespace {{$namespace}}\controller;
use xphp\core\Jump;
class {{$class}}
{
	use Jump;
    public function index()
    {
        return __METHOD__;
    }
}