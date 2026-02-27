<?php
declare(strict_types=1);
namespace app\admin\controller;
use xphp\core\Jump;

class Login
{
    use Jump;

    // 验证码图片
    public function captcha()
    {
        return extend('captcha')->make();
    }

    // 登录
    public function login(array $req)
    {
        if ($this->isPost()) {
            $model = model('common.user');
            $r = $model->login($req, true);
            $this->_jump(['登录成功', $model->getError()], $r, 'index/index');
        }
        return view();
    }

    // 退出
    public function logout()
    {
        session(null);
        $this->success('退出成功', 'login/login');
    }
}