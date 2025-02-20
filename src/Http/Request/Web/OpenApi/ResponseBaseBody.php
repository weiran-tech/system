<?php

namespace Weiran\System\Http\Request\Web\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ResponseBaseBody'
)]
abstract class ResponseBaseBody
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