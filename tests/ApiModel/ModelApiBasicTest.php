<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAb;

final class ModelApiBasicTest extends AbstractApi
{
    private const string MODEL_A = '{"someInt":808,"someStr":"Eight"}';
    private const array MODEL_A_ARR = ['someInt' => 808, 'someStr' => 'Eight'];

    protected static string $fakeServerResponse = self::MODEL_A;

    public function testBasic(): void
    {
        $model = ApiAb::fromJsonStr(self::MODEL_A);
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $model->toApiJsonStr());
        $response = $model->apiVsGet();
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());
    }

    public function testGet(): void
    {
        $response = ApiAb::apiGet();
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());

        $response = ApiAb::justGet();
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());

        $model = new ApiAb();
        $this->assertTrue($model->successGet());
        $this->assertTrue($model->isLastResponseSuccess());

        $model = new ApiAb();
        $this->assertSame(self::MODEL_A_ARR, $model->sendGet());
        $this->assertTrue($model->isLastResponseSuccess());
    }
}
