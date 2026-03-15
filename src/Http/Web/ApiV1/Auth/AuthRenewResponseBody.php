<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1\Auth;

use OpenApi\Attributes as OA;
use Weiran\System\Http\OpenApi\BaseResponseBody;

#[OA\Schema(description: '续期成功')]
class AuthRenewResponseBody extends BaseResponseBody
{
    #[OA\Property(
        description: '续期返回的新 token 信息',
        properties: [
            new OA\Property(property: 'token', description: '新 Token', type: 'string'),
            new OA\Property(property: 'type', description: '用户类型', type: 'string'),
        ],
        type: 'object'
    )]
    public object $data;
}
