<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\HardName;
use ModelsAlpha\BaseModel;

class ModelEa extends BaseModel
{
    public ModelDb $someMod;
    #[HardName('the.Special--')]
    public ModelDb $someSpecialMod;
    public ModelDc $otherMod;

    protected ModelDb $protMod;
}