<?php

namespace ExpressiveLogger\Exception;

interface NotLoggableInterface
{
    public function isLoggable() : bool;
}
