<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Weiran\System\Models\PamToken;

class PamSsoLogoutEvent
{
    /**
     * 用户 ID
     * @var int
     */
    public int $accountId;

    /**
     * 用户登录的 Token
     * @var PamToken
     */
    public PamToken $token;


    public function __construct(int $accountId, $token)
    {
        $this->accountId = $accountId;
        $this->token     = $token;
    }

}