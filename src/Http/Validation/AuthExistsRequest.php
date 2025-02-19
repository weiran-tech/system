<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Validation;

use OpenApi\Attributes as OA;
use Weiran\Framework\Application\Request;
use Weiran\Framework\Validation\Rule;

#[OA\Schema(
    schema: 'SystemAuthExistsRequest',
    required: ['passport'],
    properties: [
        new OA\Property(
            property: 'passport',
            description: '通行证',
            type: 'string',
        ),
    ]
)]
class AuthExistsRequest extends Request
{

    public function getPassport(): string
    {
        return (string) $this->get('passport');
    }

    public function attributes(): array
    {
        return [
            'passport' => '通行证',
        ];
    }

    public function rules(): array
    {
        return [
            'passport' => [
                Rule::required(),
            ],
        ];
    }
}
