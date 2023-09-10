<?php

namespace Downloads\Driver;

use Downloads\File\Download;
use Downloads\File\Metadata;

abstract class DriverInterface
{
    abstract function match(string $url): bool;
    abstract function getName(): string;
    abstract function authenticate(string $login, string $password): void;
    abstract function unauthenticate(): void;
    abstract function getMetadata(string $url): Metadata;
    abstract function getDownload(string $url): Download;
}
