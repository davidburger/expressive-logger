<?php

namespace ExpressiveLogger;

use ExpressiveLogger\Exception\NotLoggableInterface;
use Monolog\Formatter\FormatterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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

    public function __construct(array $config)
    {
        $this->logger = new MonologLogger($config['channelName']);
        
        $this->setOptions($config);
    }

    /**
     * @param array $config
     * @throws Exception\FacadeAlreadyInitializedException
     */
    private function setOptions(array $config) 
    {
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
        
        if (false === empty($config['exceptionFormatterCallback'])
            && is_callable($config['exceptionFormatterCallback'])
        ) {
            $this->exceptionFormatterCallback = $config['exceptionFormatterCallback'];
        } elseif (false === empty($config['messageFormatter'])) {
            $this->messageFormatter = $config['messageFormatter'];
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
        if (!is_object($message)) {
            return $this->logger->error($message, $context);
        }

        if (!$this->isLoggable($message)) {
            return null;
        }

        if (null !== $this->exceptionFormatterCallback) {

            $callback = $this->exceptionFormatterCallback;
            $error = $callback($message, $context);

            return $this->logger->error($error, $context);

        } elseif (null !== $this->messageFormatter) {

            $class = $this->messageFormatter;
            $formatter = new $class;
            
            if (!$formatter instanceof MessageFormatterInterface) {
                throw new \RuntimeException(
                    sprintf('Formatter %s does not implement MessageFormatterInterface', get_class($formatter))
                );
            }

            $context = $formatter->context($message, $context);
            $error = $formatter->format($message);

            return $this->logger->error($error, $context);
        }

        return $this->logger->error($message, $context);

    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->logger, $name], $arguments);
    }
}
