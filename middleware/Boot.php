<?php
/*------------------------------------------------------------------
 | Software: XPHP Framework
 | Site: https://xphp.net
 |------------------------------------------------------------------
 | (C)2020-2026 无念<24203741@qq.com>,All Rights Reserved.
 |-----------------------------------------------------------------*/
declare(strict_types=1);

namespace middleware;

use xphp\core\Config;
use Closure;

/**
 * 全局中间件
 */
class Boot
{
    public function run(Closure $next): void
    {
        header('X-Powered-By:XPHP' . __VERSION__);
        $this->_check_install();
        $next();
    }

    // 检测安装状态转跳安装脚本
    protected function _check_install(): void
    {
        $app_check_install = Config::init()->get('app.app_check_install', []);
        if (!empty($app_check_install) && in_array(APP_NAME, $app_check_install) && !file_exists(ROOT_PATH . '/backup/install.lock') && !str_contains($_SERVER['REQUEST_URI'], 'install')) {
            header('Location:' . __HOST__ . '/install.php');
            exit();
        }
    }
}