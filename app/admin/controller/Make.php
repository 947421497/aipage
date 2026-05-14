<?php
declare(strict_types=1);
namespace app\admin\controller;
use xphp\core\Jump;

class Make
{
    use Jump;
    protected string $middleware = 'cp_auth';

    // 首页
    public function index()
    {
        return view();
    }

    // 操作
    public function action()
    {
        if ($this->isPost()) {
            $sign = input('post.sign', '', 'clear_html');
            if (!preg_match('/^\w+$/', $sign)) {
                $this->error('标识格式错误！');
            }
            if (in_array($sign, ['backup', 'menu', 'config', 'fast', 'index', 'login', 'profile', 'public', 'user'])) {
                $this->error($sign.' 模块已存在，无法生成');
            }
            $cmd = input('post.cmd', '', 'clear_html');
            if ($cmd == 'make_mvc') {
                // 生成模型
                cli('make:model admin@'.$sign.' id _def -f');
                // 生成控制器
                cli('make:ctrl admin@'.$sign.' _cp -f');
                // 生成模板
                cli('make:view admin@'.$sign.' index index -f');
                cli('make:view admin@'.$sign.' _form _form -f');
                $this->success('MVC生成成功', 'index');
            } elseif ($cmd == 'remove_mvc') {
                // 移除MVC
                cli('remove:model admin@'.$sign);
                cli('remove:ctrl admin@'.$sign);
                cli('remove:view admin@'.$sign);
                $this->success('MVC移除成功', 'index');
            } elseif ($cmd == 'make_table') {
                // 生成表
                $r = cli('make:table admin@'.$sign.' '.$sign);
                $this->_jump(['数据表生成成功', '生成失败，请先删除原表再生成'], $r, 'index');
            } elseif ($cmd == 'remove_table') {
                // 移除表
                $r = cli('remove:table '.$sign);
                $this->_jump(['数据表移除成功', '数据表移除失败'], $r, 'index');
            }
        }
        $this->error('非法操作');
    }
}