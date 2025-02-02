<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Illuminate\Support\Collection;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamToken;

/**
 * 用户单点登录事件触发
 */
class PamSsoEvent
{
    /**
     * @var PamAccount
     */
    public PamAccount $pam;

    /**
     * @var Collection 单点登录被踢下去的用户的数据
     */
    public Collection $tokens;

    /**
     * PamDisableEvent constructor.
     * @param PamAccount            $pam
     * @param Collection|PamToken[] $tokens
     */
    public function __construct(PamAccount $pam, $tokens)
    {
        $this->pam    = $pam;
        $this->tokens = $tokens;
    }

}