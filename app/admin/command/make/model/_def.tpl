<?php
declare(strict_types=1);
namespace {{$namespace}}\model;
use aphp\core\Model;
class {{$class}} extends Model
{
	protected string $table = '{{$table_name}}';
	protected string $pk = '{{$pk|default='pk'}}';
	// 自动验证
	protected array $validate = [
        ['title', 'required|unique', '标题必须|标题已存在', FV_MUST, AC_BOTH],
    ];
	// 自动处理
    protected array $auto = [
        ['status', '1', 'string', FV_MUST, AC_INSERT],
    ];
}