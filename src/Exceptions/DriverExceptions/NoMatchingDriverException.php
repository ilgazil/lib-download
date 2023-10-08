<?php

namespace Ilgazil\LibDownload\Exceptions\DriverExceptions;

use Ilgazil\LibDownload\Exceptions\DownloadException;

class NoMatchingDriverException extends DownloadException
{
    public function __construct(string $identifier)
    {
        parent::__construct('No matching driver for ' . $identifier);
    }
}
