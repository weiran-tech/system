<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Contracts;

use Illuminate\Http\Request;

interface ApiSignContract
{
    /**
     * 获取Sign
     *
     * @param array  $params 参数
     * @param string $type   签名类型
     */
    public function sign(array $params, string $type = 'user'): string;

    /**
     * 检测签名
     */
    public function check(Request $request): bool;

    /**
     * 返回时间戳
     */
    public static function timestamp(): int;
}
