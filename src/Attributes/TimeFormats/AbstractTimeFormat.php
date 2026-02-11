<?php

namespace ModelsAlpha\Attributes\TimeFormats;

abstract class AbstractTimeFormat
{
    abstract public static function invoke(): string|array;
}
