<?php

namespace ExpressiveLogger;

class LoggerSystemErrorHandler implements ILoggerErrorHandler
{
    public function __invoke(\Throwable $e)
    {
        error_log((string)$e, 0);
    }
}
