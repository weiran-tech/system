<?php

declare(strict_types = 1);

namespace Weiran\System\Models\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\SysConfig;

/**
 * @mixin PamAccount
 */
class PamResource extends JsonResource
{

    /**
     * @inheritDoc
     */
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'username'       => $this->username,
            'mobile'         => $this->mobile,
            'email'          => $this->email,
            'type'           => $this->type,
            'is_enable'      => $this->is_enable === SysConfig::YES ? 'Y' : 'N',
            'disable_reason' => $this->disable_reason,
            'created_at'     => $this->created_at->toDatetimeString(),
        ];
    }
}