<?php

namespace ModelsAlpha\Attributes\TimeFormats;

#[\Attribute]
class YmdTimeFormat extends AbstractTimeFormat
{
    public static function invoke(): string|array
    {
        return 'Y-m-d';
    }
}
