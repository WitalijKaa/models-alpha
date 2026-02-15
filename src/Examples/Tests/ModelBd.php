<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\HardName;
use ModelsAlpha\Attributes\PreventToArrayOnNull;
use ModelsAlpha\BaseModel;

class ModelBd extends BaseModel
{
    public int $someInt;
    #[HardName('some.int.renamed'), PreventToArrayOnNull]
    public ?int $someIntHard;

    public ?int $someIntMayBeNull;
}