<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\HardName;
use ModelsAlpha\Attributes\PreventToArrayOnNull;
use ModelsAlpha\BaseModel;

class ModelCd extends BaseModel
{
    public string $someStr;
    #[HardName('some.stringo.renamed'), PreventToArrayOnNull]
    public ?string $someStrHard;

    public ?string $someStrMayBeNull;
}