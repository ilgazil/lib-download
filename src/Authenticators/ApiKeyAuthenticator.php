<?php

namespace Ilgazil\LibDownload\Authenticators;

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
