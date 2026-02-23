<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAe;

final class ModelApiQueryTest extends AbstractApi
{
    private const string MODEL_A = '{"someInt":808,"someStr":"Eight"}';

    protected static string $fakeQueryParams = 'q-param=something&more=1234';
    protected static string $fakeServerResponse = self::MODEL_A;

    public function testQuery(): void
    {
        $response = ApiAe::apiGet();
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());
    }
}
