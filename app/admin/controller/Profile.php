<?php
declare(strict_types=1);
namespace app\admin\controller;
use xphp\core\Jump;

class Profile
{
    use Jump;
    protected string $middleware = 'cp_auth';

    // 个人中心
    public function index()
    {
        $uid = session('user.id');
        $user = model('common.user')->find($uid);
        return view()->with(['vo' => $user->toArray()]);
    }

    // 修改资料
    public function edit(array $req)
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