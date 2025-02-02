<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Auth\Password;

use Carbon\Carbon;
use Weiran\System\Classes\Contracts\PasswordContract;
use Weiran\System\Models\PamAccount;

/**
 * 用户认证
 */
class DefaultPasswordProvider implements PasswordContract
{
    /**
     * @inheritDoc
     */
    public function check(PamAccount $pam, string $password, $type = 'plain'): bool
    {
        if ($pam->created_at instanceof Carbon) {
            $datetime = $pam->created_at->toDateTimeString();
        }
        else {
            $datetime = $pam->created_at;
        }
        return $this->genPassword($password, $datetime, $pam->password_key) === $pam->password;
    }

    /**
     * @inheritDoc
     */
    public function genPassword(string $password, string $reg_datetime, string $password_key): string
    {
        return md5(sha1($password . $reg_datetime) . $password_key);
    }
}