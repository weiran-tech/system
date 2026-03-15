<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1\Upload;

use OpenApi\Attributes as OA;
use Weiran\System\Http\OpenApi\BaseResponseBody;

#[OA\Schema(description: '图片上传成功')]
class UploadImageResponseBody extends BaseResponseBody
{
    #[OA\Property(
        description: '上传后的图片地址列表',
        properties: [
            new OA\Property(
                property: 'url',
                description: '图片地址列表',
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
        ],
        type: 'object'
    )]
    public object $data;
}
