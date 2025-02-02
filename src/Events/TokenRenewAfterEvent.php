<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Weiran\System\Models\PamToken;

/**
 * pamToken续期后续处理
 */
class TokenRenewAfterEvent
{
    /**
     * @var PamToken
     */
    public PamToken $pamToken;

    /**
     * @var string
     */
    public string $oldTokenHash;


    public function __construct(PamToken $pamToken, string $old_token_hash)
    {
        $this->pamToken     = $pamToken;
        $this->oldTokenHash = $old_token_hash;
    }
}