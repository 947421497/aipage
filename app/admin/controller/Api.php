<?php
declare(strict_types=1);
namespace app\admin\controller;
use xphp\core\Jump;

class Api
{
    use Jump;

    protected array $middleware = [
        'cp_auth' => ['except' => ['clear']], // 无需认证
    ];

    // 清除缓存
    public function clear()
    {
        cache_clear();
        cli('clear:runtime admin index common');
        $this->success('清除缓存成功', '[history]');
    }

    // 上传接口
    public function upload(string $api = 'image')
    {
        $upload = extend('upload', [$api]);
        $res = $upload->save();
        if (!isset($res[0]['path'])) {
            $this->error($upload->getError());
        }
        $this->_json(200, '上传成功', $res[0]);
    }

    // base64上传
    public function upload_base64()
    {
        $api = input('post.api', 'image', 'clear_html'); // 上传类型
        $data = input('post.base64'); // base64数据
        $upload = extend('upload', [$api]);
        $res = $upload->saveBase64Image($data);
        if (!isset($res['path'])) {
            $this->error($upload->getError());
        }
        $this->_json(200, '上传成功', ['path' => $res['path']]);
    }

    // 图片删除
    public function image_del()
    {
        $pic = input('post.pic', '', 'clear_html');
        $filename = trim($pic, '.');
        $file = ROOT_PATH . '/public' . $filename;
        if (file_exists($file)) {
            unlink($file);
        }
        $this->success('删除成功');
    }
}