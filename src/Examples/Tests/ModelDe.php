<?php

namespace ModelsAlpha\Examples\Tests;

#[\Attribute]
class ModelDe
{
    public function __construct(public string $dtoPropStr)
    {
    }

    public function toArray(): array
    {
        return ['dtoPropStr' => $this->dtoPropStr];
    }
}