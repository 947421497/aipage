<?php
declare(strict_types=1);
namespace app\admin\controller;

class Config extends Cp
{
    protected string $model = 'config';
    protected string $order = 'id ASC';
    protected int $limit = 0;

    public function add(array $req)
    {
        if ($this->isPost()) {
            $r = pdo()->trans(fn() => model($this->model)->save($req));
            if ($r) {
                $this->syncConfigFile();
            }
            $this->_jump(['添加成功', '添加失败'], $r, $this->jumpUrl);
        }
        return view();
    }

    public function edit(int $id, array $req)
    {
        $model = model($this->model)->find($id);
        if (!$model) {
            $this->error('记录不存在');
        }
        if ($this->isPost()) {
            $r = pdo()->trans(fn() => $model->save($req));
            if ($r) {
                $this->syncConfigFile();
            }
            $this->_jump(['修改成功', '修改失败'], $r, $this->jumpUrl);
        }
        return view()->with('vo', $model->toArray());
    }

    public function del(string $ids)
    {
        $ids = ids_filter($ids, true);
        if (!$ids) {
            $this->error('请选择ID');
        }
        $count = 0;
        foreach ($ids as $id) {
            $r = db('config')->where('id', $id)->delete();
            if ($r) $count++;
        }
        if ($count > 0) {
            $this->syncConfigFile();
        }
        $this->_jump(['删除成功', '删除失败'], $count, $this->jumpUrl);
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
        $tmp = $file . '.tmp.' . uniqid();
        file_put_contents($tmp, $content);
        rename($tmp, $file);
        $this->clearConfigCache();
    }

    private function clearConfigCache(): void
    {
        $dir = ROOT_PATH . '/runtime/index/config';
        if (is_dir($dir)) {
            dir_delete($dir);
        }
    }
}
