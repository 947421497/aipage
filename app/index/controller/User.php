<?php
declare(strict_types=1);
namespace app\index\controller;
use xphp\core\Jump;
class User
{
	use Jump;
    protected array $middleware = [
        'auth' => ['except' => ['captcha','login','logout','register','info']],
    ];

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
            $r = $model->login($req);
            $this->_jump(['登录成功', $model->getError()], $r, $req['referer'] ?? 'index/index');
        }
        return view();
    }

    // 退出
    public function logout()
    {
        session(null);
        $this->success('退出成功', '[history]');
    }

    // 注册
    public function register(array $req)
    {
        if ($this->isPost()) {
            $r = model('common.user')->save($req);
            $this->_jump(['注册成功', '注册失败'], $r, 'user/login?from=' . $req['username']);
        }
        return view();
    }

    // 查看个人信息
    public function info(string $id)
    {
        $vo = db('user')->where('id', $id)->find();
        if (!$vo) {
            $this->error('用户不存在');
        }
        return view()->with('vo', $vo);
    }

    // 个人中心
    public function index()
    {
        $uid = session('user.id');
        $user = model('common.user')->find($uid);
        return view()->with(['vo' => $user->toArray()]);
    }

    // 修改资料
    public function profile(array $req)
    {
        $uid = session('user.id');
        $user = model('common.user')->find($uid);
        if ($this->isPost()) {
            $r = $user->save($req);
            $this->_jump(['修改成功', '修改失败'], $r, 'index');
        }
        return view()->with('vo', $user->toArray());
    }

    // 修改密码
    public function password(array $req)
    {
        if ($this->isPost()) {
            $user = model('common.user');
            $r = $user->changePwd($req);
            $this->_jump(['修改成功', $user->getError()], $r, 'index');
        }
        return view();
    }
}