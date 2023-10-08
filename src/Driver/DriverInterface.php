<?php

namespace Ilgazil\LibDownload\Driver;

use Ilgazil\LibDownload\File\Download;
use Ilgazil\LibDownload\File\Metadata;

interface DriverInterface
{
    function match(string $url): bool;
    function getName(): string;
    function getMetadata(string $url): Metadata;
    function getDownload(string $url): Download;
}
