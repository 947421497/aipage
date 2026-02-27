<?php
/*------------------------------------------------------------------
 | Software: XPHP Framework
 | Site: https://xphp.net
 |------------------------------------------------------------------
 | (C)2020-2026 无念<24203741@qq.com>,All Rights Reserved.
 |-----------------------------------------------------------------*/
declare(strict_types=1);
namespace middleware\controller;
use Closure;
/**
 * 通用登录验证
 */
class Auth
{
    public function run(Closure $next): void
    {
        if (!session('?user')) {
            if (IS_AJAX) {
                halt('', 401); // AJAX未登录提示
            }
            header('Location:' . url('user/login')); // 转跳到登录页
            exit();
        }
        $next();
    }
}