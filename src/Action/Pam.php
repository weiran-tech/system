<?php

declare(strict_types = 1);

namespace Weiran\System\Action;

use Auth;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\Framework\Helper\EnvHelper;
use Weiran\Framework\Validation\Rule;
use Weiran\MgrPage\Http\MgrPage\FormSettingLog;
use Weiran\System\Classes\Contracts\PasswordContract;
use Weiran\System\Classes\Traits\PamTrait;
use Weiran\System\Classes\Traits\UserSettingTrait;
use Weiran\System\Events\LoginBannedEvent;
use Weiran\System\Events\LoginFailedEvent;
use Weiran\System\Events\LoginSuccessEvent;
use Weiran\System\Events\PamDisableEvent;
use Weiran\System\Events\PamEnableEvent;
use Weiran\System\Events\PamLogoutEvent;
use Weiran\System\Events\PamPasswordModifiedEvent;
use Weiran\System\Events\PamRebindEvent;
use Weiran\System\Events\PamRegisteredEvent;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamLog;
use Weiran\System\Models\PamRole;
use Weiran\System\Models\SysConfig;
use Throwable;
use Tymon\JWTAuth\JWTGuard;
use Validator;

/**
 * 账号操作
 */
class Pam
{
    use UserSettingTrait, AppTrait, PamTrait;

    /**
     * @var int 父级ID
     */
    private int $parentId = 0;

    /**
     * @var bool
     */
    private bool $isRegister = false;

    /**
     * @return bool
     */
    public function getIsRegister(): bool
    {
        return $this->isRegister;
    }

    /**
     * 验证验登录
     * @param string $passport 通行证
     * @param string $captcha 验证码
     * @param string $guard 认证 Guard
     * @return bool
     * @throws Throwable
     */
    public function captchaLogin(string $passport, string $captcha, string $guard): bool
    {
        // 验证账号 + 验证码 + 频率拦截
        $Verification = new Verification();
        if (!$Verification->isPassThrottle('login-' . $passport, 1)) {
            return $this->setError($Verification->getError());
        }

        if (!$Verification->checkCaptcha($passport, $captcha)) {
            return $this->setError($Verification->getError()->getMessage());
        }

        // 判定账号是否存在, 如果不存在则进行注册
        $this->pam = PamAccount::passport($passport);
        if (!$this->pam) {
            if (Str::contains($guard, ['backend'])) {
                return $this->setError('此类账号不允许自动注册');
            }

            // 不允许不在项目中的人登录
            if (!config('poppy.system.captcha_register')) {
                return $this->setError('该账号不存在, 无法登录');
            }

            if (!$this->register($passport)) {
                return false;
            }
            $this->isRegister = true;
        }

        // 检测权限, 是否被禁用
        if (!$this->checkIsEnable($this->pam)) {
            return false;
        }

        try {
            event(new LoginBannedEvent($this->pam, $guard));
        } catch (Throwable $e) {
            return $this->setError($e);
        }

        event(new LoginSuccessEvent($this->pam, $guard));
        return true;
    }


    /**
     * 后台验证码登录
     * @param string $mobile 通行证
     * @param string $captcha 验证码
     * @return bool
     */
    public function beCaptchaLogin(string $mobile, string $captcha): bool
    {
        // 验证账号 + 验证码
        $verification = new Verification();

        if (!$verification->checkCaptcha($mobile, $captcha)) {
            return $this->setError($verification->getError()->getMessage());
        }

        // 判定账号是否存在
        $beMobile  = PamAccount::beMobile($mobile);
        $this->pam = PamAccount::where('type', PamAccount::TYPE_BACKEND)->where('mobile', $beMobile)->firstOrFail();

        // 检测权限, 是否被禁用
        if (!$this->checkIsEnable($this->pam)) {
            return false;
        }

        try {
            event(new LoginBannedEvent($this->pam, PamAccount::GUARD_BACKEND));
        } catch (Throwable $e) {
            return $this->setError($e);
        }

        event(new LoginSuccessEvent($this->pam, PamAccount::GUARD_BACKEND));
        return true;
    }

