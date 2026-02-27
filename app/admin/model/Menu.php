<?php
declare(strict_types=1);
namespace app\admin\model;
use xphp\core\Model;
class Menu extends Model
{
	protected string $table = 'menu';
	protected string $pk = 'id';
	// 自动验证
	protected array $validate = [
        ['title', 'chs_alpha_num|unique', '标题格式错误|标题已存在', FV_MUST, AC_BOTH],
        ['href', '/^[a-z_]+\/[a-z_]+$/', '链接格式错误', FV_MUST, AC_BOTH],
        ['sign', 'string|unique', '标识格式错误|标识已存在', FV_MUST, AC_BOTH],
        ['sort', 'number', '排序值为正数', FV_MUST, AC_BOTH],
    ];
	// 自动处理
    protected array $auto = [
        ['status', '1', 'string', FV_MUST, AC_INSERT],
    ];

    protected function _before_delete(array $data): void
    {
        $this->db = $this->db->where('status=0 AND is_sys=0');
    }
}