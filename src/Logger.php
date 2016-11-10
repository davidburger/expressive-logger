<?php

namespace ExpressiveLogger;

use ExpressiveLogger\Exception\InvalidConfigurationException;
use ExpressiveLogger\Exception\NotLoggableInterface;
use ExpressiveLogger\MessageFormatter\MessageFormatterInterface;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NativeMailerHandler;
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

    /**
     * @var callable|null
     */
    private $exceptionFormatterCallback;

    /**
     * @var array|null
     */
    private $messageFormatter;

    /** @var array */
    private $namedHandlers;

    public function __construct(array $config, array $handlers)
    {
        $this->logger = new MonologLogger($config['channelName']);
        $this->namedHandlers = $handlers;
        
        $this->setOptions($config);
    }

    /**
     * @param array $config
     * @throws Exception\FacadeAlreadyInitializedException
     */
    private function setOptions(array $config) 
    {
        foreach($config['handlers'] as $name => $handler) {
           $this->setHandlerFromConfig($name, $handler);
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
        
        if (false === empty($config['exceptionFormatterCallback'])
            && is_callable($config['exceptionFormatterCallback'])
        ) {
            $this->exceptionFormatterCallback = $config['exceptionFormatterCallback'];
        } elseif (false === empty($config['messageFormatter'])) {
            $this->messageFormatter = $config['messageFormatter'];
        }
    }

    private function getHandlerInstance(string $name, array $handler) : HandlerInterface
    {
        //return named handler from factory
        if (array_key_exists($name, $this->namedHandlers)) {
            return $this->namedHandlers[$name];
        }

        $class = $handler['class'] ?? null;
        $args = $handler['args'] ?? [];

        if (!$class) {
            throw new InvalidConfigurationException(
                "Handle '{$name}' can not be created. Factory or class name is missing in config"
            );
        }

        $reflectionClass = new ReflectionClass($class);
        return $reflectionClass->newInstanceArgs($args);
    }

    private function setHandlerFromConfig(string $name, array $handler) : self
    {
        $handlerInstance = $this->getHandlerInstance($name, $handler);

        if (false === empty($handler['formatter'])) {
            $formatter = $this->getFormatterFromConfig($handler['formatter']);
            $handlerInstance->setFormatter($formatter);

            //if the HTML formatter is used with email handler, set email content-type to text/html
            if (
                $formatter instanceof HtmlFormatter &&
                $handlerInstance instanceof NativeMailerHandler &&
                method_exists($handlerInstance, 'setContentType')
            ) {
                $handlerInstance->setContentType('text/html');
            }
        }

        if (false === empty($handler['processors'])) {
            $this->setProcessorsToHandlerFromConfig($handler['processors'], $handlerInstance);
        }

        $this->logger->pushHandler($handlerInstance);
        return $this;
    }

    private function getFormatterFromConfig(array $formatter) : FormatterInterface
    {
        $class = $formatter['class'];
        $args = $formatter['args'];
        $showStackTrace = $formatter['showStackTrace'] ?? false;

        $reflectionClass = new ReflectionClass($class);
        $formatterInstance = $reflectionClass->newInstanceArgs($args);

        if (method_exists($formatterInstance, 'includeStacktraces') && $showStackTrace === true) {
            $formatterInstance->includeStacktraces();
        }

        return $formatterInstance;
    }

    private function setProcessorsToHandlerFromConfig(array $processors, HandlerInterface $handlerInstance)
    {
        foreach ($processors as $processor) {

            if (is_array($processor)) {
                $class = $processor['class'];
                $args = $processor['args'];
            } else {
                $class = $processor;
                $args = [];
            }

            $reflectionClass = new ReflectionClass($class);
            $processorInstance = $reflectionClass->newInstanceArgs($args);
            $handlerInstance->pushProcessor($processorInstance);
        }
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
     * @param int $level
     * @return bool|null
     */
    public function error($message, array $context = array(), $level = \Monolog\Logger::ERROR)
    {
        if (!is_object($message)) {
            return $this->logger->log($level, $message, $context);
        }

        if (!$this->isLoggable($message)) {
            return null;
        }

        if (null !== $this->exceptionFormatterCallback) {

            $callback = $this->exceptionFormatterCallback;
            $error = $callback($message, $context);

            return $this->logger->log($level, $error, $context);

        } elseif (null !== $this->messageFormatter) {

            $formatter = $this->messageFormatter;

            return $this->getFormattedError(new $formatter, $message, $context, $level);
        }

        return $this->logger->log($level, $message, $context);
    }

    /**
     * @param MessageFormatterInterface $formatter
     * @param $message
     * @param array $context
     * @param int $level
     * @return bool|null
     */
    private function getFormattedError(MessageFormatterInterface $formatter, $message, $context = [], $level)
    {
        $context = $formatter->context($message, $context);
        $error = $formatter->format($message);

        return $this->logger->log($level, $error, $context);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->logger, $name], $arguments);
    }
}