    /**
     * 设置父级ID
     * @param int $parent_id 父级id
     */
    public function setParentId(int $parent_id): void
    {
        $this->parentId = $parent_id;
    }

    /**
     * 用户注册
     * @param string           $passport passport
     * @param string           $password 密码
     * @param string|array|int $role_name 用户角色名称
     * @return bool
     * @throws Throwable
     */
    public function register(string $passport, string $password = '', $role_name = PamRole::FE_USER): bool
    {
        $passport = PamAccount::fullFilledPassport($passport);
        $type     = PamAccount::passportType($passport);

        $initDb = [
            $type          => $passport,
            'password'     => $password,
            'reg_platform' => x_header('os'),
            'reg_ip'       => EnvHelper::ip(),
            'parent_id'    => $this->parentId,
        ];

        $rule = [
            $type      => [
                Rule::required(),
                Rule::string(),
                Rule::between(6, 50),
            ],
            'password' => [
                Rule::string(),
            ],
        ];

        // 完善主账号类型规则
        if ($type === PamAccount::REG_TYPE_USERNAME) {
            if (preg_match('/\s+/', $passport)) {
                return $this->setError(trans('py-system::action.pam.user_name_not_space'));
            }
            // 注册用户时候的正则匹配
            if ($this->parentId) {
                // 子用户中必须包含 ':' 冒号
                if (strpos($initDb[$type], ':') === false) {
                    return $this->setError(trans('py-system::action.pam.sub_user_account_need_colon'));
                }
                // 初始化子用户数据
                $initDb['parent_id'] = $this->parentId;

                // 注册子用户, 子用户比主账号多一个 :
                array_unshift($rule[$type], Rule::username(true));
            }
            else {
                array_unshift($rule[$type], Rule::username());
            }
        }

        // 密码不为空时候的检测
        if ($password !== '') {
            $rule['password'] += [
                Rule::between(6, 20),
                Rule::required(),
                Rule::simplePwd(),
            ];
        }

        // 验证数据
        $validator = Validator::make($initDb, $rule);
        if ($validator->fails()) {
            return $this->setError($validator->messages());
        }

        $first = is_array($role_name) ? ($role_name[0] ?? null) : $role_name;
        if (!is_numeric($first)) {
            $role = PamRole::whereIn('name', (array) $role_name)->get();
        }
        else {
            $role = PamRole::whereIn('id', (array) $role_name)->get();
        }

        if (!$role->count()) {
            return $this->setError(trans('py-system::action.pam.role_not_exists'));
        }

        // 自动设置前缀
        $prefix = strtoupper(strtolower((string) sys_setting('py-system::pam.prefix', 'PF')));
        if ($type !== PamAccount::REG_TYPE_USERNAME) {
            $hasAccountName = false;
            // 检查是否设置了前缀
            if (!$prefix) {
                return $this->setError(trans('py-system::action.pam.not_set_name_prefix'));
            }
            $username = $prefix . '_' . Carbon::now()->format('YmdHis') . Str::random(6);
        }
        else {
            $hasAccountName = true;
            $username       = $passport;
        }

        $initDb['username']  = $username;
        $initDb['type']      = (string) $role->first()->type;
        $initDb['is_enable'] = SysConfig::ENABLE;

        // 注册时候检测密码强度
        if ($password !== '' && !$this->checkPwdStrength($initDb['type'], $password)) {
            return false;
        }

        // 处理数据库
        DB::transaction(function () use ($initDb, $role, $password, $hasAccountName, $prefix, $type) {

            if (PamAccount::where($type, $initDb[$type])->exists()) {
                throw new ApplicationException("账号 {$initDb[$type]} 已存在");
            }

            /** @var PamAccount $pam pam */
            $pam = PamAccount::create($initDb);

            // 给用户默认角色
            $pam->attachRole($role);

            // 如果没有设置账号, 则根据规范生成用户名
            if (!$hasAccountName) {
                $formatAccountName = sprintf("%s_%'.09d", $prefix, $pam->id);
                $pam->username     = $formatAccountName;

            }

            // 设置默认国际手机号, 后台自动生成(Backend 用户/Develop)
            if (!isset($initDb['mobile']) && $initDb['type'] === PamAccount::TYPE_BACKEND) {
                $pam->mobile = PamAccount::dftMobile($pam->id);
            }

            // 设置密码
            if ($password) {
                $key               = Str::random(6);
                $regDatetime       = $pam->created_at->toDateTimeString();
                $pam->password     = app(PasswordContract::class)->genPassword($password, $regDatetime, $key);
                $pam->password_key = $key;
            }

            $pam->save();

            // 触发注册成功的事件
            event(new PamRegisteredEvent($pam));

            $this->pam = $pam;

        });
        return true;
    }

