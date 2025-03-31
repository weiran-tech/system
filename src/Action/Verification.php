<?php

declare(strict_types = 1);

namespace Weiran\System\Action;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Weiran\Core\Redis\RdsDb;
use Weiran\Core\Redis\RdsStore;
use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\Framework\Helper\EnvHelper;
use Weiran\Framework\Helper\StrHelper;
use Weiran\Framework\Helper\UtilHelper;
use Weiran\System\Classes\WeiranSystemDef;
use Weiran\System\Models\PamAccount;

/**
 * 系统校验
 */
class Verification
{
    use AppTrait;

    const TYPE_MAIL   = 'mail';
    const TYPE_MOBILE = 'mobile';

    public const CAPTCHA_SEND_TYPE_EXIST    = 'exist';
    public const CAPTCHA_SEND_TYPE_NO_EXIST = 'no-exist';

    /**
     * @var RdsDb
     */
    private static RdsDb $db;

    /**
     * @var string
     */
    private string $captcha;

    /**
     * @var string
     */
    private string $passportKey;

    /**
     * 隐藏的数据
     * @var mixed
     */
    private $hidden;

    public function __construct()
    {
        self::$db = sys_tag('weiran-system-persist');
    }

    /**
     * @param string $passport    需要发送的通行证
     * @param int    $expired_min 过期时间
     * @param int    $length      验证码长度
     * @return bool
     */
    public function genCaptcha(string $passport, int $expired_min = 5, int $length = 6): bool
    {
        $passport = PamAccount::fullFilledPassport($passport);
        if (!$this->checkPassport($passport)) {
            return false;
        }
        $key = $this->passportKey;

        if ($data = self::$db->get(WeiranSystemDef::ckPersistVerificationCaptcha($key))) {
            if ($data['silence'] > Carbon::now()->timestamp) {
                $captcha = $data['captcha'];
            }
        }

        // 发送
        $captcha = $captcha ?? StrHelper::randomCustom($length, '0123456789');
        $data    = [
            'captcha' => $captcha,
            'silence' => Carbon::now()->timestamp + 60,
        ];
        self::$db->set(WeiranSystemDef::ckPersistVerificationCaptcha($key), $data, 'ex', $expired_min * 60);

        $this->captcha = $captcha;
        return true;
    }

    /**
     * 验证验证码, 验证码验证成功仅有一次机会
     * @param string $passport 通行证
     * @param string $captcha  验证码
     * @param bool   $forget
     * @return bool
     */
    public function checkCaptcha(string $passport, string $captcha, bool $forget = true): bool
    {
        if (!$captcha) {
            return $this->setError('请输入验证码');
        }
        $passport = PamAccount::fullFilledPassport($passport);
        if (!$this->checkPassport($passport)) {
            return false;
        }
        $key = $this->passportKey;

        /* 测试账号验证码/正确的验证码即可登录
         * ---------------------------------------- */
        $strAccount = trim(sys_setting('weiran-system::pam.test_account') ?? '');
        if ($strAccount) {
            $explode     = EnvHelper::isWindows() ? "\n" : PHP_EOL;
            $testAccount = explode($explode, sys_setting('weiran-system::pam.test_account'));
            if (count($testAccount)) {
                $testAccount = collect(array_map(function ($item) {
                    $account = explode(':', $item);

                    return [
                        'passport' => trim($account[0] ?? ''),
                        'captcha'  => trim($account[1] ?? ''),
                    ];
                }, $testAccount));
                $item        = $testAccount->where('passport', $passport)->first();
                if ($item) {
                    $savedCaptcha = (string) ($item['captcha'] ?? '');
                    if ($savedCaptcha && $captcha !== $savedCaptcha) {
                        return $this->setError('验证码不正确!');
                    }

                    return true;
                }
            }
        }

        if (($data = self::$db->get(WeiranSystemDef::ckPersistVerificationCaptcha($key))) && ((string) $data['captcha']) === $captcha) {
            if ($forget) {
                self::$db->del(WeiranSystemDef::ckPersistVerificationCaptcha($key));
            }
            return true;
        }
        return $this->setError('验证码填写错误');
    }

    /**
     * 移除验证码
     * @param string $passport 通行证
     * @return bool
     */
    public function removeCaptcha(string $passport): bool
    {
        $passport = PamAccount::fullFilledPassport($passport);
        if (!$this->checkPassport($passport)) {
            return false;
        }
        $key = $this->passportKey;
        self::$db->del(WeiranSystemDef::ckPersistVerificationCaptcha($key));
        return false;
    }

