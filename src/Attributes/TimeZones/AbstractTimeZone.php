<?php

namespace ModelsAlpha\Attributes\TimeZones;

abstract class AbstractTimeZone
{
    abstract public static function invoke(): string;
}
