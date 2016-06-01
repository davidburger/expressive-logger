<?php

namespace ExpressiveLogger\MessageFormatter;

class DefaultFormatter implements MessageFormatterInterface
{
    const FORMAT = 'Exception %s: "%s" at %s line %s';

    /**
     * @param $message
     * @return string
     */
    public function format($message) : string
    {
        return sprintf(
            self::FORMAT,
            get_class($message),
            $message->getMessage(),
            $message->getFile(),
            $message->getLine()
        );
    }

    /**
     * @param $message
     * @param array $context
     * @return array
     */
    public function context($message, array $context) : array
    {
        if (false === empty($context)) {
            return $context;
        }

        return ['exception' => $message];
    }
}
