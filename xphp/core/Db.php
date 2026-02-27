<?php
/*------------------------------------------------------------------
 | Software: XPHP Framework
 | Site: https://xphp.net
 |------------------------------------------------------------------
 | (C)2020-2026 无念<24203741@qq.com>,All Rights Reserved.
 |-----------------------------------------------------------------*/
declare(strict_types=1);

namespace xphp\core;

use xphp\core\db\Query;

/**
 * 数据库操作类
 */
class Db
{
    private static ?object $link = null;

    public static function connect($config = [], string $table = ''): object
    {
        static $conn = [];
        $sign = empty($config) ? 'default_' . $table : md5(json_encode($config)) . '_' . $table;
        $conn[$sign] ??= Query::init($table, $config);
        return static::$link = $conn[$sign];
    }

    public static function __callStatic(string $name, array $arguments)
    {
        if (static::$link === null) {
            static::connect();
        }
        return call_user_func_array([static::$link, $name], $arguments);
    }
}