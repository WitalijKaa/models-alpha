<?php

namespace ModelsAlpha\Reflection;

class ReflectionCache
{
    public static array $repo = [];

    public static function prepare(string $class): void
    {
        if (empty(ReflectionCache::$repo[$class])) {
            ReflectionCache::$repo[$class] = SmartReflectionBuilder::getSmartReflection($class);
        }
    }
}