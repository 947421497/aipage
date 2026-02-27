<?php
declare(strict_types=1);
namespace app\admin\model;
use xphp\core\Model;
class Config extends Model
{
	protected string $table = 'config';
	protected string $pk = 'id';
    protected array $validate = [
        ['name', 'required', '名称必须', FV_MUST, AC_BOTH],
        ['config_key', 'string', '键名必须是英文', FV_MUST, AC_BOTH],
        ['config_value', 'required', '配置值必须', FV_MUST, AC_BOTH],
    ];
    protected array $auto = [
        ['status', '1', 'string', FV_MUST, AC_INSERT]
    ];
}