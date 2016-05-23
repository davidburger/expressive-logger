<?php

namespace ExpressiveLogger;

use Interop\Container\ContainerInterface;

class LoggerFactory
{
    public function __invoke(ContainerInterface $container) : Logger
    {
        $config = $container->get('config');
        
        return new Logger($config['expressiveLogger']);
    }
}
