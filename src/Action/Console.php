<?php

declare(strict_types = 1);

namespace Weiran\System\Action;

use Illuminate\Support\Str;
use JsonException;
use Weiran\Extension\App\Classes\AppClient;
use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\Framework\Helper\UtilHelper;

/**
 * 对接 Console 中台
 */
class Console
{
    use AppTrait;

    /**
     * 应用控制台客户端
     * @var AppClient
     */
    private AppClient $client;

    /**
     * 控制台 URL
     * @var string
     */
    private string $host;

    /**
     * 汇报之后的地址
     * @var string
     */
    private string $cpUrl;

    /**
     * 应用 ID
     * @var string
     */
    private string $appid;

    /**
     */
    public function __construct()
    {
        $this->appid = (string) env('CP_APPID');
        $secret      = (string) env('CP_SECRET');
        $this->host  = (string) env('CP_URL');
        $this->client = (new AppClient())->setAppid($this->appid)->setSecret($secret);
    }

    /**
     * 获取本地的文件上传
     * @param $type
     * @return bool
     */
    public function apidocCapture($type): bool
    {
        if (!$this->checkAppId()) {
            return false;
        }
        $cwUrl = $this->host . '/api_v1/op/app/apidoc/capture';
        $file  = public_path('docs/' . $type . '/assets/main.bundle.js');
        if (!app('files')->exists($file)) {
            return $this->setError('文件不存在');
        }

        $config = file_get_contents(basename('composer.json'));
        if (UtilHelper::isJson($config)) {
            try {
                $compDefs = json_decode($config, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                return $this->setError('错误的 composer.json 格式');
            }
        }
        $version = $compDefs['version'] ?? 'latest';

        $resp = $this->client->file($cwUrl, [
            'name'    => env('APP_NAME'),
            'version' => $type . '-' . $version,
        ], $file);

        $status  = data_get($resp, 'status');
        $message = data_get($resp, 'message');
        if ($status !== 0) {
            return $this->setError($message);

        }
        return true;
    }


    /**
     * 生成密钥并汇报
     * @return bool
     */
    public function generateSecret(): bool
    {
        $name   = (string) env('APP_NAME');
        $env    = (string) env('APP_ENV');
        $url    = $this->host . '/api_v1/op/app/project/save-secret';
        $secret = md5(microtime(true) . Str::random());
        app('poppy.system.setting')->set('weiran-system::_.secret', $secret);

        if (!$this->checkAppId()) {
            return false;
        }

        $resp = $this->client->post($url, [
            'name'  => $name,
            'env'   => $env,
            'value' => $secret,
        ]);

        $status  = data_get($resp, 'status');
        $message = data_get($resp, 'message');
        if ($status === 0) {
            return true;
        }
        return $this->setError('已生成, 上报失败:' . $message);
    }

    /**
     * 当前的密钥
     * @return mixed
     */
    public function secret()
    {
        return sys_setting('weiran-system::_.secret');
    }

    /**
     * @return string
     */
    public function getCpUrl(): string
    {
        return $this->cpUrl;
    }

    private function checkAppId(): bool
    {
        if (!$this->appid) {
            return $this->setError('当前未设置中台应用, 不进行上报');
        }
        return true;
    }
}