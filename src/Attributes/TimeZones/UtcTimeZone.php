<?php

namespace ModelsAlpha\Attributes\TimeZones;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class UtcTimeZone extends AbstractTimeZone
{
    public static function invoke(): string
    {
        return 'UTC';
    }
}
