<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Weiran\System\Action\Sso;
use Weiran\System\Models\PamAccount;

/**
 * 用户颁发token 成功
 */
class LoginTokenPassedEvent
{
    /**
     * @var PamAccount 用户账户
     */
    public PamAccount $pam;

    /**
     * @var string
     */
    public string $token;

    /**
     * 设备ID
     * @var string
     */
    public string $deviceId;

    /**
     * 设备类型
     * @var string
     */
    public string $deviceType;

    public function __construct(PamAccount $pam, string $token, $device_id = '', $device_type = '')
    {
        $this->pam        = $pam;
        $this->token      = $token;
        $this->deviceId   = $device_id;
        $this->deviceType = $device_type;
    }
}