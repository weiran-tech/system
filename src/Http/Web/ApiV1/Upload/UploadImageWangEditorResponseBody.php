<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1\Upload;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'wang-editor 图片上传返回')]
class UploadImageWangEditorResponseBody
{
    #[OA\Property(description: '0 成功, 1 失败', type: 'integer')]
    public int $errno;

    #[OA\Property(
        description: '成功时返回的图片列表',
        type: 'array',
        items: new OA\Items(
            type: 'object',
            properties: [
                new OA\Property(property: 'url', description: '图片地址', type: 'string'),
                new OA\Property(property: 'alt', description: '图片替代文本', type: 'string'),
                new OA\Property(property: 'href', description: '图片链接', type: 'string'),
            ]
        )
    )]
    public array $data;

    #[OA\Property(description: '失败时返回的错误消息', type: 'string')]
    public string $message;
}
