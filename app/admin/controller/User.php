<?php
declare(strict_types=1);
namespace app\admin\controller;
class User extends Cp
{
    protected string $model = 'common.user';
    protected string $order = 'status DESC,id DESC';

    protected function _where(): array
    {
        $where = [];
        $name = input('name', '', 'clear_html');
        if (!empty($name)) {
            $where[] = ['username|nickname', 'like', '%' . $name . '%'];
        }
        $level = input('level', 0, 'intval');
        if ($level > 0) {
            $where[] = ['level', '=', $level];
        }
        return $where;
    }

    public function state(string $ids, string $params)
    {
        [$field, $value] = name_parse($params, 'status', '-');
        if ($field === 'status' && $value === '0') {
            $currentId = session('user.id');
            $idList = ids_filter($ids, true);
            if (in_array($currentId, $idList)) {
                $this->error('不能停用当前登录用户');
            }
        }
        parent::state($ids, $params);
    }
}