<?php
declare(strict_types=1);
namespace app\install\controller;
use xphp\core\Jump;
class Index
{
    use Jump;
    protected string $checkTable = 'user'; // 检测表，存在则已安装

    // 安装界面与安装操作
    public function index()
    {
        if ($this->isPost()) {
            $bak_path = dir_init(ROOT_PATH . '/backup', 0777);
            $lock_file = $bak_path . '/install.lock';
            $pdo = pdo();
            if (!empty($this->checkTable) && $pdo->hasTable($this->checkTable)) {
                file_put_contents($lock_file, date('Y-m-d H:i:s'));
                $this->error('检测到数据库表已存在，重装需清空数据库，或进入后台还原。');
            }
            $path = input('post.path', '', 'clear_html');
            if (!preg_match('/^bak_\w{3,60}$/', $path)) {
                $this->error('备份路径格式为 bak_***');
            }
            if (!is_dir($bak_path. '/' . $path)) {
                $this->error('备份文件夹不存在！');
            }
            $glob = @glob(ROOT_PATH . '/backup/' . $path . '/*.sql');
            sort($glob);
            foreach ($glob as $file) {
                $data = file_get_contents($file);
                $data = mb_convert_encoding($data, 'UTF-8', 'auto');
                $sqlList = explode('-- <fen> --', $data);
                if (count($sqlList) > 1) {
                    array_pop($sqlList);
                }
                foreach ($sqlList as $sql) {
                    $pdo->execute($sql);
                }
                usleep(100000);
            }
            file_put_contents($lock_file, date('Y-m-d H:i:s'));
            $this->success('安装成功!默认管理员：admin 密码：admin', __HOST__);
        }
        return view();
    }

    // 清空数据库表(注意备份数据)
    public function clear_database()
    {
        if (!file_exists(ROOT_PATH . '/backup/install.lock')) {
            cli('clear:database');
            $this->success('清空数据库表成功！', 'index/index');
        }
        $this->error('删除install.lock后，才能清空数据库表！');
    }
}