<?php

namespace ModelsAlpha\Reflection;

class SmartReflectionDto
{
    public array $construct;
    public array $fields;
    public array $hardNames; // json.name => phpField
    public array $attributes;
    public array $smartArrays;
}
