<?php
declare(strict_types=1);
namespace app\admin\model;
use xphp\core\Model;

class Menu extends Model
{
    protected string $table = 'menu';
    protected string $pk = 'id';
    protected static int $maxDepth = 3;

    public static function setMaxDepth(int $depth): void
    {
        self::$maxDepth = $depth;
    }

    protected array $validate = [
        ['title', 'chs_alpha_num|unique', '标题格式错误|标题已存在', FV_MUST, AC_BOTH],
        ['href', '/^[a-z_]*\/[a-z_]*$/', '链接格式错误', FV_MUST, AC_BOTH],
        ['sign', 'string|unique', '标识格式错误|标识已存在', FV_MUST, AC_BOTH],
        ['sort', 'number', '排序值为正数', FV_MUST, AC_BOTH],
        ['parent_id', 'number', '父级ID格式错误', FV_MUST, AC_BOTH],
    ];

    protected array $auto = [
        ['status', '1', 'string', FV_MUST, AC_INSERT],
    ];

    protected function _before_delete(array $data): void
    {
        $this->db = $this->db->where('status=0 AND is_sys=0');
    }

    public static function getTree(): array
    {
        $list = db('menu')->where('status=1')->order('sort ASC,id ASC')->select();
        return self::buildTree($list, 0);
    }

    private static function buildTree(array $list, int $parentId = 0): array
    {
        $tree = [];
        foreach ($list as $item) {
            if ((int)$item['parent_id'] === $parentId) {
                $children = self::buildTree($list, (int)$item['id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $item['level'] = self::calcLevel($list, (int)$item['id']);
                $item['url'] = !empty($item['href']) ? url($item['href']) : 'javascript:void(0)';
                $tree[] = $item;
            }
        }
        return $tree;
    }

    private static function calcLevel(array $list, int $id): int
    {
        $level = 1;
        foreach ($list as $item) {
            if ((int)$item['id'] === $id) {
                if ((int)$item['parent_id'] === 0) {
                    return $level;
                }
                return $level + self::calcLevel($list, (int)$item['parent_id']);
            }
        }
        return $level;
    }

    public static function getParentOptions(int $excludeId = 0): array
    {
        $list = db('menu')->where('status=1')->order('sort ASC,id ASC')->select();
        $options = [0 => '作为一级菜单'];
        $tree = self::buildOptions($list, 0, $excludeId);
        foreach ($tree as $item) {
            $options[$item['id']] = $item['html'] . $item['title'];
        }
        return $options;
    }

    private static function buildOptions(array $list, int $parentId, int $excludeId, int $depth = 0): array
    {
        $result = [];
        foreach ($list as $item) {
            if ((int)$item['parent_id'] === $parentId && (int)$item['id'] !== $excludeId) {
                if ($depth >= 9) continue;
                $prefix = str_repeat('　├ ', $depth);
                $item['html'] = $prefix;
                $result[] = $item;
                $children = self::buildOptions($list, (int)$item['id'], $excludeId, $depth + 1);
                $result = array_merge($result, $children);
            }
        }
        return $result;
    }
}
