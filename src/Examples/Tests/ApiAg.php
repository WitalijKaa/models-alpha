<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\PreventToArrayOnNull;

class ApiAg extends ApiAb
{
    public static function apiVsGetRetries(int $retry, int $pause): ?static
    {
        $model = new static();
        $response = $model->withRetries($retry, $pause)->sendGet();
        return $response ? static::fromArray($response) : null;
    }
}