<?php

namespace Ilgazil\LibDownload\Session\Vectors;

interface VectorInterface
{
    public function setValue(string $value): void;
    public function getValue(): string;
}
