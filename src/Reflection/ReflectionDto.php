<?php

namespace ModelsAlpha\Reflection;

final class ReflectionDto
{
    public array $construct;
    public array $fields;
    public array $hardNames; // json.name => phpField
    public array $attributes;
    public array $smartArrays;
}
