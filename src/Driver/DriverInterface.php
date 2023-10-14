<?php

namespace Ilgazil\LibDownload\Driver;

use Ilgazil\LibDownload\File\Download;
use Ilgazil\LibDownload\File\Metadata;
use Ilgazil\LibDownload\Session\Session;

interface DriverInterface
{
    function match(string $url): bool;
    function getName(): string;
    function getMetadata(string $url): Metadata;
    function getDownload(string $url): Download;
    function setSession(Session $session): void;
    function getSession(): Session;
    function login(string $login, string $password): void;
}
