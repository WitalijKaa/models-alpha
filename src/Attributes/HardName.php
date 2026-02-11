<?php

namespace ModelsAlpha\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class HardName
{
    public function __construct(public string $jsonName)
    {
    }
}
