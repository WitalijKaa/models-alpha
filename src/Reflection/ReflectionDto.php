<?php

namespace ModelsAlpha\Reflection;

final readonly class ReflectionDto
{
    public function __construct(

        public array $construct,
        public array $fields,
        public array $hardNames, // json.name => phpField
        public array $attributes,
        public array $smartArrays,

    ) {}
}
