<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\PreventToArrayOnNull;
use ModelsAlpha\BaseModel;

#[\Attribute]
class ModelDb extends BaseModel
{
    #[PreventToArrayOnNull]
    public int $someInt;
    #[PreventToArrayOnNull]
    public string $someStr;
    #[PreventToArrayOnNull]
    public float $someFloat;
    #[PreventToArrayOnNull]
    public bool $someBool;
}