<?php

declare(strict_types = 1);

namespace Weiran\System\Action;

use Illuminate\Support\Str;
use Weiran\Extension\App\Classes\AppClient;
use Weiran\Framework\Classes\Traits\AppTrait;

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
        $this->appid  = (string) env('CP_APPID');
        $secret       = (string) env('CP_SECRET');
        $this->host   = (string) env('CP_URL');
        $this->client = (new AppClient())->setAppid($this->appid)->setSecret($secret);
    }


    /**
     * 生成密钥并汇报
     * @return bool
     */
    public function generateSecret(): bool
    {
        $name   = (string) env('APP_NAME');
        $env    = (string) env('APP_ENV');
        $url    = $this->host . '/api/web/op/v1/app/project/save-secret';
        $secret = md5(microtime(true) . Str::random());
        app('weiran.system.setting')->set('weiran-system::_.secret', $secret);

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