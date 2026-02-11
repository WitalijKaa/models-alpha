<?php

namespace ModelsAlpha\Attributes\TimeZones;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class CaliforniaTimeZone extends AbstractTimeZone
{
    public static function invoke(): string
    {
        return 'America/Los_Angeles';
    }
}
