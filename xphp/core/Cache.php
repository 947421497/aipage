<?php
/*------------------------------------------------------------------
 | Software: XPHP Framework
 | Site: https://xphp.net
 |------------------------------------------------------------------
 | (C)2020-2026 无念<24203741@qq.com>,All Rights Reserved.
 |-----------------------------------------------------------------*/
declare(strict_types=1);

namespace xphp\core;
/**
 * 缓存处理类
 */
class Cache
{
    protected static ?object $link = null;

    public static function init(?string $driver = null): object
    {
        static $cache = [];
        $driver ??= Config::init()->get('cache.driver', 'file');
        if (!isset($cache[$driver])) {
            $class = '\\xphp\\core\\cache\\' . ucfirst($driver);
            $cache[$driver] = call_user_func([$class, 'init']);
        }
        return static::$link = $cache[$driver];
    }

    public static function __callStatic(string $name, array $arguments)
    {
        if (is_null(static::$link)) {
            static::init();
        }
        return call_user_func_array([static::$link, $name], $arguments);
    }
}