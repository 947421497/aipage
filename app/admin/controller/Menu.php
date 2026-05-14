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
        if ($this->isPost()) {
            $r = pdo()->trans(fn() => model($this->model)->save($req));
            $this->_jump(['添加成功', '添加失败'], $r, $this->jumpUrl);
        }
        if (!IS_AJAX) {
            $this->_url('index');
        }
        $parentOptions = MenuModel::getParentOptions();
        return $this->_form(['is_edit' => false, 'parentOptions' => $parentOptions]);
    }

    public function edit(int $id, array $req)
    {
        $model = model($this->model)->find($id);
        if (!$model) {
            $this->error('记录不存在');
        }
        if ($this->isPost()) {
            if (isset($req['parent_id'])) {
                $parentId = (int)$req['parent_id'];
                if ($parentId === $id) {
                    $this->error('父级不能是自身');
                }
                $childIds = MenuModel::getChildIds([$id]);
                if (in_array($parentId, $childIds)) {
                    $this->error('父级不能是自身的子菜单');
                }
            }
            $r = pdo()->trans(fn() => $model->save($req));
            $this->_jump(['修改成功', '修改失败'], $r, $this->jumpUrl);
        }
        if (!IS_AJAX) {
            $this->_url('index');
        }
        $parentOptions = MenuModel::getParentOptions($id);
        return $this->_form(['is_edit' => true, 'vo' => $model->toArray(), 'parentOptions' => $parentOptions]);
    }

    protected function _after_state(string $field, string $value, array $ids): void
    {
        if ($field === 'status' && $value === '0') {
            $childIds = MenuModel::getChildIds($ids);
            if (!empty($childIds)) {
                model($this->model)->where([['id', 'in', $childIds], ['status', '<>', '0']])->setField('status', '0');
            }
        }
    }
}
