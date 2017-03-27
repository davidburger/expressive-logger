<?php

namespace ExpressiveLogger;

use Interop\Container\ContainerInterface;

class LoggerFactory
{
    public function __invoke(ContainerInterface $container) : Logger
    {
        $config = $container->get('config');
        $handlersInConfig = $config['expressiveLogger']['handlers'] ?? [];
        $handlers = [];

        /* Get handlers object from factory */
        foreach ($handlersInConfig as $name => $handler) {
            if ($container->has($handler['class'])) {
                $handlers[$name] = $container->get($handler['class']);
            }
        }
        
        return new Logger($config['expressiveLogger'], $handlers);
    }
}
