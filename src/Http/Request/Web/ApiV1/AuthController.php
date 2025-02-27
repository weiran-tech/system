<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\Web\ApiV1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Weiran\Framework\Auth\ThrottlesLogins;
use Weiran\Framework\Classes\Resp;
use Weiran\Framework\Helper\UtilHelper;
use Weiran\System\Action\Pam;
use Weiran\System\Action\Verification;
use Weiran\System\Events\LoginSuccessEvent;
use Weiran\System\Events\LoginTokenPassedEvent;
use Weiran\System\Events\TokenRenewEvent;
use Weiran\System\Http\Request\Web\Validation\AuthBindMobileRequest;
use Weiran\System\Http\Request\Web\Validation\AuthExistsRequest;
use Weiran\System\Http\Request\Web\Validation\AuthLoginRequest;
use Weiran\System\Http\Request\Web\Validation\AuthPasswordRequest;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\Resources\PamResource;

/**
 * 认证控制器
 */
class AuthController extends JwtApiController
{
    use ThrottlesLogins;

    /**
     * 最大请求次数 10 次
     * @var float
     */
    protected float $maxAttempts = 10;

    /**
     * 30 秒内, 最多 10 次请求
     * @var float
     */
    protected float $decayMinutes = 0.5;

    #[OA\Get(
        path: '/api/web/system/v1/auth/access',
        description: '检测 Token',
        summary: '检测 Token',
        tags: ['System'],
        parameters: [
            new OA\Parameter(
                name: 'token',
                description: 'Token',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '获取成功',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/AliyunOssStsTempOssBody'
                )
            )
        ]
    )]
    public function access(): JsonResponse
    {
        $pam    = (new PamResource($this->pam()))->toArray(app('request'));
        $append = (array) sys_hook('weiran.system.auth_access');
        $all    = array_merge($pam, $append);
        return Resp::success(
            '有效登录',
            $all
        );
    }

    /**
     * @param Request $req
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     * @throws Throwable
     */
    #[OA\Post(
        path: '/api/web/system/v1/auth/login',
        summary: '登录',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SystemAuthLoginRequest')
        ),
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: '登录成功',
                content: new OA\JsonContent(ref: '#/components/schemas/SystemAuthLoginBody')
            )
        ]
    )]
    public function login(Request $req): JsonResponse
    {
        $req->merge([
            'os' => input('device_type', '') ?: x_header('os'),
        ]);
        /** @var AuthLoginRequest $request */
        $request     = app(AuthLoginRequest::class, [$req]);
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
     */
    #[OA\Post(
        path: '/api/web/system/v1/auth/reset_password',
        summary: '重设密码',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SystemAuthPasswordRequest')
        ),
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: '操作成功',
                content: new OA\JsonContent(ref: '#/components/schemas/ResponseBaseBody')
            )
        ]
    )]
    public function resetPassword(AuthPasswordRequest $request)
    {
        $verify_code = $request->input('verify_code', '');
        $password    = $request->input('password');
        $passport    = $request->input('passport', '');
        $captcha     = $request->input('captcha', '');

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

    #[OA\Post(
        path: '/api/web/system/v1/auth/bind_mobile',
        summary: '换绑手机',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SystemAuthBindMobileRequest')
        ),
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: '操作成功',
                content: new OA\JsonContent(ref: '#/components/schemas/ResponseBaseBody')
            )
        ]
    )]
    public function bindMobile(AuthBindMobileRequest $request)
    {
        $captcha     = $request->getCaptcha();
        $passport    = $request->getPassport();
        $verify_code = $request->getVerifyCode();

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

    #[OA\Post(
        path: '/api/web/system/v1/auth/renew',
        summary: '续期',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'device_id', description: '设备 ID, 参考 header x-id', type: 'string',),
                new OA\Property(property: 'device_type', description: '设备类型, 参考 header x-os', type: 'string',),
            ])
        ),
        tags: ['System'],
        responses: [
            new OA\Response(response: 200, description: '操作成功',
                content: new OA\JsonContent(ref: '#/components/schemas/ResponseBaseBody')
            )
        ]
    )]
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
     * @return JsonResponse|RedirectResponse|Response
     * @throws Throwable
     */
    #[OA\Post(
        path: '/api/web/system/v1/auth/logout',
        summary: '退出登录',
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: '操作成功',
                content: new OA\JsonContent(ref: '#/components/schemas/ResponseBaseBody')
            )
        ]
    )]
    public function logout(): Response|JsonResponse|RedirectResponse
    {
        (new Pam())->setPam($this->pam())->logout();
        return Resp::success('已退出登录');
    }

    #[OA\Post(
        path: '/api/web/system/v1/auth/exists',
        summary: '检查通行证是否存在',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SystemAuthExistsRequest')
        ),
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: '操作成功',
                content: new OA\JsonContent(ref: '#/components/schemas/ResponseBaseBody')
            )
        ]
    )]
    public function exists(AuthExistsRequest $request): JsonResponse
    {
        $exists = PamAccount::passportExists($request->getPassport());

        if ($exists) {
            return Resp::success('通行证存在', [
                'is_exist' => 'Y',
            ]);
        }
        return Resp::success('通行证不存在', [
            'is_exist' => 'N',
        ]);
    }

    protected function username(): string
    {
        return 'passport';
    }
}