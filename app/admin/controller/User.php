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
}