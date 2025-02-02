<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Traits;

use Auth;
use Weiran\System\Models\PamAccount;
use View;

/**
 * Class Helpers.
 */
trait BackendTrait
{
    /**
     * 后台共享
     */
    public function backendShare()
    {
        View::share([
            '_pam' => Auth::guard(PamAccount::GUARD_BACKEND)->user(),
        ]);
    }
}