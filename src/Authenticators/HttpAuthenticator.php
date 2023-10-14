<?php

namespace Ilgazil\LibDownload\Authenticators;

class HttpAuthenticator
{
    protected string $login = '';
    protected string $password = '';
    protected string $cookie = '';

    function __construct(string $login, string $password)
    {
        $this->login = $login;
        $this->password = $password;
    }

    public function setLogin(string $login): HttpAuthenticator
    {
        $this->login = $login;

        return $this;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setPassword(string $password): HttpAuthenticator
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setCookie(string $cookie): HttpAuthenticator
    {
        if (preg_match('/(\S+=[^;]+)/', $cookie, $matches)) {
            return $this->setCookieValue($matches[1]);
        }

        return $this;
    }

    public function setCookieValue(string $cookie): HttpAuthenticator
    {
        $this->cookie = $cookie;

        return $this;
    }

    public function getCookie(): string
    {
        return $this->cookie;
    }
}
