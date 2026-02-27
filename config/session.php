<?php
/*------------------------------------------------------------------
 | Software: XPHP Framework
 | Site: https://xphp.net
 |------------------------------------------------------------------
 | (C)2020-2026 无念<24203741@qq.com>,All Rights Reserved.
 |-----------------------------------------------------------------*/
/**
 * Session配置
 */
return [
    'driver' => 'file', // 支持file,redis驱动
    'name' => 'xphp_session', // 名称前缀
    'expire' => 86400, //1 day
    'domain'   => '',
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'pass' => '',
        'database' => 0,
    ],
];