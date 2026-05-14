<?php
/*------------------------------------------------------------------
 | Software: XPHP Framework
 | Site: https://xphp.net
 |------------------------------------------------------------------
 | (C)2020-2026 无念<24203741@qq.com>,All Rights Reserved.
 |-----------------------------------------------------------------*/
declare(strict_types=1);

namespace xphp\core;

/**
 * 配置处理类
 */
class Config
{
    use Single;

    protected static array $items = [];
    protected string $cachePath;
    protected array $fileList = [];

    private function __construct(string|array $load = [])
    {
        $this->cachePath = dir_init(RUNTIME_PATH . '/config', 0777);
        if (!empty($load)) {
            $this->load($load);
        }
    }

    public function load(string|array $load = []): void
    {
        if (!empty($load)) {
            $fileList = $this->parseFileList($load);
            $this->fileList = array_merge($this->fileList, $fileList);
        }

        $lastTime = file_last_time($this->fileList);
        $cacheFile = $this->cachePath . '/' . md5(json_encode($this->fileList) . $lastTime) . '.php';

        if (file_exists($cacheFile)) {
            $data = $this->loadCacheFile($cacheFile);
            if (is_array($data)) {
                self::$items = $data;
                return;
            }
        }

        dir_delete($this->cachePath);
        self::$items = $this->loadConfigFiles();
        $this->saveCacheFile($cacheFile, self::$items);
    }

    protected function loadCacheFile(string $file): ?array
    {
        try {
            $data = @include $file;
        } catch (\Throwable) {
            return null;
        }
        return is_array($data) ? $data : null;
    }

    protected function loadConfigFiles(): array
    {
        $config = [];
        foreach ($this->fileList as $file) {
            if (!file_exists($file)) continue;
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                try {
                    $data = @include $file;
                } catch (\Throwable) {
                    continue;
                }
                if (is_array($data)) {
                    arr_key_case($data);
                    $name = basename($file, '.php');
                    $config[$name] = isset($config[$name]) ? array_replace_recursive($config[$name], $data) : $data;
                }
            } elseif ($ext == 'env') {
                $data = @parse_ini_file($file, true, INI_SCANNER_TYPED);
                if (is_array($data)) {
                    arr_key_case($data);
                    $config = array_replace_recursive($config, $data);
                }
            }
        }
        return $config;
    }

    protected function saveCacheFile(string $file, array $data): void
    {
        $content = "<?php\nreturn " . var_export($data, true) . ';';
        $tempFile = $file . '.tmp.' . str_replace('.', '', (string)microtime(true)) . bin2hex(random_bytes(4));
        $written = @file_put_contents($tempFile, $content);
        if ($written === false) {
            @unlink($tempFile);
            return;
        }
        if (!@rename($tempFile, $file)) {
            @unlink($tempFile);
            return;
        }
    }

    private function parseFileList(string|array $load = []): array
    {
        if (is_string($load)) {
            return dir_get_file($load, ['php', 'env']);
        }
        $list = array_map(fn($dir) => dir_get_file($dir, ['php', 'env']), $load);
        return array_reduce($list, 'array_merge', []);
    }

    public function get(string $name = '', mixed $default = '', bool $toArray = false): mixed
    {
        if (empty($name)) {
            return self::$items;
        }
        return arr_get(self::$items, $name, $default, $toArray);
    }

    public function set(string $name, mixed $value = ''): mixed
    {
        return arr_set(self::$items, $name, $value);
    }

    public function has(string $name): bool
    {
        return arr_has(self::$items, $name);
    }
}
