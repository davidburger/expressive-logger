<?php

namespace ExpressiveLogger;

use Interop\Container\ContainerInterface;
use Monolog\Handler\RedisHandler;

class LoggerFactory
{
    const HANDLERS_WITH_FACTORY = [
        RedisHandler::class,
    ];

    public function __invoke(ContainerInterface $container) : Logger
    {
        $config = $container->get('config');

        $handlers = [];

        /* Get handlers object from factory */
        foreach (self::HANDLERS_WITH_FACTORY as $handler) {
            $handlerObject = $container->get($handler);

            if ($handlerObject) {
                $handlers[$handler] = $handlerObject;
            }
        }
        
        return new Logger($config['expressiveLogger'], $handlers);
    }
}
