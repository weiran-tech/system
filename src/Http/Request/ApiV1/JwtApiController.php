<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\ApiV1;

use Illuminate\Contracts\Auth\Authenticatable;
use Weiran\Framework\Application\ApiController;
use Weiran\System\Models\PamAccount;

/**
 * Jwt api 控制器[支持多个用户类型]
 */
abstract class JwtApiController extends ApiController
{

    /**
     * @var null|PamAccount
     */
    protected ?PamAccount $pam = null;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            $this->pam = $request->user();
            return $next($request);
        });
    }

    /**
     * 返回 Jwt 用户
     * @return Authenticatable|PamAccount
     * @see        $pam
     */
    protected function pam()
    {
        if ($this->pam) {
            return $this->pam;
        }
        $this->pam = app('request')->user(PamAccount::GUARD_JWT);
        if (!$this->pam) {
            $this->pam = app('auth')->guard(PamAccount::GUARD_JWT)->user();
        }

        return $this->pam;
    }
}