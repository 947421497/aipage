<?php
declare(strict_types=1);
function nav_active(string $nav, string $class = ' class="active"'): string
{
    if (str_starts_with($nav, '@')) {
        $nav = substr($nav, 1);
        return ($nav == get_action()) ? $class : '';
    }
    return ($nav == get_controller()) ? $class : '';
}

function nav_active_tree(array $nav, string $class = ' active'): string
{
    $controller = get_controller();
    if ($nav['sign'] == $controller) return $class;
    if (isset($nav['children'])) {
        foreach ($nav['children'] as $child) {
            if (nav_active_tree($child, $class) !== '') return $class;
        }
    }
    return '';
}

function get_avatar(string $avatar = '', string $qq = ''): string
{
    if (!empty($avatar)) {
        return $avatar;
    }
    if (!empty($qq)) {
        return 'http://q1.qlogo.cn/g?b=qq&nk='.$qq.'&s=100&t='.time();
    }
    return __STATIC__.'/images/avatar.jpg';
}

function get_time_ago(int $time): string
{
    $etime = time() - $time;
    if ($etime < 1) {
        return '刚刚';
    }
    $interval = [31536000 => '年前', 2592000 => '个月前', 604800 => '星期前', 86400 => '天前', 3600 => '小时前', 60 => '分钟前', 1 => '秒前'];
    foreach ($interval as $k => $v) {
        $ok = floor($etime / $k);
        if ($ok != 0) {
            return $ok . $v;
        }
    }
    return '刚刚';
}

function form_select(string $name, $options, $selected = '', string $attr = ''): string
{
    if (!is_array($options)) {
        $options = str_to_array($options);
    }
    if (!empty($attr)) {
        $attr = ' ' . clear_html($attr);
    }
    $selected = is_array($selected) ? $selected : explode(',', strval($selected));
    $select = '<select name="' . $name . '"' . $attr . '>' . "\n";
    foreach ($options as $k => $v) {
        $select .= "\t<option value=\"{$k}\"" . (in_array($k, $selected) ? ' selected="selected"' : '') . ">{$v}</option>\n";
    }
    return $select . "</select>\n";
}

function form_radio(string $name, $options, $selected = '', string $attr = '', bool $is_label = true): string
{
    if (!is_array($options)) {
        $options = str_to_array($options);
    }
    if (!empty($attr)) {
        $attr = ' ' . clear_html($attr);
    }
    $radio = '';
    foreach ($options as $k => $v) {
        if ($is_label) {
            $radio .= '<label' . $attr . '><input type="radio" name="' . $name . '" value="' . $k . '"' . ($k == $selected ? ' checked="checked"' : '') . ' /> ' . $v . '</label>' . "\n";
        } else {
            $radio .= '<input type="radio" name="' . $name . '" value="' . $k . '" title="' . $v . '"' . ($k == $selected ? ' checked="checked"' : '') . ' />';
        }
    }
    return $radio;
}

function pic_attr(string $url = ''): array
{
    $data = [
        'img' => ' style="display:none"',
        'span' => '',
    ];
    if (!empty($url)) {
        $data['img'] = ' src="'.$url.'"';
        $data['span'] = ' style="display:inline"';
    }
    return $data;
}
