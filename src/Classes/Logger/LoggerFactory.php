<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Logger;

use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;

readonly class LoggerFactory
{
    public function __construct(
        private LogManager $manager
    ) {}

    /**
     * @return LogManager
     */
    public function get(string $channel = ''): LoggerInterface
    {
        $channel = $channel ?: (string) config('app.env', 'production');

        return $this->manager->withContext([
            'channel' => $channel,
        ]);
    }
}
