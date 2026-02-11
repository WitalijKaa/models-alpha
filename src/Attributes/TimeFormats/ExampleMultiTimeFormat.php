<?php

namespace ModelsAlpha\Attributes\TimeFormats;

#[\Attribute]
class ExampleMultiTimeFormat extends AbstractTimeFormat
{
    public static function invoke(): string|array
    {
        // first we will try 'Y-m-d H:i:s'
        return ['Y-m-d H:i:s', 'Y-m-d'];
    }
}
