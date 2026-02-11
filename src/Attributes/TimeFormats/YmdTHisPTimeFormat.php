<?php

namespace ModelsAlpha\Attributes\TimeFormats;

#[\Attribute]
class YmdTHisPTimeFormat extends AbstractTimeFormat
{
    public static function invoke(): string|array
    {
        return 'Y-m-d\TH:i:sP';
    }
}
