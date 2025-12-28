<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Traits;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Request;
use Weiran\Framework\Classes\Resp;

/**
 * Class Helpers.
 *
 * @see        \Weiran\Framework\Classes\Traits\PjaxTrait
 *
 * @deprecated 5.0
 */
trait PjaxTrait
{
    /**
     * Pjax 请求错误
     *
     * @return Application|ResponseFactory|JsonResponse|RedirectResponse|Response
     */
    public function pjaxError(string $message)
    {
        if (Request::pjax()) {
            return response($message, 416);
        }

        return Resp::web(Resp::PARAM_ERROR, $message);
    }
}
