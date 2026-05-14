<?php
declare(strict_types=1);
namespace app\common\model;
use xphp\core\Model;
class User extends Model
{
	protected string $table = 'user';
	protected string $pk = 'id';
    protected array $validate = [
        ['username', 'username|unique', '用户名4-12位|用户名已存在', FV_MUST, AC_INSERT],
        ['nickname', 'required|unique', '昵称必须|昵称已存在', FV_MUST, AC_BOTH],
        ['password', 'required', '请输入密码', FV_MUST, AC_INSERT],
        ['password', 'password', '密码5-12位', FV_VALUE, AC_BOTH],
        ['re_password', 'confirmed:password', '确认密码不一致', FV_ISSET, AC_BOTH],
        ['mobile', 'mobile', '手机号格式错误', FV_VALUE, AC_BOTH],
        ['email', 'email', '邮箱格式错误', FV_VALUE, AC_BOTH],
        ['qq', 'qq', 'QQ号格式错误', FV_VALUE, AC_BOTH],
    ];
    protected array $auto = [
        ['password', 'setPwd', 'method', FV_VALUE, AC_BOTH],
        ['level', '1', 'string', FV_MUST, AC_INSERT],
        ['status', '1', 'string', FV_MUST, AC_INSERT],
    ];
    protected array $filter = [
        ['username', FV_ISSET, AC_UPDATE],
        ['password', FV_EMPTY, AC_UPDATE],
    ];
    public function setPwd(string $val, array $data): string
    {
        if (empty($val)) {
            return $data['password'] ?? '';
        }
        return password_hash($val, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    protected function _after_update(array $before, array $after): void
    {
        $user = session('user');
        if ($after['id'] == $user['id'] && $after['nickname'] != $user['nickname']) {
            session('user.nickname', $after['nickname']);
        }
    }
    protected function _before_delete(array $data): void
    {
        $this->db = $this->db->where([['id', '>', 1], ['status', '=', 0]]);
    }
    public function login(array $req, bool $isAdmin = false): bool
    {
        $rule = [
            ['username', 'username', '用户名4-12位'],
            ['password', 'password', '密码5-12位'],
            ['captcha', 'captcha', '验证码错误'],
        ];
        $errors = validate($rule, $req)->getError();
        if (!empty($errors)) {
            $this->errors[] = current($errors);
            return false;
        }
        $user = $this->db->field('id,username,password,nickname,level,qq,status')->where('username', $req['username'])->find();
        if (!$user) {
            $this->errors[] = '用户不存在';
            return false;
        }
        if ($user['status'] == 0) {
            $this->errors[] = '用户已停用';
            return false;
        }
        if ($isAdmin && $user['level'] < 3) {
            $this->errors[] = '只有后台管理才能登录';
            return false;
        }
        if (!password_verify($req['password'], $user['password'])) {
            $this->errors[] = '密码错误';
            return false;
        }
        unset($user['password'], $user['status']);
        session('user', $user);
        return true;
    }
    public function changePwd(array $req): bool
    {
        $rule = [
            ['old_pwd', 'password', '旧密码5-12位'],
            ['new_pwd', 'password', '新密码5-12位'],
            ['re_pwd', 'confirmed:new_pwd', '确认密码不一致'],
        ];
        $errors = validate($rule, $req)->getError();
        if (!empty($errors)) {
            $this->errors[] = current($errors);
            return false;
        }
        $uid = session('user.id');
        $user = $this->db->field('password')->where('id', $uid)->find();
        if (!password_verify($req['old_pwd'], $user['password'])) {
            $this->errors[] = '旧密码错误';
            return false;
        }
        $newPwd = password_hash($req['new_pwd'], PASSWORD_BCRYPT, ['cost' => 12]);
        $r = $this->db->where('id', $uid)->setField('password', $newPwd);
        if (!$r) {
            $this->errors[] = '修改失败';
            return false;
        }
        return true;
    }
}
