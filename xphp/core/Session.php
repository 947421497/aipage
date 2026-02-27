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
 * Session类
 */
class Session
{
    protected static ?object $link = null;

    public static function init(): object
    {
        if (is_null(static::$link)) {
            $driver = Config::init()->get('session.driver', 'file');
            $class = '\\xphp\\core\\session\\' . ucfirst($driver);
            static::$link = call_user_func([$class, 'init']);
        }
        return static::$link;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        if (is_null(static::$link)) {
            static::init();
        }
        return call_user_func_array([static::$link, $name], $arguments);
    }
}