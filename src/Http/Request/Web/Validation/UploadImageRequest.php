<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\Web\Validation;

use OpenApi\Attributes as OA;
use Weiran\Framework\Application\Request;
use Weiran\Framework\Validation\Rule;

#[OA\Schema(
    schema: 'SystemUploadImageRequest',
    required: ['image', 'type'],
    properties: [
        new OA\Property(
            property: 'image',
            description: '图片',
            type: 'string',
            format: 'binary'
        ),
        new OA\Property(
            property: 'type',
            description: '上传图片的类型',
            type: 'string',
            default: 'form',
            enum: ['form', 'base64']
        ),
        new OA\Property(
            property: 'folder',
            description: '图片存储类型默认文件夹',
            type: 'string',
            default: 'default'
        ),
        new OA\Property(
            property: 'from',
            description: '上传来源, 如果是 wang-editor, 则返回支持 wang-editor 编辑器的格式',
            type: 'string',
            default: '',
        ),
        new OA\Property(
            property: 'watermark',
            description: '是否开启水印, Y 设置为开启',
            type: 'string',
            default: '',

        )
    ],
)]
class UploadImageRequest extends Request
{

    public function getType(): string
    {
        return (string) $this->input('type', 'form');
    }

    public function getFolder(): string
    {
        return ((string) $this->input('folder')) ?: 'default';
    }

    public function getFrom(): string
    {
        return (string) $this->input('from', '');
    }

    public function getWatermark(): string
    {
        return (string) $this->input('watermark', '');
    }

    public function getImage(): mixed
    {
        return $this->input('image') ?: $this->file('image');
    }

    public function attributes(): array
    {
        return [
            'type'      => '类型',
            'image'     => '图片',
            'from'      => '图片来源',
            'folder'    => '存储文件夹',
            'watermark' => '是否开启水印',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'image'     => [
                Rule::required(),
            ],
            'type'      => [
                Rule::required(),
                Rule::in(['form', 'base64'])
            ],
            'folder'    => [
                Rule::string(),
                Rule::regex('/^([a-z-]+)$/i')
            ],
            'watermark' => [
                Rule::string(),
            ],
            'from'      => [
                Rule::string(),
                Rule::regex('/^([a-z-]+)$/i')
            ],
        ];
    }
}
