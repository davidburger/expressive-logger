<?php

namespace ExpressiveLogger\Exception;

use Exception;

class FacadeNotInitializedException extends Exception
{
    const ERROR_MSG = 'Please set useFacade = true in your expressiveLogger configuration';
}
