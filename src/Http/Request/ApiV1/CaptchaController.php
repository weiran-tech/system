<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\ApiV1;

use Weiran\Framework\Classes\Resp;
use Weiran\System\Action\Verification;
use Weiran\System\Events\CaptchaSendEvent;
use Weiran\System\Http\Validation\CaptchaSendRequest;
use Weiran\System\Models\PamAccount;
use Throwable;

/**
 * 验证码
 */
class CaptchaController extends JwtApiController
{

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


    /**
     * @api                   {post} /api_v1/system/captcha/verify_code [Sys]获取验证串
     * @apiDescription        用以保存 passport 验证的验证串, 隐藏字串为 passport
     * @apiVersion            1.0.0
     * @apiName               SysCaptchaVerifyCode
     * @apiGroup              Poppy
     * @apiQuery {string}     passport           通行证
     * @apiQuery {string}     captcha            验证码
     * @apiQuery {string}     [expire_min]       验证串有效期(默认:10 分钟, 最长不超过 60 分钟)
     */
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