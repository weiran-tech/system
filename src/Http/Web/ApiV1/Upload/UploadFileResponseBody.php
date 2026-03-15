<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1\Upload;

use OpenApi\Attributes as OA;
use Weiran\System\Http\OpenApi\BaseResponseBody;

#[OA\Schema(description: '文件上传成功')]
class UploadFileResponseBody extends BaseResponseBody
{
    #[OA\Property(
        description: '上传后的文件地址列表',
        properties: [
            new OA\Property(
                property: 'url',
                description: '文件地址列表',
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
        ],
        type: 'object'
    )]
    public object $data;
}
