<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\HardName;
use ModelsAlpha\Attributes\PreventToArrayOnNull;
use ModelsAlpha\BaseModel;

class ModelBa extends BaseModel
{
    public int $someInt;
    #[HardName('some.int.renamed')]
    public int $someIntHard;
    public ?int $someIntMayBeNull;
    #[PreventToArrayOnNull]
    public ?int $someIntNullHidden;

    protected int $invisibleInt = 13;
    protected int $kindOfAttributeInt = 42;

    public function getProtectedProp(): int
    {
        return $this->invisibleInt;
    }
}