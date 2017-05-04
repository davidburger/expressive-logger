<?php
/**
 * ExpressiveLogger
 * error log configuration
 */

return [
    'dependencies' => [
        'factories' => [
            \ExpressiveLogger\Logger::class => \ExpressiveLogger\LoggerFactory::class,
//          \Monolog\Handler\RedisHandler::class => \ExpressiveLogger\Factory\RedisHandlerFactory::class, //uncomment only if you use RedisHandler
        ],
    ],
    'expressiveLogger' => [
        'channelName' => 'expressiveLogger',
        'handlers' => [
            'default' => [
                'class' => \Monolog\Handler\StreamHandler::class,
                'args' => [
                    'path' => 'data/log/error.log',
                    'level' => \Monolog\Logger::DEBUG
                ],
                'formatter' => [
                    'class' => \Monolog\Formatter\LineFormatter::class,
                    'args' => [
                        'format' => "[%datetime%] %channel%.%level_name%: %message%"
                            . "\n Context: %context%\n Extra: %extra%\n-----------\n",
                        'dateFormat' => null,
                        'allowInlineLineBreaks' => true,
                        'ignoreEmptyContextAndExtra' => false
                    ],
                    'showStackTrace' => true,
                ],
                'processors' => [
                    \Monolog\Processor\WebProcessor::class,
                    [
                        'class' => \Monolog\Processor\IntrospectionProcessor::class,
                        'args' => [
                            'level' => \Monolog\Logger::DEBUG,
                        ],
                    ],
                ],
            ]
        ],
        //exception preformatting callback
        'exceptionFormatterCallback' => null,
        'messageFormatter' => \ExpressiveLogger\MessageFormatter\DefaultFormatter::class,
        'registerErrorHandler' => false,
        'ignoredExceptionClasses' => [
            \Assert\InvalidArgumentException::class,
        ],
        'useIgnoreLogic' => false, //false - all errors will be logged
        'useFacade' => true,
        'loggerErrorHandler' => '', // \ExpressiveLogger\LoggerSystemErrorHandler::class, // (logger for logger errors)
    ]
];
