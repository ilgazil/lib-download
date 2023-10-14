<?php

namespace Ilgazil\LibDownload\Authenticators;

use anlutro\cURL\Request;

class ApiKeyAuthenticator
{
    protected string $key;

    function __construct(string $key)
    {
        $this->key = $key;
    }

    public function setKey(string $key): ApiKeyAuthenticator
    {
        $this->key = $key;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
