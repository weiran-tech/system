<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1\Auth;

use OpenApi\Attributes as OA;
use Weiran\System\Http\OpenApi\BaseResponseBody;

#[OA\Schema(
    description: '登录成功',
)]
class AuthLoginResponseBody extends BaseResponseBody
{
    #[OA\Property(
        description: '登录成功返回的token信息',
        properties: [
            new OA\Property(property: 'token', description: 'Token', type: 'string'),
            new OA\Property(property: 'type', description: '类型', type: 'string'),
            new OA\Property(property: 'is_register', description: '是否是注册', type: 'string'),
        ],
        type: 'object'
    )]
    public object $data;
}
