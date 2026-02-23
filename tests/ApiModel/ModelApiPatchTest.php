<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAb;
use ModelsAlpha\Examples\Tests\ApiAd;

final class ModelApiPatchTest extends ModelApiMethods
{
    protected static string $fakeServerMethod = 'PATCH';
    protected static string $methodSuffix = 'Patch';

    protected function callApi(ApiAd $request): ApiAb
    {
        return ApiAb::apiVsPatch($request);
    }
}
