<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\Web\ApiV1;

use OpenApi\Attributes as OA;
use Throwable;
use Weiran\Framework\Classes\Resp;
use Weiran\System\Action\Verification;
use Weiran\System\Events\CaptchaSendEvent;
use Weiran\System\Http\Validation\CaptchaSendRequest;
use Weiran\System\Models\PamAccount;

/**
 * 验证码
 */
class CaptchaController extends JwtApiController
{
    #[OA\Get(
        path: '/api/web/v1/system/captcha/send',
        summary: '发送验证码',
        tags: ['Weiran'],
        parameters: [
            new OA\Parameter(
                name: 'passport',
                description: '通行证',
                in: 'query',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                ),
            ),
            new OA\Parameter(
                name: 'type',
                description: '验证类型',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: [
                        Verification::CAPTCHA_SEND_TYPE_EXIST,
                        Verification::CAPTCHA_SEND_TYPE_NO_EXIST,
                    ],
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '发送成功',
                content: new OA\JsonContent(ref: '#/components/schemas/ResponseBaseBody')
            )
        ]
    )]
    public function send(CaptchaSendRequest $request)
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
        $expired      = (int) sys_setting('wr-system::pam.captcha_expired') ?: 5;
        $length       = ((int) sys_setting('wr-system::pam.captcha_length')) ?: 6;

        if (!$Verification->isPassThrottle('send-' . $passport)) {
            return Resp::error($Verification->getError());
        }
        if ($Verification->genCaptcha($passport, $expired, $length)) {
            $captcha = $Verification->getCaptcha();
            try {
                event(new CaptchaSendEvent($passport, $captcha));
                return Resp::success('验证码发送成功' . (!is_production() ? ', 验证码:' . $captcha : ''));
            } catch (Throwable $e) {
                return Resp::error($e);
            }
        }
        else {
            return Resp::error($Verification->getError());
        }
    }


    #[OA\Post(
        path: '/api/web/v1/system/captcha/verify_code',
        summary: '获取验证串',
        tags: ['Weiran'],
        parameters: [
            new OA\Parameter(
                name: 'passport',
                description: '通行证',
                in: 'query',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                ),
            ),
            new OA\Parameter(
                name: 'captcha',
                description: '验证码',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string',),
            ),
            new OA\Parameter(
                name: 'expire_min',
                description: '验证串有效期(默认:10 分钟, 最长不超过 60 分钟)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer',),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '生成验证串',
                content: new OA\JsonContent(ref: '#/components/schemas/ResponseBaseBody')
            )
        ]
    )]
    public function verifyCode()
    {
        $passport   = (string) input('passport');
        $captcha    = (string) input('captcha');
        $expire_min = (int) input('expire_min', 10);
        if ($expire_min > 60) {
            $expire_min = 60;
        }
        if ($expire_min < 1) {
            $expire_min = 1;
        }

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