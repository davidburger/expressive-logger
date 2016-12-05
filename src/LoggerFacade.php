<?php

namespace ExpressiveLogger;

use ExpressiveLogger\Exception\FacadeAlreadyInitializedException;
use ExpressiveLogger\Exception\FacadeNotInitializedException;

class LoggerFacade
{
    /**
     * @var Logger
     */
    private static $logger;

    /**
     * @param Logger $logger
     * @throws Exception\FacadeAlreadyInitializedException
     */
    static public function setLogger(Logger $logger)
    {
        if (null !== self::$logger) {
            throw new FacadeAlreadyInitializedException('LoggerFacade already initialized');
        }

        self::$logger = $logger;
    }

    public static function __callStatic($name, $arguments)
    {
        if (null === self::$logger) {
            throw new FacadeNotInitializedException(FacadeNotInitializedException::ERROR_MSG);
        }

        try {
            return call_user_func_array([self::$logger, $name], $arguments);
        } catch (\Exception $e) {
        }

    }
}
