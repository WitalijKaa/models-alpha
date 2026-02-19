<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\HardName;
use ModelsAlpha\Attributes\PreventToArrayOnNull;
use ModelsAlpha\BaseModel;
use ModelsAlpha\Helpers\Collection;

class ModelEc extends BaseModel
{
    public ModelEb $someBig;
    #[ModelDb, ModelDc, HardName('the.clt-!-'), PreventToArrayOnNull]
    public Collection $someClt;
    #[ModelEb]
    public Collection $someBigClt;
}