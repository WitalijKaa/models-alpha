<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAf;

final class ModelApiTimeoutTest extends AbstractApi
{
    private const string MODEL_A = '{"someInt":808,"someStr":"Eight"}';

    protected static string $fakeServerResponse = self::MODEL_A;
    protected static float $fakeTimeout = 1.2;

    public function testTimeout(): void
    {
        $response = ApiAf::apiVsGetTimeout(1.5);
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());

        $this->assertNull(ApiAf::apiVsGetTimeout(1.1));
    }
}
