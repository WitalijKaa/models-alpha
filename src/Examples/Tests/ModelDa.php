<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\HardName;
use ModelsAlpha\Attributes\PreventToArrayOnNull;
use ModelsAlpha\BaseModel;
use ModelsAlpha\Helpers\Collection;

class ModelDa extends BaseModel
{
    #[ModelDb, ModelDc, ModelDe, HardName('the.Mass_Effect'), PreventToArrayOnNull]
    public Collection $theMass;
    #[ModelDb]
    public Collection $theItems;
}