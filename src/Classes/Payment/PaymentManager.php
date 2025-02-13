<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Payment;

use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Classes\Payment\Contracts\Payment;

/**
 * 支付管理
 */
class PaymentManager
{
	use AppTrait;

	/**
	 * @var array 支付映射
	 */
	private static $match = [];

	/**
	 * @param string $type 类型
	 * @return Payment
	 * @throws ApplicationException
	 */
	public static function make($type)
	{
		self::$match = (array) config('weiran.system.payment_types');
		if (!isset(self::$match[$type])) {
			throw new ApplicationException('给定的类型不正确');
		}
		$class = self::$match[$type];
		if (!class_exists($class)) {
			throw new ApplicationException('指定的类' . $class . '不存在');
		}
		$obj = new $class();
		if ($obj instanceof Payment) {
			return $obj;
		}

		throw new ApplicationException('支付对象必须实现 ' . Payment::class . ' 类');
	}

	/**
	 * 根据 Class 来组织类
	 * @param string $class 类名
	 * @return mixed
	 * @throws ApplicationException
	 */
	public static function makeByClass($class)
	{
		if (!$class) {
			throw new ApplicationException('类名不能为空');
		}
		if (!class_exists($class)) {
			throw new ApplicationException('指定的类' . $class . '不存在');
		}
		$obj = new $class();
		if ($obj instanceof Payment) {
			return $obj;
		}

		throw new ApplicationException('支付对象必须实现 ' . Payment::class . ' 类');
	}
}