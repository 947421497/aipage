<?php
declare(strict_types=1);
namespace app\admin\controller;
use xphp\core\Jump;

class Config
{
    use Jump;
    protected string $middleware = 'cp_auth'; // 后台验证

    // 配置
    public function index(array $req)
    {
        if ($this->isPost()) {
            $site = !isset($req['id']) ? model('config') : model('config')->find($req['id']);
            $r = $site->save($req);
            $this->_jump(['操作成功，生成配置后生效', '操作失败'], $r, 'index');
        }
        $list = db('config')->order('id ASC')->select();
        return view()->with('list', $list);
    }

    // 删除
    public function del(int $id)
    {
        $r = db('config')->where('id', $id)->delete();
        $this->_jump(['删除成功，生成配置后生效', '删除失败'], $r, 'index');
    }

    // 生成配置文件
    public function save_file()
    {
        if ($this->isAjax()) {
            $site = db('config')->order('id ASC')->column('config_value', 'config_key');
            $config = var_export($site, true);
            file_put_contents(ROOT_PATH . '/app/index/config/site.php', "<?php\nreturn " . $config . ';');
            $this->success('生成文件成功', 'index');
        }
        $this->error('非法操作');
    }
}