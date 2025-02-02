<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Payment\Contracts;

/**
 * 支付接口
 */
interface Payment
{
	/**
	 * @param string $order_no 订单号
	 * @param string $flow_no  订单号
	 * @return mixed
	 */
	public function payOk($order_no, $flow_no);

	/**
	 * @param string $order_no 订单号
	 * @return mixed
	 */
	public function fetch($order_no);

	/**
	 * @return mixed
	 */
	public function getError();
}