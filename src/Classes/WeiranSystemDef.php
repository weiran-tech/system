<?php

declare(strict_types = 1);

namespace Weiran\System\Classes;

use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Weiran\System\Exceptions\SettingKeyNotMatchException;
use Weiran\System\Exceptions\SettingValueOutOfRangeException;

class WeiranSystemDef
{

    /**
     * [user-setting-key]:账号
     * @return string
     */
    public static function uskAccount(): string
    {
        return 'py-sys-account';
    }

    /**
     * 数据库优化的存储 KEY
     * @param $table
     * @return string
     */
    public static function ckDbOptimize($table): string
    {
        return 'db-optimize:' . $table;
    }

    /**
     * 设置
     * @return string
     */
    public static function ckSetting(): string
    {
        return 'setting';
    }

    /**
     * 设置
     * @return string
     */
    public static function ckPamRelParent(): string
    {
        return 'pam-rel-parent';
    }

    /**
     * 一次验证码(永久保存)
     * @return string
     */
    public static function ckPersistVerificationOnce(): string
    {
        return 'verification-once_code';
    }

    /**
     * 自定义验证(永久保存)
     * @return string
     */
    public static function ckPersistVerificationWord(): string
    {
        return 'verification-word';
    }

    /**
     * 验证码 KEY(持久保存)
     * @param string $key
     * @return string
     */
    public static function ckPersistVerificationCaptcha(string $key): string
    {
        return 'verification-captcha:' . $key;
    }

    /**
     * 允许访问的单点登录的 Hash(永久保存)
     * @return string
     */
    public static function ckPersistSsoValid(): string
    {
        return 'sso-valid';
    }

    /**
     * 用户单一设备禁用
     * @param string $type 账号类型
     * @return string
     */
    public static function ckBanOne(string $type): string
    {
        return 'ban-one-' . $type;
    }

    /**
     * Ip 范围
     * @param string $type
     * @return string
     */
    public static function ckBanIpRange(string $type): string
    {
        return 'ban-ip-range-' . $type;
    }


    /**
     * 填充邮箱配置
     * @return void
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws SettingKeyNotMatchException
     * @throws SettingValueOutOfRangeException
     */
    public static function fillMailConfig(): void
    {
        config([
            'mail.driver'       => sys_setting('weiran-system::mail.driver') ?: config('mail.driver'),
            'mail.encryption'   => sys_setting('weiran-system::mail.encryption') ?: config('mail.encryption'),
            'mail.port'         => sys_setting('weiran-system::mail.port') ?: config('mail.port'),
            'mail.host'         => sys_setting('weiran-system::mail.host') ?: config('mail.host'),
            'mail.from.address' => sys_setting('weiran-system::mail.from') ?: config('mail.from.address'),
            'mail.from.name'    => sys_setting('weiran-system::mail.from') ?: config('mail.from.name'),
            'mail.username'     => sys_setting('weiran-system::mail.username') ?: config('mail.username'),
            'mail.password'     => sys_setting('weiran-system::mail.password') ?: config('mail.password'),
        ]);
    }
}