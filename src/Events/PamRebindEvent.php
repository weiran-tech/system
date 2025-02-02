<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Weiran\System\Models\PamAccount;

/**
 * 用户绑定
 */
class PamRebindEvent
{
    /**
     * @var PamAccount
     */
    public $pam;

    /**
     * @param PamAccount $pam
     */
    public function __construct(PamAccount $pam)
    {
        $this->pam = $pam;
    }
}