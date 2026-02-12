<?php

namespace ModelsAlpha\Reflection;

use ModelsAlpha\Helpers\Str;

class ReflectionBuilder
{
    public static function getReflection(string $class): ReflectionDto
    {
        $R = new \ReflectionClass($class);
        [$construct, $fields, $hardNames] = static::propertiesReflection($R);
        $methods = $R->getMethods();

        return new ReflectionDto(
            construct: $construct,
            fields: $fields,
            hardNames: $hardNames,
            attributes: static::attributesReflection($methods),
            smartArrays: static::smartArraysReflection($methods)
        );
    }


    private static function propertiesReflection(\ReflectionClass $R): array
    {
        $construct = [];
        $fields = [];
        $hardNames = [];
        foreach ($R->getProperties() as $refProperty) {
            if ($refProperty->isPublic() && !$refProperty->isReadOnly() && !$refProperty->isStatic()) {
                if ($refProperty->isPromoted()) {
                    $construct[$refProperty->getName()] = new ReflectionProperty($refProperty);
                }
                else {
                    $refProp = $fields[$refProperty->getName()] = new ReflectionProperty($refProperty);
                }
            }
            if (!empty($refProp) && $refProp->hardName) {
                $hardNames[$refProp->hardName] = $refProp->name;
            }
        }
        return [$construct, $fields, $hardNames];
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
