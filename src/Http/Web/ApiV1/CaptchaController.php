<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Web\ApiV1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Random\RandomException;
use Throwable;
use Weiran\Framework\Classes\Resp;
use Weiran\System\Action\Verification;
use Weiran\System\Events\CaptchaSendEvent;
use Weiran\System\Http\OpenApi\BaseResponseBody;
use Weiran\System\Http\Web\ApiV1\Captcha\CaptchaSendRequest;
use Weiran\System\Http\Web\ApiV1\Captcha\CaptchaVerifyCodeResponseBody;
use Weiran\System\Http\Web\ApiV1\Captcha\CaptchaVerifyRequest;
use Weiran\System\Models\PamAccount;

/**
 * 验证码
 */
class CaptchaController extends JwtApiController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    #[OA\Post(
        path: '/api/web/system/v1/captcha/send',
        summary: '发送验证码',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['passport'],
                properties: [
                    new OA\Property(property: 'passport', description: '通行证', type: 'string'),
                    new OA\Property(
                        property: 'type',
                        description: '验证类型',
                        type: 'string',
                        enum: [
                            Verification::CAPTCHA_SEND_TYPE_EXIST,
                            Verification::CAPTCHA_SEND_TYPE_NO_EXIST,
                        ],
                    ),
                ]
            )
        ),
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: '发送成功',
                content: new OA\JsonContent(ref: BaseResponseBody::class)
            ),
        ]
    )]
    public function send(CaptchaSendRequest $request): Response|JsonResponse|RedirectResponse
    {
        $passport = $request->getPassport();
        $type     = $request->getType();

        if ($type) {
            if ($type === Verification::CAPTCHA_SEND_TYPE_EXIST) {
                if (!PamAccount::passportExists($passport)) {
                    return Resp::error('输入的账号不存在, 请检查输入');
                }
            }
            elseif ($type === Verification::CAPTCHA_SEND_TYPE_NO_EXIST) {
                if (PamAccount::passportExists($passport)) {
                    return Resp::error('输入的账号已存在, 请检查输入');
                }
            }
            else {
                return Resp::error('验证类型有误,请检查输入');
            }
        }

        $Verification = new Verification();
        $expired      = (int) sys_setting('weiran-system::pam.captcha_expired') ?: 5;
        $length       = ((int) sys_setting('weiran-system::pam.captcha_length')) ?: 6;

        if (!$Verification->isPassThrottle('send-' . $passport)) {
            return Resp::error($Verification->getError());
        }
        if ($Verification->genCaptcha($passport, $expired, $length)) {
            $captcha = $Verification->getCaptcha();
            try {
                event(new CaptchaSendEvent($passport, $captcha));

                return Resp::success('验证码发送成功' . (!is_production() ? ', 验证码:' . $captcha : ''));
            }
            catch (Throwable $e) {
                return Resp::error($e);
            }
        }
        else {
            return Resp::error($Verification->getError());
        }
    }

    #[OA\Post(
        path: '/api/web/system/v1/captcha/verify_code',
        summary: '获取验证串',
        tags: ['System'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['passport', 'captcha'],
                properties: [
                    new OA\Property(property: 'passport', description: '通行证', type: 'string'),
                    new OA\Property(property: 'captcha', description: '验证码', type: 'string'),
                    new OA\Property(property: 'expire_min', description: '验证串有效期(默认:10 分钟, 最长不超过 60 分钟)', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '生成验证串',
                content: new OA\JsonContent(ref: CaptchaVerifyCodeResponseBody::class)
            ),
        ]
    )]
    public function verifyCode(CaptchaVerifyRequest $request): Response|JsonResponse|RedirectResponse
    {
        $passport   = $request->getPassport();
        $captcha    = $request->getCaptcha();
        $expire_min = $request->getExpireMin();

        $Verification = new Verification();
        if (!$Verification->checkCaptcha($passport, $captcha)) {
            return Resp::error($Verification->getError());
        }
        $onceCode = $Verification->genOnceVerifyCode($expire_min, $passport);

        return Resp::success('生成验证串', [
            'verify_code' => $onceCode,
        ]);
    }
}
