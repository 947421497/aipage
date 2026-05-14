<?php
declare(strict_types=1);
namespace app\admin\controller;

class Config extends Cp
{
    protected string $model = 'config';
    protected string $order = 'id ASC';
    protected int $limit = 0;

    protected function _after_state(string $field, string $value, array $ids): void
    {
        $this->syncConfigFile();
    }

    public function add(array $req)
    {
        if ($this->isPost()) {
            $r = pdo()->trans(fn() => model($this->model)->save($req));
            if ($r) {
                $this->syncConfigFile();
            }
            $this->_jump(['添加成功', '添加失败'], $r, $this->jumpUrl);
        }
        if (!IS_AJAX) {
            $this->_url('index');
        }
        return $this->_form(['is_edit' => false]);
    }

    public function edit(int $id, array $req)
    {
        $model = model($this->model)->find($id);
        if (!$model) {
            $this->error('记录不存在');
        }
        if ($this->isPost()) {
            $req['config_key'] = $model->config_key;
            $r = pdo()->trans(fn() => $model->save($req));
            if ($r) {
                $this->syncConfigFile();
            }
            $this->_jump(['修改成功', '修改失败'], $r, $this->jumpUrl);
        }
        if (!IS_AJAX) {
            $this->_url('index');
        }
        return $this->_form(['is_edit' => true, 'vo' => $model->toArray()]);
    }

    public function del(string $ids)
    {
        $ids = ids_filter($ids, true);
        if (!$ids) {
            $this->error('请选择ID');
        }
        $items = model($this->model)->where([['status', '=', 0], ['id', 'in', $ids]])->select();
        if (empty($items)) {
            $this->_jump(['删除成功', '删除失败，未停用或已禁删'], 0, $this->jumpUrl);
        }
        $count = pdo()->trans(function () use ($items) {
            $c = 0;
            foreach ($items as $item) {
                $item->del();
                $c++;
            }
            return $c;
        });
        if ($count > 0) {
            $this->syncConfigFile();
        }
        $this->_jump(['删除成功', '删除失败，未停用或已禁删'], $count, $this->jumpUrl);
    }

    public function save_file()
    {
        if ($this->isAjax()) {
            $this->syncConfigFile();
            $this->success('生成文件成功', 'index');
        }
        $this->error('非法操作');
    }

    private function syncConfigFile(): void
    {
        $site = db('config')->where('status', 1)->order('id ASC')->column('config_value', 'config_key');
        $content = "<?php\nreturn " . var_export($site, true) . ';';
        $file = ROOT_PATH . '/app/index/config/site.php';
        $tmp = $file . '.tmp.' . str_replace('.', '', (string)microtime(true)) . bin2hex(random_bytes(4));
        $written = @file_put_contents($tmp, $content);
        if ($written === false) {
            @unlink($tmp);
            return;
        }
        if (is_file($file)) {
            @unlink($file);
        }
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            return;
        }
        $this->clearConfigCache();
    }

    private function clearConfigCache(): void
    {
        $runtimePath = ROOT_PATH . '/runtime';
        if (is_dir($runtimePath)) {
            foreach (new \DirectoryIterator($runtimePath) as $dir) {
                if ($dir->isDir() && !$dir->isDot()) {
                    $configDir = $dir->getPathname() . '/config';
                    if (is_dir($configDir)) {
                        dir_delete($configDir);
                    }
                }
            }
        }
        $globalConfigDir = $runtimePath . '/config';
        if (is_dir($globalConfigDir)) {
            dir_delete($globalConfigDir);
        }
    }
}
