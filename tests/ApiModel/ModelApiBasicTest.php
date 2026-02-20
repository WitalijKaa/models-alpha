<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAb;

final class ModelApiBasicTest extends AbstractApi
{
    private const string MODEL_A = '{"someInt":808,"someStr":"Eight"}';

    public static function setUpBeforeClass(): void
    {
        static::$fakeServerResponse = self::MODEL_A;
        parent::setUpBeforeClass();
    }

    public function testGet(): void
    {
        $model = ApiAb::fromJsonStr(self::MODEL_A);
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $model->toApiJsonStr());
        $response = $model->api();
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());
    }
}
