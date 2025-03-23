<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class AppendRequestIdProcessor implements ProcessorInterface
{

    public function __invoke(LogRecord $record)
    {
        $requestId = request()->requestId ?? '';

        $record['extra']['request_id'] = $requestId;

        return $record;
    }
}