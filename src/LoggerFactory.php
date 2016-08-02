<?php

namespace ExpressiveLogger;

use Interop\Container\ContainerInterface;
use Monolog\Handler\RedisHandler;

class LoggerFactory
{
    const NAMED_HANDLERS = [
        'redis' => RedisHandler::class,
    ];

    public function __invoke(ContainerInterface $container) : Logger
    {
        $config = $container->get('config');

        $handlers = [];

        /* Get handlers object from factory */
        foreach (self::NAMED_HANDLERS as $name => $handler) {
            $handlerObject = $container->get($handler);

            if ($handlerObject) {
                $handlers[$name] = $handlerObject;
            }
        }
        
        return new Logger($config['expressiveLogger'], $handlers);
    }
}
