<?php
declare(strict_types=1);
namespace {{$namespace}}\controller;
class {{$class}} extends Cp
{
    protected string $model = '{{$name}}';
    protected string $order = 'id DESC';

    protected function _where(): array
    {
        $where = [];
        $title = input('title', '', 'clear_html');
        if (!empty($title)) {
            $where[] = ['title', 'like', '%' . $title . '%'];
        }
        return $where;
    }
}