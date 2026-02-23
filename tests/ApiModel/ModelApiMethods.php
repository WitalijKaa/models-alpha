<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAb;
use ModelsAlpha\Examples\Tests\ApiAd;

abstract class ModelApiMethods extends AbstractApi
{
    private const string MODEL_A = '{"someInt":555,"someStr":"Nine"}';
    private const array MODEL_A_ARR = ['someInt' => 555, 'someStr' => 'Nine'];
    private const string MODEL_R = '{"requestInt":13,"requestStr":"the Request"}';

    protected static string $fakeServerResponse = self::MODEL_A;
    protected static string $fakeRequestBody = '"requestStr":"the Request"';
    protected static string $fakeServerMethod = '_';
    protected static string $methodSuffix = '_';

    abstract protected function callApi(ApiAd $request): ApiAb;

    public function testApi(): void
    {
        $request = ApiAd::fromJsonStr(self::MODEL_R);
        $this->assertJsonStringEqualsJsonString(self::MODEL_R, $request->toApiJsonStr());
        $response = $this->callApi($request);
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());
    }

    public function testMethods(): void
    {
        $request = ApiAd::fromJsonStr(self::MODEL_R);

        $apiMethod = 'api' . static::$methodSuffix;
        $response = ApiAb::{$apiMethod}($request->toArray());
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());

        $justMethod = 'just' . static::$methodSuffix;
        $response = ApiAb::{$justMethod}($request->toArray());
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());

        $successMethod = 'success' . static::$methodSuffix;
        $model = new ApiAb();
        $this->assertTrue($model->{$successMethod}($request->toArray()));
        $this->assertTrue($model->isLastResponseSuccess());

        $sendMethod = 'send' . static::$methodSuffix;
        $model = new ApiAb();
        $this->assertSame(self::MODEL_A_ARR, $model->{$sendMethod}($request->toArray()));
        $this->assertTrue($model->isLastResponseSuccess());
    }

    public function testException(): void
    {
        $model = $this->getMockBuilder(ApiAb::class)
            ->onlyMethods(['logAnError', 'apiEndPoint'])
            ->getMock();

        $model->method('apiEndPoint')
            ->willReturn('alpha-a-error');

        $model->expects($this->once())
            ->method('logAnError')
            ->with(
                $this->equalTo('ERROR'),
                $this->logicalAnd(
                    $this->stringStartsWith('Not-Found-API '),
                    $this->stringEndsWith(' 404 http://127.0.0.1:22022/alpha-a-error')
                ),
                $this->equalTo(['action' => 'error'])
            );

        $request = ApiAd::fromJsonStr(self::MODEL_R);

        $sendMethod = 'send' . static::$methodSuffix;
        $model->{$sendMethod}($request->toArray());
        $this->assertFalse($model->isLastResponseSuccess());
        $this->assertSame(404, $model->errorCode());
        $this->assertSame('{"action":"error"}', $model->errorMsg());
    }


}
