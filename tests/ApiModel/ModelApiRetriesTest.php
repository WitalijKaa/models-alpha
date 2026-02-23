<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAg;

final class ModelApiRetriesTest extends AbstractApi
{
    private const string MODEL_A = '{"someInt":808,"someStr":"Eight"}';

    protected static string $fakeServerResponse = self::MODEL_A;
    protected static float $fakeRetries = 2;

    public function testRetry(): void
    {
        static::clearRetries();
        $response = ApiAg::apiVsGetRetries(2, 1);
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());

        static::clearRetries();
        $this->assertNull(ApiAg::apiVsGetRetries(1, 1));
    }
}