    /**
     * 限流以及提示, 开发状态下不进行限流
     * @param string $key     限流标识
     * @param int    $seconds 秒数
     * @return bool
     */
    public function isPassThrottle(string $key, int $seconds = 30): bool
    {
        if (!is_production()) {
            return true;
        }

        if (RdsStore::inLock('verification:' . $key, $seconds)) {
            return $this->setError('请勿频繁请求');
        }
        return true;
    }


    /**
     * 获取通行证验证码
     * @param string $passport 通行证
     * @return bool
     */
    public function fetchCaptcha(string $passport): bool
    {
        $passport = PamAccount::fullFilledPassport($passport);
        if (!$this->checkPassport($passport)) {
            return false;
        }
        $key = $this->passportKey;

        if ($data = self::$db->get(WeiranSystemDef::ckPersistVerificationCaptcha($key))) {
            $this->captcha = $data['captcha'];
            return true;
        }
        return $this->setError('验证码失效, 无法获取');
    }

    /**
     * 生成一次验证码
     * @param int          $expired_min 过期时间
     * @param string|array $hidden_str  隐藏的验证字串
     * @return string
     */
    public function genOnceVerifyCode(int $expired_min = 10, $hidden_str = ''): string
    {
        $randStr = Str::random();

        $hidden = serialize($hidden_str);

        $str  = [
            'hidden' => $hidden,
            'random' => $randStr . '@' . Carbon::now()->timestamp,
        ];
        $code = md5(json_encode($str) . microtime());
        self::$db->set(WeiranSystemDef::ckPersistVerificationOnce() . ':' . $code, $str, 'ex', $expired_min * 60);
        return $code;
    }

    /**
     * 需要验证的验证码
     * @param string $code   一次验证码
     * @param bool   $forget 是否删除验证码
     * @return bool
     */
    public function verifyOnceCode(string $code, bool $forget = true): bool
    {
        if ($data = self::$db->get(WeiranSystemDef::ckPersistVerificationOnce() . ':' . $code)) {
            $this->hidden = unserialize($data['hidden']);
            if ($forget) {
                self::$db->del(WeiranSystemDef::ckPersistVerificationOnce() . ':' . $code);
            }
            return true;
        }
        return $this->setError(trans('weiran-system::action.verification.verify_code_error'));
    }

    public function removeOnceCode($code): bool
    {
        self::$db->del(WeiranSystemDef::ckPersistVerificationOnce() . ':' . $code);
        return true;
    }

    /**
     * @param string       $key
     * @param int          $expired_min 过期时间
     * @param string|array $word
     */
    public function saveWord(string $key, $word = '', int $expired_min = 5): void
    {
        if (!is_array($word)) {
            $word = (string) $word;
        }
        self::$db->set(WeiranSystemDef::ckPersistVerificationWord() . ':' . $key, $word, 'ex', $expired_min * 60);
    }

    /**
     * 验证校验值, 不进行删除
     * @param string       $key  验证KEy
     * @param string|array $word 验证值
     * @return bool
     */
    public function verifyWord(string $key, $word = ''): bool
    {
        if (!is_array($word)) {
            $word = (string) $word;
        }
        if (!$word) {
            return $this->setError('请输入校验值');
        }

        if ($data = self::$db->get(WeiranSystemDef::ckPersistVerificationWord() . ':' . $key)) {
            if (is_numeric($data)) {
                $data = (string) $data;
            }
            if ($data === $word) {
                return true;
            }
        }
        return $this->setError('校验值填写错误');
    }

    /**
     * 删除验证数据
     * @param string $key
     */
    public function removeWord(string $key): void
    {
        self::$db->del(WeiranSystemDef::ckPersistVerificationWord() . ':' . $key);
    }


    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * @return string
     */
    public function getCaptcha(): string
    {
        return $this->captcha;
    }

    private function checkPassport($passport): bool
    {
        // 验证数据格式
        if (UtilHelper::isEmail($passport)) {
            $passportType = self::TYPE_MAIL;
        }
        elseif (UtilHelper::isMobile($passport)) {
            $passportType = self::TYPE_MOBILE;
        }
        else {
            return $this->setError(trans('weiran-system::action.verification.passport_not_support'));
        }
        $this->passportKey = $passportType . '-' . $passport;
        return true;
    }
}
