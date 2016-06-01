<?php

namespace ExpressiveLogger\MessageFormatter;

interface MessageFormatterInterface
{
    public function format($message) : string;

    public function context($message, array $context) : array;
}
