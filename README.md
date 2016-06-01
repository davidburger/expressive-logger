# Zend Expressive Monolog integration

------------

Zend Expressive implementation of Monolog (https://github.com/Seldaek/monolog/)
 
## Installation
```sh
composer require davidburger/expressive-logger
```

## Setup
```sh
cd <project_root>
cp vendor/davidburger/expressive-logger/config/errorlog.global.php.dist config/autoload/errorlog.global.php
```
- edit config/autoload/errorlog.global.php file and set what you need

### Configuration directives
**registerErrorHandler** (default value: false) 
- if enabled, the \Monolog\ErrorHandler::register() method is called after logger initialization to set php error handlers, @see http://php.net/manual/en/ref.errorfunc.php for more details

**ignoredExceptionClasses** (default value: []) 
- Exception class names that will not be logged by defined error handlers

**useIgnoreLogic** (default value: false) 
- false = all errors will be logged
- true = classes defined in ignoredExceptionClasses array and instances of ExpressiveLogger\Exception\NotLoggableInterface will be ignored by logger

**useFacade** (default value: true)
- logger will be registered for static calls - see below.

**exceptionFormatterCallback** (default value: null)
- callback for formatting exception message and determining context before Monolog\Logger::error($message, $context) is called
- higher priority than `messageFormatter` if both are defined
- config example:
```php
'exceptionFormatterCallback' => function($exception, &$context) {

    if (true === empty($context)) {
        $context = ['exception' => $exception];
    }

    return sprintf('Exception %s: "%s" at %s line %s',
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    );
},
```
**messageFormatter** (default value: null)
- class used for formatting error messages before Monolog\Logger::error($message) is called (very usefull for exceptions)
- lower priority than `exceptionFormatterCallback` if both are defined
- it is possible to write and use custom formatter implementing `\ExpressiveLogger\MessageFormatter\MessageFormatterInterface`
- config example:
```php
'messageFormatter' => \ExpressiveLogger\MessageFormatter\DefaultFormatter::class
```

  
## Usage
### Boostrap initialization
```php
<?php

require __DIR__ . '/vendor/autoload.php';

/** @var \Interop\Container\ContainerInterface $container */
$container = require __DIR__ . '/config/container.php';

$factory = new \ExpressiveLogger\LoggerFactory();
$logger = $factory($container);

```
### Static calls
```php
use ExpressiveLogger\LoggerFacade;
//..

try {

} catch(RuntimeException $e) {
   LoggerFacade::error($e);
}
```


