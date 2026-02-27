<?php
declare(strict_types=1);
namespace app\admin\controller;
use xphp\core\Jump;

abstract class Cp
{
    use Jump;

    protected string $middleware = 'cp_auth'; // 后台验证
    protected string $model; // 模型名称
    protected string $order = 'id DESC'; // 列表排序
    protected int $limit = 10; // 列表获取条数(0获取全部)
    protected string $listFieldExcept = ''; // 列表排除字段,多个用,分开
    protected string $jumpUrl = 'index'; // 成功跳转URL
    protected array $stateList = ['status' => ['停用', '启用']]; // 状态操作设置

    // 条件设定
    protected function _where(): array
    {
        return [];
    }

    // 列表
    public function index()
    {
        $where = $this->_where();
        $list = model($this->model)->field($this->listFieldExcept, true)->where($where)->order($this->order)->paginate($this->limit);
        return view()->with('list', $list);
    }

    // 新增
    public function add(array $req)
    {
        if ($this->isPost()) {
            $r = pdo()->trans(function () use ($req) {
                model($this->model)->save($req);
            });
            $this->_jump(['添加成功', '添加失败'], $r, $this->jumpUrl);
        }
        return view();
    }

    // 修改
    public function edit(int $id, array $req)
    {
        $model = model($this->model)->find($id);
        if (!$model) {
            $this->error('记录不存在');
        }
        if ($this->isPost()) {
            $r = pdo()->trans(function () use ($model, $req) {
                $model->save($req);
            });
            $this->_jump(['修改成功', '修改失败'], $r, $this->jumpUrl);
        }
        return view()->with('vo', $model->toArray());
    }

    // 删除
    public function del(string $ids)
    {
        $ids = ids_filter($ids, true);
        if (!$ids) {
            $this->error('请选择ID');
        }
        $map = [];
        $map['status'] = 0;
        $model = model($this->model);
        $count = 0; //成功删除数量
        foreach ($ids as $id) {
            $tmp = $model->where($map)->find($id);
            if ($tmp) {
                $ok = pdo()->trans(function () use ($tmp) {
                    $tmp->del();
                });
                if ($ok) $count++;
            }
        }
        $this->_jump(['删除成功', '删除失败，未停用或已禁删'], $count, $this->jumpUrl);
    }

    // 状态切换
    public function state(string $ids, string $params)
    {
        $ids = ids_filter($ids, true);
        if (empty($ids)) {
            $this->error('请选择ID');
        }
        [$field, $value] = name_parse($params, 'status', '-');
        if (!isset($this->stateList[$field][$value])) {
            $this->error('参数未配置');
        }
        $title = $this->stateList[$field][$value];
        $map = [];
        $map[] = [$field, '<>', $value];
        if (count($ids) == 1) {
            $map['id'] = current($ids);
        } else {
            $map[] = ['id', 'in', $ids];
        }
        $model = model($this->model);
        $r = $model->where($map)->setField($field, $value);
        $model->widgetReload(); // 重载部件缓存
        $this->_jump([$title . '成功', $title . '失败'], $r, $this->jumpUrl);
    }
}