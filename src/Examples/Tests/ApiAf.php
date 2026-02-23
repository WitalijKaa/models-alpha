<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\PreventToArrayOnNull;

class ApiAf extends ApiAb
{
    public static function apiVsGetTimeout(float $timeout): ?static
    {
        $model = new static();
        $response = $model->withTimeout($timeout)->sendGet();
        return $response ? static::fromArray($response) : null;
    }

}