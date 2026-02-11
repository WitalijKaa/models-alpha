<?php

namespace ModelsAlpha\Attributes\TimeFormats;

#[\Attribute]
class YmdHisTimeFormat extends AbstractTimeFormat
{
    public static function invoke(): string|array
    {
        return 'Y-m-d H:i:s';
    }
}
