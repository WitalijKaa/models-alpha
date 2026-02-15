<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\HardName;
use ModelsAlpha\Attributes\PreventToArrayOnNull;
use ModelsAlpha\BaseModel;

class ModelCa extends BaseModel
{
    public string $someStr;
    #[HardName('some.stringo.renamed')]
    public string $someStrHard;
    public ?string $someStrMayBeNull;
    #[PreventToArrayOnNull]
    public ?string $someStrNullHidden;

    protected string $invisibleStr = "uCantSeeMe";
    protected string $kindOfAttributeStr = "iAmNotAnAttr";

    public function getProtectedProp(): string
    {
        return $this->invisibleStr . '_' . $this->kindOfAttributeStr;
    }
}