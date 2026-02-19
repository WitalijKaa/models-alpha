<?php

namespace ModelsAlpha\Attributes\TimeZones;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class AutoTimeZone extends AbstractTimeZone
{
    public static function invoke(): string
    {
        return '__AUTO__';
    }
}
