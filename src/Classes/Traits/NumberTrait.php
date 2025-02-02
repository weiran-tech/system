<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Traits;

use Weiran\Framework\Classes\Number;
use Weiran\Framework\Exceptions\ArithmeticException;
use Throwable;

/**
 * Numbers Helpers
 */
trait NumberTrait
{
    /**
     * 数值相加
     * @param mixed $a 需要叠加的数据
     * @param mixed $b 需要叠加的数据
     * @param int   $scale
     * @return string
     */
    public function numberAdd($a, $b, int $scale = 2): string
    {
        return (new Number($a, $scale))->add($b)->getValue();
    }

    /**
     * 减法
     * @param mixed $a 需要叠加的数据
     * @param mixed $b 需要叠加的数据
     * @param int   $scale
     * @return string
     */
    public function numberSubtract($a, $b, int $scale = 2): string
    {
        return (new Number($a, $scale))->subtract($b)->getValue();
    }

    /**
     * 乘法计算
     * @param mixed $a 需要叠加的数据
     * @param mixed $b 需要叠加的数据
     * @param int   $scale
     * @return string
     */
    public function numberMultiply($a, $b, int $scale = 2): string
    {
        return (new Number($a, $scale))->multiply($b)->getValue();
    }

    /**
     * 除法
     * @param mixed $a     除数
     * @param mixed $b     被除数
     * @param int   $scale 精度
     * @return string
     */
    public function numberDivide($a, $b, int $scale = 2): string
    {
        try {
            return (new Number($a, $scale))->divide($b)->getValue();
        } catch (ArithmeticException $e) {
            return '0.00';
        }
    }

    /**
     * 计算费率
     * @param mixed $amount   金额
     * @param mixed $fee_rate 费率
     * @param int   $scale
     * @return string
     */
    public function numberFee($amount, $fee_rate = 0.0, int $scale = 2): string
    {
        try {
            return (new Number($amount, $scale))->multiply($fee_rate)->divide(100)->getValue();
        } catch (Throwable $e) {
            return '0.00';
        }
    }

    /**
     * 数值比较
     *
     * 返回值 0: 相等 , 1: a大于b; -1: a小于b
     *
     * @param mixed $a 数值a
     * @param mixed $b 数值b
     * @param int   $scale
     * @return int
     */
    public function numberCompare($a, $b, int $scale = 2): int
    {
        return (new Number($a, $scale))->compareTo($b);
    }

    /**
     * 获取字四舍五入串值
     * Returns the current raw value of this BigNumber
     *
     * @param mixed $a 数值a
     * @param int   $precision
     * @param int   $scale
     * @return string String representation of the number in base 10
     */
    public function numberRound($a, int $precision = 0, int $scale = 2): string
    {
        return (new Number($a, $scale))->round($precision)->getValue();
    }
}