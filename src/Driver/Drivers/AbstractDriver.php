<?php

namespace Ilgazil\LibDownload\Driver\Drivers;

use Ilgazil\LibDownload\Driver\DriverInterface;
use Ilgazil\LibDownload\Session\Session;

abstract class AbstractDriver implements DriverInterface
{
    protected Session $session;

    function __construct()
    {
        $this->session = new Session();
    }

    function setSession(Session $session): void {
        $this->session = $session;
    }

    function getSession(): Session {
        return $this->session;
    }
}
