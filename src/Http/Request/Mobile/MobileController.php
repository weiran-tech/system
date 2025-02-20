<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\Mobile;

use Auth;
use Weiran\Framework\Application\Controller;
use Weiran\System\Models\PamAccount;

/**
 * H5 控制器
 */
abstract class MobileController extends Controller
{

    /**
     * @var PamAccount 用户账户
     */
    protected $pam;

    public function __construct()
    {
        parent::__construct();
        weiran_container()->setExecutionContext('mobile');
        $this->middleware(function ($request, $next) {
            $webCheck = app('auth')->guard(PamAccount::GUARD_WEB)->check();
            if ($webCheck) {
                app('auth')->shouldUse(PamAccount::GUARD_WEB);
            }
            $jwtCheck = app('auth')->guard(PamAccount::GUARD_JWT_WEB)->check();
            if ($jwtCheck) {
                /** @var PamAccount $jwtPam */
                $jwtPam = app('auth')->guard(PamAccount::GUARD_JWT_WEB)->user();
                app('auth')->guard(PamAccount::GUARD_WEB)->loginUsingId($jwtPam->id, true);
                app('auth')->shouldUse(PamAccount::GUARD_WEB);
            }
            $this->pam = Auth::user();
            return $next($request);
        });
    }
}