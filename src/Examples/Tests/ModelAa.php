<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\BaseModel;

class ModelAa extends BaseModel
{
    public function __construct(public string $inConstructPublic = 'NULL', protected ?string $inConstructProtected = null)
    {
    }

    public function getProtectedProp(): ?string
    {
        return $this->inConstructProtected;
    }
}