<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\ApiV1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Weiran\Framework\Auth\ThrottlesLogins;
use Weiran\Framework\Classes\Resp;
use Weiran\Framework\Helper\UtilHelper;
use Weiran\System\Action\Pam;
use Weiran\System\Action\Verification;
use Weiran\System\Events\LoginSuccessEvent;
use Weiran\System\Events\LoginTokenPassedEvent;
use Weiran\System\Events\TokenRenewEvent;
use Weiran\System\Http\Validation\PamLoginRequest;
use Weiran\System\Http\Validation\PamPasswordRequest;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\Resources\PamResource;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * 认证控制器
 */
class AuthController extends JwtApiController
{
    use ThrottlesLogins;

    /**
     * 最大请求次数 10 次
     * @var float|int
     */
    protected float $maxAttempts = 10;

    /**
     * 30 秒内, 最多 10 次请求
     * @var float
     */
    protected float $decayMinutes = 0.5;

    /**
     * @api                   {post} /api_v1/system/auth/access [Sys]检测 Token
     * @apiVersion            1.0.0
     * @apiName               SysAuthAccess
     * @apiGroup              Poppy
     * @apiQuery {integer}    token           Token
     * @apiSuccess {object[]} data            返回
     * @apiSuccess {integer}  id              ID
     * @apiSuccess {string}   username        用户名
     * @apiSuccess {string}   mobile          手机号
     * @apiSuccess {string}   email           邮箱
     * @apiSuccess {string}   type            类型
     * @apiSuccess {string}   is_enable       是否启用(Y|N)
     * @apiSuccess {string}   disable_reason  禁用原因
     * @apiSuccess {string}   created_at      创建时间
     * @apiSuccessExample {json} data:
     * {
     *     "status": 0,
     *     "message": "",
     *     "data": {
     *         "id": 9,
     *         "username": "user001",
     *         "mobile": "",
     *         "email": "",
     *         "type": "user",
     *         "is_enable": "Y",
     *         "disable_reason": "",
     *         "created_at": "2021-03-18 15:30:15",
     *         "updated_at": "2021-03-18 16:38:06"
     *     }
     * }
     */
    public function access(): JsonResponse
    {
        $pam    = (new PamResource($this->pam()))->toArray(app('request'));
        $append = (array) sys_hook('poppy.system.auth_access');
        $all    = array_merge($pam, $append);
        return Resp::success(
            '有效登录',
            $all
        );
    }

    /**
     * @api                   {post} /api_v1/system/auth/login [Sys]登录/注册
     * @apiVersion            1.0.0
     * @apiName               SysAuthLogin
     * @apiGroup              Poppy
     * @apiQuery {string}     passport        通行证
     * @apiQuery {string}     [password]      密码
     * @apiQuery {string}     [captcha]       验证码
     * @apiQuery {string}     [device_id]     设备ID(开启单一登录之后可用)
     * @apiQuery {string}     [device_type]   设备类型(开启单一登录之后可用)
     * @apiQuery {string}     [guard]         登录类型 [web|用户(默认);backend|后台;]
     * @apiSuccess {string}   token           认证成功的Token
     * @apiSuccess {string}   type            账号类型
     * @apiSuccess {string}   is_register     是否是注册 [Y|N]
     * @apiSuccessExample {json} data:
     * {
     *      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.*******",
     *      "type": "backend",
     *      "is_register": "backend",
     * }
     */


    /**
     * @param Request $req
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws Throwable
     */
    public function login(Request $req): JsonResponse
    {
        $req->merge([
            'os' => input('device_type', '') ?: x_header('os'),
        ]);
        /** @var PamLoginRequest $request */
        $request     = app(PamLoginRequest::class, [$req]);
        $reqPassport = $request->scene('passport')->validated();

        // 频率限制
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->sendLockoutResponse($request);
        }
        $this->incrementLoginAttempts($request);

        // 类型拦截
        if (!$request->input('captcha') && !$request->input('password')) {
            return Resp::error('登录密码或者验证码必须填写');
        }

        // 登录类型
        $guard = (input('guard') ?: x_header('type')) === PamAccount::TYPE_BACKEND
            ? PamAccount::GUARD_JWT_BACKEND
            : PamAccount::GUARD_JWT_WEB;


        $Pam = new Pam();
        if ($request->input('captcha')) {
            $reqCaptcha = $request->scene('captcha')->validated();
            if (!$Pam->captchaLogin($reqCaptcha['passport'], $reqCaptcha['captcha'], $guard)) {
                return Resp::error($Pam->getError());
            }
        }
        else {
            // use password
            $reqPwd   = $request->scene('password')->validated();
            $passport = PamAccount::fullFilledPassport($reqPwd['passport']);
            if (!$Pam->loginCheck($passport, $reqPwd['password'], $guard)) {
                return Resp::error($Pam->getError());
            }
        }

        $this->clearLoginAttempts($request);

        $pam   = $Pam->getPam();
        $token = JWTAuth::fromUser($pam);

        /* 设备单一性登陆验证(基于 Redis + Db)
         * ---------------------------------------- */
        try {
            $deviceId = x_header('id') ?: input('device_id', '');
            event(new LoginTokenPassedEvent($pam, $token, $deviceId, $reqPassport['os']));
        } catch (Throwable $e) {
            return Resp::error($e->getMessage());
        }

