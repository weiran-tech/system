<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Weiran\System\Models\PamAccount;

class PamLogoutEvent
{
    /**
     * 用户
     * @var PamAccount
     */
    public PamAccount $pam;

    /**
     * @param PamAccount $pam
     */
    public function __construct(PamAccount $pam)
    {
        $this->pam = $pam;
    }
}