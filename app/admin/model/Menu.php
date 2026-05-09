<?php
declare(strict_types=1);
namespace app\admin\model;
use xphp\core\Model;

class Menu extends Model
{
    protected string $table = 'menu';
    protected string $pk = 'id';

    protected array $validate = [
        ['title', 'chs_alpha_num|length:1,50', '标题格式错误|标题长度需在1-50字符', FV_MUST, AC_BOTH],
        ['sign', 'string|length:1,20|unique', '标识格式错误|标识长度需在1-20字符|标识已存在', FV_MUST, AC_BOTH],
        ['sort', 'number', '排序值为正数', FV_MUST, AC_BOTH],
        ['pid', 'number', '父级ID格式错误', FV_MUST, AC_BOTH],
    ];

    protected array $auto = [
        ['status', '1', 'string', FV_MUST, AC_INSERT],
    ];

    protected function _before_insert(array $data): array
    {
        $this->validateHref($data);
        $this->validateDepth($data);
        return $data;
    }

    protected function _before_update(array $data): array
    {
        $this->validateHref($data);
        $this->validateDepth($data);
        $this->validateCircular($data);
        return $data;
    }

    protected function validateHref(array $data): void
    {
        if (!empty($data['pid']) && empty($data['href'])) {
            halt('子级菜单必须填写链接地址');
        }
    }

    protected function validateDepth(array $data): void
    {
        if (!empty($data['pid'])) {
            $parent = $this->find($data['pid']);
            if ($parent) {
                $depth = $this->getDepth($parent);
                if ($depth >= 3) {
                    halt('最多只能创建三级菜单');
                }
            }
        }
    }

    protected function getDepth(array $menu): int
    {
        $depth = 1;
        $pid = $menu['pid'] ?? 0;
        while ($pid > 0 && $depth < 100) {
            $parent = $this->find($pid);
            if ($parent && isset($parent['pid'])) {
                $pid = $parent['pid'];
                $depth++;
            } else {
                break;
            }
        }
        return $depth;
    }

    protected function validateCircular(array $data): void
    {
        if (!isset($data['id']) || !isset($data['pid'])) {
            return;
        }

        $checkId = $data['pid'];
        $targetId = $data['id'];
        $visited = [$targetId];

        while ($checkId > 0) {
            if (in_array($checkId, $visited)) {
                halt('检测到循环引用，禁止操作');
            }
            $visited[] = $checkId;
            $parent = $this->find($checkId);
            if ($parent) {
                $checkId = $parent['pid'] ?? 0;
            } else {
                break;
            }
        }
    }

    protected function _before_delete(array $data): void
    {
        $hasChildren = db('menu')->where('pid', $data['id'])->count();
        if ($hasChildren > 0) {
            halt('存在子菜单，请先删除子菜单');
        }
        $this->db = $this->db->where('status=0 AND is_sys=0');
    }
}