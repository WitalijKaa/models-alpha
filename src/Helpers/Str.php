<?php

namespace ModelsAlpha\Helpers;

class Str
{
    public static function aClass(mixed $class): string
    {
        if (!is_string($class)) {
            if (is_object($class)) {
                $class = $class::class;
            }
            else if (is_array($class)) {
                return 'Array';
            }
            else if (is_callable($class)) {
                return 'Callable';
            }
            else {
                $class = (string)$class;
            }
        }
        if (!$class) {
            return 'NoClass';
        }

        $pos = strrpos($class, '\\');

        if (!$pos) {
            return $class;
        }

        return substr($class, 1 + $pos);
    }

    public static function camel($value)
    {
        return lcfirst(static::studly($value));
    }

    public static function snake($value, $delimiter = '_')
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        return $value;
    }

    public static function studly($value)
    {
        $words = mb_split('\s+', static::replace(['-', '_'], ' ', $value));

        $studlyWords = array_map(fn ($word) => static::ucfirst($word), $words);

        return implode($studlyWords);
    }

    public static function replace($search, $replace, $subject, $caseSensitive = true)
    {
        return $caseSensitive
            ? str_replace($search, $replace, $subject)
            : str_ireplace($search, $replace, $subject);
    }

    public static function ucfirst($string)
    {
        return static::upper(static::substr($string, 0, 1)).static::substr($string, 1);
    }

    public static function substr($string, $start, $length = null, $encoding = 'UTF-8')
    {
        return mb_substr($string, $start, $length, $encoding);
    }

    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }
}