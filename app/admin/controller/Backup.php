<?php
declare(strict_types=1);
namespace app\admin\controller;
use xphp\core\Jump;

class Backup
{
    use Jump;

    protected string $middleware = 'cp_auth'; // 后台验证
    protected object $pdo; // pdo对象
    protected string $dbName; // 数据库名
    protected string $prefix; // 表前缀
    protected string $bakPath; // 备份路径

    public function __construct()
    {
        $this->pdo = pdo();
        $this->dbName = $this->pdo->getConfig('db_name');
        $this->prefix = $this->pdo->getConfig('table_prefix');
        $this->bakPath = ROOT_PATH . '/backup';
    }

    public function index()
    {
        $list = $this->pdo->getResult('SHOW TABLE STATUS');
        foreach ($list as &$vo) {
            $vo['Data_length'] = size_to_kb($vo['Data_length']);
            $vo['Update_time'] ??= $vo['Create_time'];
        }
        return view()->with('list', $list);
    }

    // 表结构
    public function structure(string $table): array
    {
        if (!is_table($table)) {
            $this->error('表名格式错误');
        }
        $vo = $this->pdo->getResult("SHOW CREATE TABLE `$table`");
        return ['status' => 1, 'table' => $vo[0]['Table'], 'data' => $vo[0]['Create Table']];
    }

    // 优化表
    public function optimize(string $ids)
    {
        $tables = array_filter(explode(',', $ids), 'is_table');
        if (empty($tables)) {
            $this->error('请选择表');
        }
        if (count($tables) > 5) {
            $this->error('每次最多只能优化5张表');
        }
        $tables = implode(',', $tables);
        $this->pdo->execute("OPTIMIZE TABLE `$tables`");
        $this->success('成功优化表：' . $tables, 'index');
    }

    // 修复表
    public function repair(string $ids)
    {
        $tables = array_filter(explode(',', $ids), 'is_table');
        if (empty($tables)) {
            $this->error('请选择表');
        }
        if (count($tables) > 5) {
            $this->error('每次最多只能修复5张表');
        }
        $tables = implode(',', $tables);
        $this->pdo->execute("REPAIR TABLE `$tables`");
        $this->success('成功修复表：' . $tables, 'index');
    }

    // 备份表
    public function backup(string $ids = '')
    {
        function_exists('set_time_limit') && set_time_limit(0);
        $tables = array_filter(explode(',', $ids), 'is_table');
        if (empty($tables)) {
            $tables = $this->_get_all_table();
            $type = 'all';
        } else {
            $type = 't' . count($tables) . '_' . substr($tables[0], strlen($this->prefix));
        }
        $path = $this->bakPath . '/bak_' . $type . '_' . date('YmdHis') . '_' . rand(1000, 9999);

        $this->_bak_lock();
        register_shutdown_function([$this, '_bak_unlock']);

        try {
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            $this->_bak_drop_table($tables, $path);
            $this->_bak_create_table($tables, $path);
            $this->_bak_insert_table($tables, $path);
            $this->_bak_unlock();
            $this->success('备份成功', 'restore');
        } catch (\Throwable $e) {
            $this->_bak_unlock();
            $this->error('备份失败：' . $e->getMessage());
        }
    }

    private function _bak_lock(string $file = 'backup.lock'): void
    {
        $lockFile = $this->bakPath . '/' . $file;
        $time = time();
        if (is_file($lockFile)) {
            if (($time - filemtime($lockFile)) < 600) {
                $this->error('检测到有一个备份任务正在执行，请稍后再试！');
            }
            @unlink($lockFile);
        }
        $lock = file_put_contents($lockFile, $time); //创建锁文件
        if (!$lock) {
            $this->error('backup目录不可写！');
        }
    }

    protected function _bak_unlock(string $file = 'backup.lock'): void
    {
        if (file_exists($this->bakPath . '/' . $file)) {
            @unlink($this->bakPath . '/' . $file);
        }
    }

    private function _bak_drop_table(array $tables, string $path): void
    {
        $sql = '';
        foreach ($tables as $table) {
            $sql .= "-- 清空表: $table --\r\nDROP TABLE IF EXISTS `$table`;\r\n-- <fen> --\r\n";
        }
        $write_bytes = file_put_contents($path . '/1_drop_table.sql', $sql);
        if ($write_bytes <= 0) {
            $this->_bak_unlock();
            $this->error('移除表SQL写入失败！');
        }
    }

    private function _bak_create_table(array $tables, string $path): void
    {
        $sql = '';
        foreach ($tables as $table) {
            $res = $this->pdo->getResult("SHOW CREATE TABLE `$table`");
            $sql .= "-- 表结构: $table --\r\n" . $res[0]['Create Table'] . ";\r\n-- <fen> --\r\n";
        }
        $sql = mb_convert_encoding($sql, 'UTF-8', 'auto');
        $write_bytes = file_put_contents($path . '/2_create_table.sql', $sql);
        if ($write_bytes <= 0) {
            $this->_bak_unlock();
            $this->error('构建表结构SQL写入失败！');
        }
    }

