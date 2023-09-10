<?php

namespace Downloads\Exceptions\DriverExceptions;

use Downloads\Exceptions\DownloadException;

class NoMatchingDriverException extends DownloadException
{
    public function __construct(string $identifier)
    {
        parent::__construct('No matching driver for ' . $identifier);
    }
}
