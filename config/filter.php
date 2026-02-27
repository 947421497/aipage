<?php
/*------------------------------------------------------------------
 | Software: XPHP Framework
 | Site: https://xphp.net
 |------------------------------------------------------------------
 | (C)2020-2026 无念<24203741@qq.com>,All Rights Reserved.
 |-----------------------------------------------------------------*/
/**
 * 过滤处理配置
 */
return [
    'is_filter_req' => true, // 是否过滤req参数
    'except_field' => ['markdown'], // 排除字段(可写入script脚本)
    'filter_field' => [
        '/^(id|p)$/' => 'intval', // id分页自动转换数字
        '/^content(_\w+|\d+)?$/' => 'remove_xss', // 编辑器内容xss过滤
        '/^html(_\w+|\d+)?$/' => 'trim', // html内容
        //'pwd' => 'intval|md5', //演示字段md5
        '*' => 'clear_html', // 其他处理(必须放在最后)：去除html代码
    ],
];