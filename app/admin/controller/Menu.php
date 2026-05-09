<?php
declare(strict_types=1);
namespace app\admin\controller;
use app\admin\model\Menu as MenuModel;

class Menu extends Cp
{
    protected string $model = 'menu';
    protected string $order = 'sort ASC,id ASC';
    protected int $limit = 0;

    public function index()
    {
        $list = MenuModel::getTree();
        return view()->with('list', $list);
    }

    public function add(array $req)
    {
        $parentOptions = MenuModel::getParentOptions();
        if ($this->isPost()) {
            $r = pdo()->trans(fn() => model($this->model)->save($req));
            $this->_jump(['添加成功', '添加失败'], $r, $this->jumpUrl);
        }
        return view()->with('parentOptions', $parentOptions);
    }

    public function edit(int $id, array $req)
    {
        $model = model($this->model)->find($id);
        if (!$model) {
            $this->error('记录不存在');
        }
        if ($this->isPost()) {
            $r = pdo()->trans(fn() => $model->save($req));
            $this->_jump(['修改成功', '修改失败'], $r, $this->jumpUrl);
        }
        $parentOptions = MenuModel::getParentOptions($id);
        return view()->with('vo', $model->toArray())->with('parentOptions', $parentOptions);
    }
}
