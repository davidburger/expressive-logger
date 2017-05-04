<?php

namespace ExpressiveLogger;

interface ILoggerErrorHandler
{
    public function __invoke(\Throwable $e);
}
