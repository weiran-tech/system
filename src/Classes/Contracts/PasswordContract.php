<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Contracts;

use Weiran\System\Models\PamAccount;

interface PasswordContract
{
	/**
	 * @param PamAccount $pam      账号信息
	 * @param string     $password 密码
	 * @param string     $type     认证类型 [plain|明文;]
	 * @return mixed
	 */
	public function check(PamAccount $pam, string $password, $type = 'plain');


	/**
	 * 生成密码
	 * @param string $password     密码
	 * @param string $reg_datetime 注册日期
	 * @param string $password_key 账号KEY
	 * @return mixed
	 */
	public function genPassword(string $password, string $reg_datetime, string $password_key);
}