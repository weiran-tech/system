<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class DefaultConfig
{
    protected static array $config = [];

    /**
     * @return array
     */
    public static function get(): array
    {
        return self::$config ?: self::default();
    }

    public static function config(array $config): void
    {
        static::$config = $config;
    }

    /**
     * @return array[]
     */
    public static function default(): array
    {
        return [
            'handlers'   => [
                [
                    'class'       => RotatingFileHandler::class,
                    'constructor' => [
                        'filename' => storage_path('logs/laravel.log'),
                        'level'    => Logger::DEBUG,
                    ],
                    'formatter'   => [
                        'class'       => LineFormatter::class,
                        'constructor' => [
                            'format'                     => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                            'allowInlineLineBreaks'      => true,
                            'ignoreEmptyContextAndExtra' => true,
                            'includeStacktraces'         => true,
                            'stacktracesParser'          => null,
                            'dateFormat'                 => 'Y-m-d H:i:s',
                        ],
                    ],
                ],
            ],
            'processors' => [
                [
                    'class' => AppendRequestIdProcessor::class,
                ],
            ],
        ];
    }
}