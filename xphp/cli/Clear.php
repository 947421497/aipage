<?php
/*------------------------------------------------------------------
 | Software: XPHP Framework
 | Site: https://xphp.net
 |------------------------------------------------------------------
 | (C)2020-2026 无念<24203741@qq.com>,All Rights Reserved.
 |-----------------------------------------------------------------*/
declare(strict_types=1);

namespace xphp\cli;
/**
 * 清理命令
 */
class Clear extends Command
{
    public function cli(): bool
    {
        if (!$this->isCall) {
            echo "|++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++|\n";
            echo "| 1. clear:runtime [app_name(or *)]                                          |\n";
            echo "| 2. clear:database                                                          |\n";
            echo "|++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++|\n";
        }
        return true;
    }

    // 清除运行缓存
    public function runtime(array $req = []): ?bool
    {
        if (empty($req)) {
            dir_delete(ROOT_PATH . '/runtime/');
        } else {
            foreach ($req as $app) {
                dir_delete(ROOT_PATH . '/runtime/' . $app, true);
            }
        }
        return $this->success();
    }

    // 清空所有数据表
    public function database(array $req = []): ?bool
    {
        $pdo = pdo();
        $list = $pdo->getResult('SHOW TABLES');
        foreach ($list as $vo) {
            $table = current($vo);
            $pdo->execute("DROP TABLE IF EXISTS `$table`");
        }
        return $this->success();
    }
}