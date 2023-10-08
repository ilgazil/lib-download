<?php

namespace Ilgazil\LibDownload\Session\Vectors;

class EmptyVector implements VectorInterface
{
    public function getValue(): string
    {
        return '';
    }
}
