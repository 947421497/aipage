<?php
declare(strict_types=1);
namespace app\admin\controller;
use xphp\core\Jump;
use xphp\core\View;

class Profile
{
    use Jump;
    protected string $middleware = 'cp_auth';

    public function index()
    {
        $uid = session('user.id');
        $user = model('common.user')->find($uid);
        if (!$user) {
            $this->error('用户不存在', 400, 'cp/login');
        }
        return view()->with(['vo' => $user->toArray()]);
    }

    public function edit(array $req)
    {
        $uid = session('user.id');
        $user = model('common.user')->find($uid);
        if (!$user) {
            $this->error('用户不存在', 400, 'cp/login');
        }
        if ($this->isPost()) {
            $allowed = ['nickname', 'bio', 'qq', 'email', 'mobile'];
            $data = array_intersect_key($req, array_flip($allowed));
            $r = $user->save($data);
            $this->_jump(['修改成功', '修改失败'], $r, 'profile/index');
        }
        if (!IS_AJAX) {
            $this->_url('profile/index');
        }
        return View::init()->fetch('profile:_form', ['is_edit' => true, 'vo' => $user->toArray()]);
    }

    public function password(array $req)
    {
        if ($this->isPost()) {
            $user = model('common.user');
            $r = $user->changePwd($req);
            $this->_jump(['修改成功', $user->getError()], $r, 'profile/index');
        }
        if (!IS_AJAX) {
            $this->_url('profile/index');
        }
        return View::init()->fetch('profile:password_form', []);
    }
}
