<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\HardName;
use ModelsAlpha\BaseModel;

#[\Attribute]
class ModelEb extends BaseModel
{
    public ModelEa $someModa;
    #[HardName('the.child-!-')]
    public ModelEa $someSpecialModa;
}