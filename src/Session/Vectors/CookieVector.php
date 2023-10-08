<?php

namespace Ilgazil\LibDownload\Session\Vectors;

class CookieVector implements VectorInterface
{
    private string $value = '';

    public function parse(string $raw): self
    {
        if (preg_match('/(\S+=[^;]+)/', $raw, $matches)) {
            $this->setValue($matches[1]);
        }

        return $this;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