    /**
     * 密码登录
     * @param string $passport passport
     * @param string $password 密码
     * @param string $guard_name 类型
     * @return bool
     * @throws ApplicationException
     */
    public function loginCheck(string $passport, string $password, string $guard_name = PamAccount::GUARD_WEB): bool
    {
        $type        = PamAccount::passportType($passport);
        $credentials = [
            $type      => $passport,
            'password' => $password,
        ];

        // check exists
        $validator = Validator::make($credentials, [
            $type      => [
                Rule::required(),
            ],
            'password' => Rule::required(),
        ]);
        if ($validator->fails()) {
            return $this->setError($validator->errors());
        }

        $guard = Auth::guard($guard_name);

        if ($guard->attempt($credentials)) {
            // jwt 不能获取到 user， 使用 getLastAttempted 方法来获取数据
            if ($guard instanceof JWTGuard || $guard instanceof SessionGuard) {
                /** @var PamAccount $pam */
                $pam = $guard->user();
            }
            else {
                throw new ApplicationException('未知的 guard');
            }
            $this->pam = $pam;

            if (!$this->checkIsEnable($this->pam)) {
                $guard->logout();
                return false;
            }

            try {
                event(new LoginBannedEvent($this->pam, $guard_name));
            } catch (Throwable $e) {
                $guard->logout();
                return $this->setError($e);
            }

            event(new LoginSuccessEvent($pam, $guard_name));
            return true;
        }

        $credentials += [
            'type'     => $type,
            'passport' => $passport,
        ];

        event(new LoginFailedEvent($credentials));

        return $this->setError(trans('py-system::action.pam.login_fail_again'));

    }

    /**
     * 设置登录密码
     * @param PamAccount $pam 用户
     * @param string     $password 密码
     * @return bool
     */
    public function setPassword(PamAccount $pam, string $password): bool
    {
        $validator = Validator::make([
            'password' => $password,
        ], [
            'password' => [
                Rule::string(),
                Rule::required(),
                Rule::simplePwd(),
                Rule::between(6, 20),
            ],
        ]);
        if ($validator->fails()) {
            return $this->setError($validator->messages());
        }

        if (!$this->checkPwdStrength($pam->type, $password)) {
            return false;
        }

        $key               = Str::random(6);
        $regDatetime       = $pam->created_at->toDateTimeString();
        $cryptPassword     = app(PasswordContract::class)->genPassword($password, $regDatetime, $key);
        $pam->password     = $cryptPassword;
        $pam->password_key = $key;
        $pam->save();

        event(new PamPasswordModifiedEvent($pam));

        return true;
    }

    /**
     * 清空后台登录用户的手机通行证
     * @param int $id
     * @return bool
     */
    public function clearMobile(int $id): bool
    {
        $pam = PamAccount::findOrFail($id);
        if (!$this->pam->can('beClearMobile', $pam)) {
            return $this->setError('你无权操作此账号, 请检查权限和用户类型');
        }

        $mobile      = PamAccount::dftMobile($pam->id);
        $pam->mobile = $mobile;
        $pam->save();
        return true;
    }

