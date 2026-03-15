<?php

declare(strict_types=1);

namespace Weiran\System\Http\Web\ApiV1\Upload;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'base64 图片上传请求')]
class UploadImageBase64Request
{
    #[OA\Property(description: '上传类型, 固定为 base64', type: 'string', default: 'base64', enum: ['base64'])]
    public string $type;

    #[OA\Property(
        description: 'base64 图片内容, 支持单个字符串或字符串数组',
        oneOf: [
            new OA\Schema(type: 'string'),
            new OA\Schema(type: 'array', items: new OA\Items(type: 'string')),
        ]
    )]
    public string|array $image;

    #[OA\Property(description: '图片存储文件夹', type: 'string', default: 'default')]
    public string $folder;

    #[OA\Property(description: '上传来源, wang-editor 时返回编辑器结构', type: 'string', default: '')]
    public string $from;

    #[OA\Property(description: '是否开启水印, Y 为开启', type: 'string', default: '')]
    public string $watermark;
}
