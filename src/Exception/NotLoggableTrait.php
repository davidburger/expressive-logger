<?php

namespace ExpressiveLogger\Exception;

trait NotLoggableTrait
{
    public $loggable = false;

    public function isLoggable() : bool
    {
        return $this->loggable;
    }
}
