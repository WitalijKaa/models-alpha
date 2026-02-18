<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\PreventToArrayOnNull;
use ModelsAlpha\BaseModel;

#[\Attribute]
class ModelDc extends BaseModel
{
    #[PreventToArrayOnNull]
    public int $otherInt;
    #[PreventToArrayOnNull]
    public string $otherStr;
    #[PreventToArrayOnNull]
    public float $otherFloat;
    #[PreventToArrayOnNull]
    public bool $otherBool;
}