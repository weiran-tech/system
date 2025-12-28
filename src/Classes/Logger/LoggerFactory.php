<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Logger;

use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    /**
     * @var Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return mixed|LoggerInterface
     */
    public function get(string $name = '')
    {
        $name = $name ?: $this->name();

        $config = config('weiran.system.logging') ?: DefaultConfig::get();

        $handlers   = $this->handlers($config);
        $processors = $this->processors($config);

        return $this->app->make(Logger::class, [
            'name'       => $name,
            'handlers'   => $handlers,
            'processors' => $processors,
        ]);
    }

    protected function name(): string
    {
        return (string) config('app.env', 'production');
    }

    protected function handlers(array $config): array
    {
        $handlerConfigs         = $config['handlers'] ?? [[]];
        $handlers               = [];
        $defaultHandlerConfig   = $this->getDefaultHandlerConfig($config);
        $defaultFormatterConfig = $this->getDefaultFormatterConfig($config);
        foreach ($handlerConfigs as $value) {
            $class       = $value['class'] ?? $defaultHandlerConfig['class'];
            $constructor = $value['constructor'] ?? $defaultHandlerConfig['constructor'];
            if (isset($value['formatter'])) {
                if (!isset($value['formatter']['constructor'])) {
                    $value['formatter']['constructor'] = $defaultFormatterConfig['constructor'];
                }
            }
            $formatterConfig = $value['formatter'] ?? $defaultFormatterConfig;

            $handlers[] = $this->handler($class, $constructor, $formatterConfig);
        }

        return $handlers;
    }

    protected function processors(array $config): array
    {
        $result = [];
        if (!isset($config['processors']) && isset($config['processor'])) {
            $config['processors'] = [$config['processor']];
        }

        foreach ($config['processors'] ?? [] as $value) {
            if (is_array($value) && isset($value['class'])) {
                $value = $this->app->make($value['class'], $value['constructor'] ?? []);
            }

            $result[] = $value;
        }

        return $result;
    }

    protected function handler($class, $constructor, $formatterConfig): HandlerInterface
    {
        /** @var HandlerInterface $handler */
        $handler = $this->app->make($class, $constructor);

        if ($handler instanceof FormattableHandlerInterface) {
            $formatterClass       = $formatterConfig['class'];
            $formatterConstructor = $formatterConfig['constructor'];

            /** @var FormatterInterface $formatter */
            $formatter = $this->app->make($formatterClass, $formatterConstructor);

            $handler->setFormatter($formatter);
        }

        return $handler;
    }

    protected function getDefaultHandlerConfig($config): array
    {
        $handlerClass       = Arr::get($config, 'handler.class', StreamHandler::class);
        $handlerConstructor = Arr::get($config, 'handler.constructor', [
            'stream' => storage_path('logs/laravel.log'),
            'level'  => Logger::DEBUG,
        ]);

        return [
            'class'       => $handlerClass,
            'constructor' => $handlerConstructor,
        ];
    }

    protected function getDefaultFormatterConfig($config): array
    {
        $formatterClass       = Arr::get($config, 'formatter.class', LineFormatter::class);
        $formatterConstructor = Arr::get($config, 'formatter.constructor', []);

        return [
            'class'       => $formatterClass,
            'constructor' => $formatterConstructor,
        ];
    }
}
