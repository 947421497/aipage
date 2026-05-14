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
        $this->db = $this->db->where([['status', '=', 0], ['is_sys', '=', 0]]);
    }

    public static function getTree(bool $onlyActive = false): array
    {
        $query = db('menu');
        if ($onlyActive) {
            $query = $query->where('status=1');
        }
        $list = $query->order('sort ASC,id ASC')->select();
        return self::buildTree($list, 0);
    }

    private static function buildTree(array $list, int $parentId = 0, int $depth = 1): array
    {
        $tree = [];
        foreach ($list as $item) {
            if ((int)$item['parent_id'] === $parentId) {
                $item['level'] = $depth;
                $item['url'] = !empty($item['href']) ? url($item['href']) : 'javascript:void(0)';
                $children = self::buildTree($list, (int)$item['id'], $depth + 1);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    public static function renderTableRows(array $list): string
    {
        $html = '';
        foreach ($list as $item) {
            $statusClass = (int)$item['status'] === 0 ? 'table-secondary opacity-60' : '';
            $editUrl = url('edit', ['id' => $item['id']]);
            $delUrl = url('del', ['ids' => $item['id']]);
            $stateOnUrl = url('state?params=status-1', ['ids' => $item['id']]);
            $stateOffUrl = url('state?params=status-0', ['ids' => $item['id']]);
            $statusBtn = (int)$item['status'] === 1
                ? '<a href="javascript:ajaxConfirm(\'' . $stateOffUrl . '\',\'停用\',true);" class="btn btn-sm btn-success">已启用</a>'
                : '<a href="javascript:ajaxConfirm(\'' . $stateOnUrl . '\',\'启用\',true);" class="btn btn-sm btn-secondary">已停用</a>';
            $arrow = (int)$item['level'] > 1 ? '<i class="mdi mdi-subdirectory-arrow-right" style="margin-right:5px"></i>' : '';

            $html .= '<tr class="' . $statusClass . '">';
            $html .= '<td><div class="form-check"><input class="form-check-input ids" type="checkbox" name="ids[]" value="' . htmlspecialchars((string)$item['id']) . '" /></div></td>';
            $html .= '<td>' . htmlspecialchars((string)$item['id']) . '</td>';
            $html .= '<td style="padding-left:' . ((int)$item['level'] * 20) . 'px">' . $arrow . '<a href="javascript:openModal(\'' . $editUrl . '\',\'编辑菜单\')">' . htmlspecialchars($item['title']) . '</a></td>';
            $html .= '<td>' . htmlspecialchars($item['href']) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['sign']) . '</td>';
            $html .= '<td><i class="' . htmlspecialchars($item['icon']) . '"></i></td>';
            $html .= '<td>' . htmlspecialchars((string)$item['sort']) . '</td>';
            $html .= '<td>' . $statusBtn . '</td>';
            $html .= '<td><div class="btn-group btn-group-sm">';
            $html .= '<a href="javascript:openModal(\'' . $editUrl . '\',\'编辑菜单\')" class="btn btn-primary">编辑</a>';
            $html .= '<a href="javascript:ajaxConfirm(\'' . $delUrl . '\',\'删除\',true);" class="btn btn-danger">删除</a>';
            $html .= '</div></td>';
            $html .= '</tr>';

            if (isset($item['children']) && !empty($item['children'])) {
                $html .= self::renderTableRows($item['children']);
            }
        }
        return $html;
    }

    public static function getChildIds(array $parentIds): array
    {
        $result = [];
        $visited = [];
        $stack = $parentIds;
        while (!empty($stack)) {
            $newStack = [];
            foreach ($stack as $id) {
                if (isset($visited[$id])) {
                    continue;
                }
                $visited[$id] = true;
            }
            $children = db('menu')->where([['parent_id', 'in', $stack]])->column('id');
            if (empty($children)) {
                break;
            }
            $children = array_filter($children, fn($id) => !isset($visited[$id]));
            if (empty($children)) {
                break;
            }
            $result = array_merge($result, $children);
            $stack = $children;
        }
        return $result;
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
                if ($depth >= self::$maxDepth) continue;
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