        return Resp::success('登录成功', [
            'token'       => $token,
            'type'        => $pam->type,
            'is_register' => $Pam->getIsRegister() ? 'Y' : 'N',
        ]);
    }


    /**
     * @throws Throwable
     * @api                   {post} /api_v1/system/auth/reset_password [Sys]重设密码
     * @apiVersion            1.0.0
     * @apiName               SysAuthResetPassword
     * @apiGroup              Poppy
     * @apiQuery {string}     [verify_code]     方式1: 通过验证码获取到-> 验证串
     * @apiQuery {string}     [passport]        方式2: 手机号 + 验证码直接验证并修改
     * @apiQuery {string}     [captcha]         验证码
     * @apiQuery {string}     password          密码
     */
    public function resetPassword(PamPasswordRequest $request)
    {
        $verify_code = input('verify_code', '');
        $password    = $request->input('password');
        $passport    = input('passport', '');
        $captcha     = input('captcha', '');

        $Verification = new Verification();
        if ((!$verify_code && !$passport) || ($verify_code && $passport)) {
            return Resp::error('请选一种方式重设密码!');
        }

        // 获取通行证
        $useVerify = false;
        if (!$passport) {
            $useVerify = true;
            if (!$Verification->verifyOnceCode($verify_code, false)) {
                return Resp::error($Verification->getError());
            }
            $passport = $Verification->getHidden();
        }
        else if (!$captcha || !$Verification->checkCaptcha($passport, $captcha, false)) {
            return Resp::error('请输入正确验证码');
        }

        $pam = PamAccount::passport($passport);
        if (!$pam) {
            return Resp::error('此账号不存在');
        }
        $Pam = new Pam();
        if (!$Pam->checkPwdStrength($pam->type, $password)) {
            return Resp::error($Pam->getError());
        }

        if ($Pam->setPassword($pam, $password)) {
            if ($useVerify) {
                $Verification->removeOnceCode($verify_code);
            }
            else {
                $Verification->removeCaptcha($passport);
            }

            return Resp::success('密码已经重新设置');
        }

        return Resp::error($Pam->getError());
    }

    /**
     * @api                   {post} /api_v1/system/auth/bind_mobile [Sys]换绑手机
     * @apiVersion            1.0.0
     * @apiName               SysAuthBindMobile
     * @apiGroup              Poppy
     * @apiQuery {string}     verify_code     之前手机号生成的校验验证串
     * @apiQuery {string}     passport        新手机号
     * @apiQuery {string}     captcha         验证码
     */
    public function bindMobile()
    {
        $captcha     = input('captcha');
        $passport    = input('passport');
        $verify_code = input('verify_code');

        if (!UtilHelper::isMobile($passport)) {
            return Resp::error('请输入正确手机号');
        }

        $Verification = new Verification();
        if (!$Verification->checkCaptcha($passport, $captcha)) {
            return Resp::error('请输入正确验证码');
        }

        if ($verify_code && !$Verification->verifyOnceCode($verify_code)) {
            return Resp::error($Verification->getError());
        }

        $hidden = $Verification->getHidden();

        $Pam = new Pam();
        if (!$Pam->rebind($hidden, $passport)) {
            return Resp::error($Pam->getError());
        }
        return Resp::success('成功绑定手机');
    }

    /**
     * @api                   {post} /api_v1/system/auth/renew [Sys]凭证续期
     * @apiVersion            1.0.0
     * @apiName               SysAuthRenew
     * @apiGroup              Poppy
     * @apiQuery {string}     [device_id]   设备 ID, 参考 header x-id
     * @apiQuery {string}     [device_type] 设备 类型, 参考 header x-os
     */
    public function renew()
    {
        $pam   = $this->pam;
        $token = JWTAuth::fromUser($pam);

        try {
            $deviceId   = x_header('id') ?: input('device_id', '');
            $deviceType = x_header('os') ?: input('device_type', '');

            event(new TokenRenewEvent($pam, $token, $deviceId, $deviceType));
        } catch (Throwable $e) {
            return Resp::error($e->getMessage());
        }

        event(new LoginSuccessEvent($this->pam, 'jwt', 'renew'));

        return Resp::success('续期成功', [
            'token' => $token,
            'type'  => $pam->type,
        ]);
    }


    /**
     * @api                   {post} /api_v1/system/auth/logout [Sys]退出登录
     * @apiVersion            1.0.0
     * @apiName               SysAuthLogout
     * @apiGroup              Poppy
     */

    /**
     * @return JsonResponse|RedirectResponse|Response
     * @throws Throwable
     */
    public function logout()
    {
        (new Pam())->setPam($this->pam())->logout();
        return Resp::success('已退出登录');
    }

    /**
     * @api                   {post} /api_v1/system/auth/exists [Sys]检查通行证是否存在
     * @apiDescription        存在返回成功, 不成功返回失败
     * @apiVersion            1.0.0
     * @apiName               SysAuthExists
     * @apiGroup              Poppy
     * @apiQuery {string}     passport  通行证
     * @apiQuery {string}     [is_data] 是否以Data形式返回 [Y|N]
     */
    public function exists()
    {
        $passport = input('passport');
        $is_data  = input('is_data', 'N');
        $exists   = PamAccount::passportExists($passport);

        if ($exists) {
            if ($is_data === 'Y') {
                return Resp::success('通行证存在', [
                    'is_exist' => 'Y',
                ]);
            }
            return Resp::success('通行证存在');
        }
        if ($is_data === 'Y') {
            return Resp::success('通行证不存在', [
                'is_exist' => 'N',
            ]);
        }
        return Resp::error('通行证不存在');
    }

    protected function username(): string
    {
        return 'passport';
    }
}