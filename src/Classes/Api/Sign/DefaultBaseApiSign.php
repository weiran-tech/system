<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Api\Sign;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Log;
use Weiran\Framework\Classes\Resp;
use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\Framework\Helper\EnvHelper;
use Weiran\System\Classes\Contracts\ApiSignContract;
use Weiran\System\Models\PamAccount;

/**
 * 默认的 Timestamp 约定
 */
abstract class DefaultBaseApiSign implements ApiSignContract
{
    use AppTrait;

    /**
     * 默认时间戳
     * @return int
     */
    public static function timestamp(): int
    {
        return (new DateTime())->getTimestamp();
    }

    public function check(Request $request): bool
    {
        // 加密 debug, 不验证签名
        $secret = (string) config('weiran.system.secret');
        if ($secret && (string) $request->input('_weiran_secret') === $secret) {
            return true;
        }

        // check token
        $timestamp = $request->input('timestamp');
        if (!$timestamp) {
            return $this->setError(new Resp(Resp::PARAM_ERROR, '未传递时间戳'));
        }

        // check token
        $sign = $request->input('sign');
        if (!$sign) {
            return $this->setError(new Resp(Resp::PARAM_ERROR, '未进行签名'));
        }

        $type = $request->header('x-type') ?: PamAccount::TYPE_USER;

        // check sign
        if ($sign !== $this->sign($request->all(), $type)) {
            Log::error('sign-error', [
                'params'  => $request->all(),
                'ip'      => EnvHelper::ip(),
                'uri'     => $request->getPathInfo(),
                'headers' => [
                    'os'    => x_header('os'),
                    'ver'   => x_header('ver'),
                    'token' => jwt_token(),
                ],
            ]);
            return $this->setError(new Resp(Resp::SIGN_ERROR, '签名错误'));
        }
        return true;
    }

    protected function except($params): array
    {
        $excepts = [];
        foreach ($params as $key => $param) {
            if (!Str::startsWith($key, '_')) {
                $excepts[$key] = $param;
            }
        }
        return Arr::except($excepts, [
            'sign', 'image', 'file', 'token',
        ]);
    }
}