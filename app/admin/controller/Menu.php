<?php
declare(strict_types=1);
namespace app\admin\controller;
class Menu extends Cp
{
    protected string $model = 'menu';
    protected string $order = 'sort ASC,id ASC';
}