    /**
     * 设置后台登录用户的手机通行证
     * @param PamAccount $pam 用户
     * @param string     $mobile 密码
     * @return bool
     */
    public function setMobile(PamAccount $pam, string $mobile): bool
    {
        if (!$this->pam->can('beMobile', $pam)) {
            return $this->setError('你无权操作此账号, 请检查权限和用户类型');
        }

        // 补充自定义的参数
        $mobile = PamAccount::BACKEND_MOBILE_PREFIX . $mobile;

        $validator = Validator::make([
            'mobile' => $mobile,
        ], [
            'mobile' => [
                Rule::required(),
                Rule::mobile(),
                Rule::unique((new PamAccount())->getTable(), 'mobile')->where(function ($query) use ($pam) {
                    $query->where('type', PamAccount::TYPE_BACKEND);
                    if ($pam->id) {
                        $query->where('id', '!=', $pam->id);
                    }
                }),
            ],
        ]);
        if ($validator->fails()) {
            return $this->setError($validator->messages());
        }

        $pam->mobile = $mobile;
        $pam->save();

        return true;
    }


    /**
     * 设置备注
     * @param PamAccount $pam
     * @param string     $note
     * @return void
     */
    public function setNote(PamAccount $pam, string $note): void
    {
        $pam->note = $note;
        $pam->save();
    }

    /**
     * 设置角色
     * @param PamAccount|mixed $pam 账号数据
     * @param array            $roles 角色名
     * @return bool
     */
    public function setRoles($pam, array $roles): bool
    {
        /** @var PamRole[]|Collection $role */
        $role = PamRole::whereIn('id', $roles)->get();
        $pam->roles()->detach();
        $pam->roles()->attach($role->pluck('id'));

        return true;
    }

    /**
     * 生成支持 passport 格式的数组
     * @param array|Request $credentials 待转化的数据
     * @return array
     */
    public function passportData($credentials): array
    {
        if ($credentials instanceof Request) {
            $credentials = $credentials->all();
        }
        $passport     = $credentials['passport'] ?? '';
        $passport     = $passport ?: $credentials['mobile'] ?? '';
        $passport     = $passport ?: $credentials['username'] ?? '';
        $passport     = $passport ?: $credentials['email'] ?? '';
        $passportType = PamAccount::passportType($passport);

        return [
            $passportType => $passport,
            'password'    => $credentials['password'] ?? '',
        ];
    }


    /**
     * 更换账号主体, 支持除非ID外的更换方式
     * @param string|numeric|PamAccount $old_passport
     * @param string                    $new_passport
     * @return bool
     */
    public function rebind($old_passport, string $new_passport): bool
    {
        $pam = null;
        if (PamAccount::passportExists($new_passport)) {
            return $this->setError('账号已存在, 无法更换');
        }
        if (is_numeric($old_passport) || is_string($old_passport)) {
            $old_passport = PamAccount::fullFilledPassport($old_passport);
            $pam          = PamAccount::passport($old_passport);
        }
        else if ($old_passport instanceof PamAccount) {
            $pam = $old_passport;
        }
        if (!$pam) {
            return $this->setError('原账号不存在, 无法更换');
        }
        $newPassportType = PamAccount::passportType($new_passport);
        if ($newPassportType === 'id') {
            return $this->setError('用户ID 无法更换, 请检查输入');
        }
        $pam->{$newPassportType} = PamAccount::fullFilledPassport($new_passport);
        $pam->save();

        event(new PamRebindEvent($pam));
        return true;
    }

    /**
     * 后台用户禁用
     * @param int    $id 用户id
     * @param string $to 解禁时间
     * @param string $reason 禁用原因
     * @return bool
     */
    public function disable(int $id, string $to, string $reason): bool
    {
        $data      = [
            'disable_reason' => $reason,
            'disable_to'     => $to,
        ];
        $validator = Validator::make($data, [
            'disable_reason' => [
                Rule::string(),
            ],
            'disable_to'     => [
                Rule::string(),
                Rule::dateFormat('Y-m-d H:i:s'),
            ], [], [
                'disable_reason' => trans('py-system::action.pam.disable_reason'),
                'disable_to'     => trans('py-system::action.pam.disable_to'),
            ],
        ]);
        if ($validator->fails()) {
            return $this->setError($validator->messages());
        }

        /** @var PamAccount $pam */
        $pam = PamAccount::find($id);
        //当前用户已禁用
        if (!$pam->is_enable) {
            return $this->setError(trans('py-system::action.pam.account_disabled'));
        }

        $disableTo = Carbon::parse($data['disable_to']);
        if ($disableTo->lessThan(Carbon::now())) {
            return $this->setError('解禁日期需要大于当前日期');
        }
        $pam->update([
            'is_enable'        => SysConfig::DISABLE,
            'disable_reason'   => $data['disable_reason'],
            'disable_start_at' => Carbon::now(),
            'disable_end_at'   => $disableTo->toDateTimeString(),
        ]);

        event(new PamDisableEvent($pam, $this->pam, $reason));

        return true;
    }

