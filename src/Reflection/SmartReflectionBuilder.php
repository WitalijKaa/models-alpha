<?php

namespace ModelsAlpha\Reflection;

use ModelsAlpha\Helpers\Str;

class SmartReflectionBuilder
{
    public static function getSmartReflection(string $class): SmartReflectionDto
    {
        $R = new \ReflectionClass($class);
        $dto = new SmartReflectionDto();
        static::propertiesReflection($dto, $R);
        $methods = $R->getMethods();
        $dto->attributes = static::attributesReflection($methods);
        $dto->smartArrays = static::smartArraysReflection($methods);
        return $dto;
    }


    private static function propertiesReflection(SmartReflectionDto $dto, \ReflectionClass $R): void
    {
        $dto->construct = [];
        $dto->fields = [];
        $dto->hardNames = [];
        foreach ($R->getProperties() as $refProperty) {
            if ($refProperty->isPublic() && !$refProperty->isReadOnly() && !$refProperty->isStatic()) {
                if ($refProperty->isPromoted()) {
                    $dto->construct[$refProperty->getName()] = new SmartReflectionProperty($refProperty);
                }
                else {
                    $smartProp = $dto->fields[$refProperty->getName()] = new SmartReflectionProperty($refProperty);
                }
            }
            if (!empty($smartProp) && $smartProp->hardName) {
                $dto->hardNames[$smartProp->hardName] = $smartProp->name;
            }
        }
    }

    private static function attributesReflection(array $refMethods): array
    {
        $attributes = [];
        foreach ($refMethods as $method) {
            /** @var \ReflectionMethod $method */

            if ($method->isStatic() ||
                !$method->isPublic() ||
                strlen($method->name) < 14 || // 3 + 9 == 12
                !str_starts_with($method->name, 'get') ||
                !str_ends_with($method->name, 'Attribute'))
            {
                continue;
            }

            // strlen('get') == 3
            // strlen('Attribute') == 9
            $name = substr($method->name, 3, -9);
            $attributes[Str::camel($name)] = $method->name;
            $attributes[Str::snake($name)] = $method->name;
        }
        return $attributes;
    }

    private static function smartArraysReflection(array $refMethods): array
    {
        $smartArrays = [];
        foreach ($refMethods as $method) {
            /** @var \ReflectionMethod $method */

            if (!$method->isPublic() ||
                !$method->isStatic() ||
                strlen($method->name) < 12 || // strlen('smartArray') == 10
                !str_starts_with($method->name, 'smartArray')
            ) {
                continue;
            }

            $key = ucfirst(substr($method->name, 10));
            $smartArrays[$key] = call_user_func($method->class . '::' . $method->name);
        }
        return $smartArrays;
    }
}
