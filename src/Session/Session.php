<?php

namespace Ilgazil\LibDownload\Session;

use Ilgazil\LibDownload\Session\Vectors\VectorInterface;

class Session
{
    protected null | Credentials $credentials = null;
    protected null | VectorInterface $vector = null;

    public function setCredentials(Credentials $credentials): Session
    {
        $this->credentials = $credentials;

        return $this;
    }

    public function getCredentials(): ?Credentials
    {
        return $this->credentials;
    }

    public function setVector(VectorInterface | null $vector): Session
    {
        $this->vector = $vector;

        return $this;
    }

    public function getVector(): ?VectorInterface
    {
        return $this->vector;
    }

}
