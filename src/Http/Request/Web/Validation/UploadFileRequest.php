<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\Web\Validation;

use Illuminate\Http\UploadedFile;
use OpenApi\Attributes as OA;
use Weiran\Framework\Application\Request;
use Weiran\Framework\Validation\Rule;

#[OA\Schema(
    schema: 'SystemUploadFileRequest',
    required: ['file', 'type'],
    properties: [
        new OA\Property(
            property: 'file',
            description: '文件',
            type: 'string',
            format: 'binary'
        ),
        new OA\Property(
            property: 'type',
            description: '上传文件的类型',
            type: 'string',
            default: 'audio',
            enum: ['audio', 'video', 'images', 'file']
        ),
        new OA\Property(
            property: 'folder',
            description: '图片存储类型默认文件夹',
            type: 'string',
            default: 'default'
        ),
    ],
)]
class UploadFileRequest extends Request
{

    public function getType(): string
    {
        return (string) $this->input('type', 'audio');
    }

    public function getFolder(): string
    {
        return (string) $this->input('folder', 'default');
    }


    public function getFile(): array|UploadedFile|null
    {
        return $this->file('file');
    }

    public function attributes(): array
    {
        return [
            'type'   => '类型',
            'file'   => '图片',
            'folder' => '存储文件夹',
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
            'file'   => [
                Rule::required(),
                Rule::file()
            ],
            'type'   => [
                Rule::required(),
                Rule::in(['audio', 'video', 'file'])
            ],
            'folder' => [
                Rule::string(),
                Rule::regex('/^([a-z-]+)$/i')
            ],
        ];
    }
}
