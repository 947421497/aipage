<?php
/*------------------------------------------------------------------
 | Software: XPHP Framework
 | Site: https://xphp.net
 |------------------------------------------------------------------
 | (C)2020-2026 无念<24203741@qq.com>,All Rights Reserved.
 |-----------------------------------------------------------------*/

use xphp\core\App;

define('ROOT_PATH', strtr(realpath(__DIR__ . '/../'), '\\', '/'));
require ROOT_PATH . '/xphp/bootstrap.php';
App::init('{{$app}}')->boot();