<?php
/*------------------------------------------------------------------
 | Software: XPHP Framework
 | Site: https://xphp.net
 |------------------------------------------------------------------
 | (C)2020-2026 无念<24203741@qq.com>,All Rights Reserved.
 |-----------------------------------------------------------------*/
declare(strict_types=1);

namespace xphp\core\session;

use xphp\core\Config;

/**
 * Redis驱动Session类
 */
class Redis extends Base
{
    protected object $redis;

    //连接
    public function connect(): void
    {
        $config = Config::init()->get('session.redis');
        $this->redis = new \Redis();
        $this->redis->connect($config['host'], $config['port']);
        if (!empty($config['pass'])) {
            $this->redis->auth($config['pass']);
        }
        $this->redis->select((int)$config['database']);
    }

    public function read(): array
    {
        $data = $this->redis->get($this->session_id);
        return $data ? json_decode($data, true) : [];
    }

    public function write(): void
    {
        $this->redis->set($this->session_id, json_encode($this->items, JSON_UNESCAPED_UNICODE));
    }

    public function gc(): void
    {
    }
}