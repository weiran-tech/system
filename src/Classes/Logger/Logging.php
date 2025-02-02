<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Logger;

use Psr\Log\LoggerInterface;

/**
 * @method static emergency($message, array $context = [])
 * @method static alert($message, array $context = [])
 * @method static critical($message, array $context = [])
 * @method static error($message, array $context = [])
 * @method static warning($message, array $context = [])
 * @method static notice($message, array $context = [])
 * @method static info($message, array $context = [])
 * @method static debug($message, array $context = [])
 * @method static log($level, $message, array $context = [])
 */
class Logging
{
    /**
     * @param string $name
     * @return mixed|LoggerInterface
     */
    public static function logger(string $name = '')
    {
        return app(LoggerFactory::class)->get($name);
    }

    public static function __callStatic(string $method, $arguments)
    {
        return self::logger()->{$method}(...$arguments);
    }
}