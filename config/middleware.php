<?php
/*------------------------------------------------------------------
 | Software: XPHP Framework
 | Site: https://xphp.net
 |------------------------------------------------------------------
 | (C)2020-2026 无念<24203741@qq.com>,All Rights Reserved.
 |-----------------------------------------------------------------*/
/**
 * 中间件配置
 */
return [
    // 控制器中间件
    'controller' => [
        'auth' => [
            \middleware\controller\Auth::class, // 前台登录验证
        ],
        'cp_auth' => [
            \middleware\controller\CpAuth::class, // 后台验证
        ],
    ],
    // 全局中间件
    'common' => [
        \middleware\Boot::class, // 框架启动
    ],
    // 框架中间件
    'framework' => [
        'controller_start' => [
           \middleware\Csrf::class, // 表单令牌验证
        ], // 控制器开始
        'database_query' => [], // 查询sql
        'database_execute' => [], // 执行sql
    ],
];