<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Weiran\System\Models\PamAccount;

/**
 * 用户禁用
 */
class PamDisableEvent
{
    public PamAccount $pam;

    /**
     * @var null|PamAccount 操作账号
     */
    public ?PamAccount $editor;

    /**
     * @var string 禁用原因
     */
    public string $reason;

    /**
     * PamDisableEvent constructor.
     */
    public function __construct(PamAccount $pam, ?PamAccount $editor, string $reason = '')
    {
        $this->pam    = $pam;
        $this->editor = $editor;
        $this->reason = $reason;
    }
}
