<?php

namespace ExpressiveLogger;

use ExpressiveLogger\Exception\NotLoggableInterface;
use Monolog\Formatter\FormatterInterface;
use Psr\Log\LoggerInterface;

use Monolog\Logger as MonologLogger;
use Monolog\ErrorHandler as MonologErrorHandler;
use ReflectionClass;

class Logger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $ignoredExceptions = [];

    /**
     * @var bool
     */
    private $useIgnoreLogic = false;

    public function __construct(array $config)
    {
        $this->logger = new MonologLogger($config['channelName']);
        foreach($config['handlers'] as $handler) {
            $this->setHandlerFromConfig($handler);
        }

        if (false === empty($config['registerErrorHandler'])) {
            MonologErrorHandler::register($this->logger);
        }

        if (false === empty($config['ignoredExceptionClasses'])) {
            $this->ignoredExceptions = $config['ignoredExceptionClasses'];
        }

        if (isset($config['useIgnoreLogic'])) {
            $this->useIgnoreLogic = (bool) $config['useIgnoreLogic'];
        }

        if (false === empty($config['useFacade'])) {
            LoggerFacade::setLogger($this);
        }
    }

    /**
     * @param array $handler
     * @return $this
     */
    private function setHandlerFromConfig(array $handler)
    {
        $class = $handler['class'];
        $args = $handler['args'];

        $reflectionClass = new ReflectionClass($class);
        $handlerInstance = $reflectionClass->newInstanceArgs($args);

        if (false === empty($handler['formatter'])) {
            $formatter = $this->getFormatterFromConfig($handler['formatter']);
            $handlerInstance->setFormatter($formatter);
        }

        $this->logger->pushHandler($handlerInstance);
        return $this;
    }

    /**
     * @param array $formatter
     * @return FormatterInterface
     */
    private function getFormatterFromConfig(array $formatter)
    {
        $class = $formatter['class'];
        $args = $formatter['args'];

        $reflectionClass = new ReflectionClass($class);
        return $reflectionClass->newInstanceArgs($args);
    }

    public function isLoggable($error) : bool
    {
        if (false === $this->useIgnoreLogic) {
            return true;
        }

        $class = get_class($error);

        if (false !== array_search($class, $this->ignoredExceptions)) {
            return false;
        }

        if ($error instanceof NotLoggableInterface) {
            return $error->isLoggable();
        }

        return true;
    }

    /**
     * @param $message
     * @param array $context
     * @return bool|null
     */
    public function error($message, array $context = array())
    {
        if (is_object($message) && $this->isLoggable($message)) {
            return $this->logger->error($message, $context);
        }
        return null;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->logger, $name], $arguments);
    }
}
