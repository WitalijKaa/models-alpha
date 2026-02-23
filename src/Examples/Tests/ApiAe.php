<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\PreventToArrayOnNull;

class ApiAe extends ApiAb
{
    public function forcedQuery(): array
    {
        return [
            'q-param' => 'something',
            'more' => 1234,
        ];
    }
}