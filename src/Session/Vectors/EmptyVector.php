<?php

namespace Ilgazil\LibDownload\Session\Vectors;

class EmptyVector implements VectorInterface
{
    public function setValue(string $value): void
    {
    }

    public function getValue(): string
    {
        return '';
    }
}