    private function _bak_insert_table(array $tables, string $path): void
    {
        $fail_table = []; // 写入失败的表
        $pageSize = 1000; // 每页1000条
        foreach ($tables as $table) {
            $model = db(substr($table, strlen($this->prefix))); //db对象
            $count = $model->count(); //总记录数
            if ($count > 0) {
                $totalPage = ceil($count / $pageSize); //总页数
                $page = 1; //当前页
                while ($page <= $totalPage) {
                    $sql = "-- 表数据: {$table}({$page}/{$totalPage}) 每页: {$pageSize} --\r\n";
                    $start = $pageSize * ($page - 1);
                    $list = $model->limit($start . ',' . $pageSize)->select();
                    foreach ($list as $vo) {
                        $sql .= $model->getSql()->insert($vo);
                        $sql .= ";-- <fen> --\r\n";
                    }
                    $write_bytes = file_put_contents($path . '/3_insert_' . $table . '_part' . $page . '.sql', $sql);
                    if ($write_bytes <= 0) {
                        $fail_table[] = $table;
                    }
                    $page++;
                }
                usleep(50000);
            }
            usleep(100000);
        }
        if (!empty($fail_table)) {
            $this->_bak_unlock();
            $fail_table = array_unique($fail_table);
            $this->error('表' . implode(',', $fail_table) . '数据SQL写入失败！');
        }
    }

    private function _get_all_table(): array
    {
        $dbName = $this->dbName;
        $list = $this->pdo->getResult("SHOW TABLES FROM `$dbName`");
        $tables = [];
        foreach ($list as $vo) {
            $tables[] = $vo['Tables_in_' . $dbName];
        }
        return $tables;
    }

    public function restore()
    {
        $list = $this->_get_bak_list($this->bakPath);
        return view()->with('list', $list);
    }

    // 获取备份列表
    private function _get_bak_list(string $path): array
    {
        $list = [];
        if (is_dir($path)) {
            $open = opendir($path);
            if ($open) {
                while (false !== ($name = readdir($open))) {
                    if ($name == '.' || $name == '..') {
                        continue;
                    }
                    if (is_dir($path . '/' . $name)) {
                        $ctime = filectime($path . '/' . $name);
                        $list[$ctime]['name'] = date('mdHis', $ctime);
                        $list[$ctime]['path'] = $name;
                        $list[$ctime]['time'] = get_time_ago($ctime); //date('Y-m-d H:i:s', $ctime);
                    }
                }
                closedir($open);
            }
        }
        krsort($list); //按时间排序
        return $list;
    }

    // 删除备份
    public function bak_del()
    {
        $path = input('path', '', 'clear_html');
        if (empty($path)) {
            $this->error('请选择备份');
        }
        if (!preg_match('/^bak_\w{3,60}$/', $path)) {
            $this->error('备份路径格式为 bak_***');
        }
        $r = dir_delete($this->bakPath . '/' . $path, true);
        $this->_jump(['删除成功', '删除失败'], $r, 'restore');
    }

    // 下载备份
    public function bak_down()
    {
        $path = input('path', '', 'clear_html');
        if (empty($path)) {
            $this->error('请选择备份');
        }
        if (!is_dir($this->bakPath . '/' . $path)) {
            $this->error('备份不存在');
        }
        $zipFile = $this->bakPath . '/' . $path . '.zip';
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }
        $zip = new \PharData($zipFile);
        $zip->buildFromDirectory($this->bakPath . '/' . $path, '/\.sql$/');
        if (!is_file($zipFile)) {
            $this->error('备份文件不存在');
        }
        if (ob_get_length() !== false) @ob_end_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition:attachment;filename=' . $path . '.zip');
        header('Content-Length:' . filesize($zipFile));
        @readfile($zipFile);
        @unlink($zipFile);
        exit;
    }

    // 备份还原
    public function bak_restore()
    {
        $path = input('path', '', 'clear_html');
        if (empty($path)) {
            $this->error('请选择备份');
        }
        if (!is_dir($this->bakPath . '/' . $path)) {
            $this->error('备份不存在');
        }
        $glob = @glob($this->bakPath . '/' . $path . '/*.sql');
        sort($glob);
        foreach ($glob as $file) {
            $data = file_get_contents($file);
            $data = mb_convert_encoding($data, 'UTF-8', 'auto');
            $sqlList = explode('-- <fen> --', $data);
            if (count($sqlList) > 1) {
                array_pop($sqlList);
            }
            foreach ($sqlList as $sql) {
                $this->pdo->execute($sql);
            }
            usleep(100000);
        }
        cache_clear();
        cli('clear:runtime admin index common'); // 清除缓存
        $this->success('数据已还原', 'restore');
    }
}