    /**
     * 后台用户启用
     * @param int    $id 用户Id
     * @param string $reason 原因
     * @return bool
     */
    public function enable(int $id, string $reason = ''): bool
    {
        $pam = PamAccount::find($id);
        if (!$pam) {
            return $this->setError('用户不存在');
        }
        if ($pam->is_enable === SysConfig::YES) {
            return $this->setError(trans('py-system::action.pam.account_enabled'));
        }

        $pam->is_enable = SysConfig::ENABLE;
        $pam->save();

        event(new PamEnableEvent($pam, $this->pam, $reason));

        return true;
    }

    /**
     * 自动解禁
     */
    public function autoEnable(): bool
    {
        $Db  = PamAccount::where([
            'is_enable' => SysConfig::DISABLE,
        ])->where('disable_end_at', '<=', Carbon::now());
        $res = (clone $Db)->exists();
        if ($res) {
            $items = $Db->get();
            foreach ($items as $item) {
                $item->is_enable = SysConfig::ENABLE;
                $item->save();
                event(new PamEnableEvent($item, null, '系统自动解禁'));
            }
        }

        return true;
    }

    /**
     * @throws Throwable
     */
    public function logout(): void
    {
        event(new PamLogoutEvent($this->pam));
        Auth::logout();
    }

    /**
     * 清除登录日志
     * @return bool
     * @throws Exception
     */
    public function clearLog(): bool
    {
        $days = sys_setting('py-system::log.days');
        if ($days === FormSettingLog::DAYS_FOREVER) {
            return true;
        }

        $days = ((int) $days) ?: 180;
        // 删除 xx 天以外的登录日志, 默认 180 天
        PamLog::where('created_at', '<', Carbon::now()->subDays($days))->delete();
        return true;
    }

    /**
     * 修改密码
     * @param string $old_password 老密码
     * @param string $password 新密码
     * @return bool
     */
    public function changePassword(string $old_password, string $password): bool
    {
        if (!$this->checkPam()) {
            return false;
        }
        $old_password = trim($old_password);
        $password     = trim($password);

        if ($old_password === $password) {
            return $this->setError('新旧密码不能相同');
        }

        if (!app(PasswordContract::class)->check($this->pam, $old_password)) {
            return $this->setError('旧密码不正确');
        }

        return $this->setPassword($this->pam, $password);
    }

    public function checkPwdStrength($type, $password): bool
    {
        $key      = "py-system::pam.{$type}_pwd_strength";
        $strength = array_filter((array) sys_setting($key, []));
        if (!count($strength)) {
            return true;
        }
        $pwdStrength  = PamAccount::pwdStrength($password);
        $diffStrength = array_diff($strength, $pwdStrength);
        if (!count($diffStrength)) {
            return true;
        }
        $desc = collect($diffStrength)->map(function ($type) {
            return PamAccount::kvPwdStrength($type);
        })->implode(', ');
        return $this->setError('密码强度不足, 必须包含 ' . $desc);
    }

    /**
     * 验证用户权限
     * @param PamAccount $pam 用户
     * @return bool
     */
    public function checkIsEnable(PamAccount $pam): bool
    {
        if ($pam->is_enable === SysConfig::NO) {
            $now = Carbon::now();
            // 当前时间大于禁用时间(已解禁)
            if ($now->gt($pam->disable_end_at)) {
                $this->enable($pam->id, '用户登录, 超过封禁时间, 自动解禁');
                return true;
            }
            return $this->setError("该账号因 $pam->disable_reason 被封禁至 $pam->disable_end_at");
        }
        return true;
    }
}
