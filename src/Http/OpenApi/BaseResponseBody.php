<?php

declare(strict_types = 1);

namespace Weiran\System\Http\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema()]
abstract class BaseResponseBody
{
    #[OA\Property(
        description: '状态码',
        type: 'integer',
        example: 1
    )]
    public int $code;

    #[OA\Property(
        description: '提示信息',
        type: 'string',
    )]
    public string $message;
}
