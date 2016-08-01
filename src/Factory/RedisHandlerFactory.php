<?php

namespace ExpressiveLogger\Factory;

use ExpressiveLogger\Exception\InvalidConfigurationException;
use Interop\Container\ContainerInterface;
use Monolog\Handler\RedisHandler;
use Monolog\Logger;
use Predis\Client;

final class RedisHandlerFactory
{

    public function __invokable(ContainerInterface $container) : RedisHandler
    {
        $config = $container->get('config');

        if (!isset($config['expressiveLogger']['handlers']['redis'])) {
            throw new InvalidConfigurationException('Redis config is not set');
        }

        $redisConfig = $config['expressiveLogger']['handlers']['redis'];

        $redisClient = $container->get($redisConfig['client']);

        if (!$redisClient) {
            throw new InvalidConfigurationException('Redis client not found in container');
        }

        if ($redisClient instanceof Client) {
            throw new InvalidConfigurationException('Redis client have to be instance of Predis\Client');
        }

        $key = $redisConfig['key'] ?? 'default';
        $level = $redisConfig['key'] ?? Logger::DEBUG;

        return new RedisHandler($redisClient, $key, $level);
    }
}