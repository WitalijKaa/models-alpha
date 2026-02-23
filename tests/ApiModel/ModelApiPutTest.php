<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAb;
use ModelsAlpha\Examples\Tests\ApiAd;

final class ModelApiPutTest extends ModelApiMethods
{
    protected static string $fakeServerMethod = 'PUT';
    protected static string $methodSuffix = 'Put';

    protected function callApi(ApiAd $request): ApiAb
    {
        return ApiAb::apiVsPut($request);
    }
}
