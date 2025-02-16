<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Weiran\System\Models\PamToken;

/**
 * 禁用token
 */
class PamTokenBanEvent
{

    /**
     * @var string
     */
    public $type;

    /**
     * @var \Weiran\System\Models\PamToken
     */
    public $token;


    /**
     * @param PamToken $token
     * @param string   $type [ip|通过IP禁用;device|通过设备禁用;token|通过Token禁用]
     */
    public function __construct(PamToken $token, string $type)
    {
        $this->token = $token;
        $this->type  = $type;
    }
}