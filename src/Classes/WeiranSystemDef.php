<?php

declare(strict_types = 1);

namespace Weiran\System\Classes;

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
}