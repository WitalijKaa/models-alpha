<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\PreventToArrayOnNull;
use ModelsAlpha\BaseModel;

class ApiAd extends BaseModel
{
    #[PreventToArrayOnNull]
    public string $requestStr;
    #[PreventToArrayOnNull]
    public int $requestInt;
}