<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAb;
use ModelsAlpha\Examples\Tests\ApiAd;

final class ModelApiPostTest extends AbstractApi
{
    private const string MODEL_A = '{"someInt":555,"someStr":"Nine"}';
    private const array MODEL_A_ARR = ['someInt' => 555, 'someStr' => 'Nine'];
    private const string MODEL_R = '{"requestInt":13,"requestStr":"the Request"}';

    protected static string $fakeServerResponse = self::MODEL_A;
    protected static string $fakeServerMethod = 'POST';
    protected static string $fakeRequestBody = '"requestStr":"the Request"';

    public function testPost(): void
    {
        $request = ApiAd::fromJsonStr(self::MODEL_R);
        $this->assertJsonStringEqualsJsonString(self::MODEL_R, $request->toApiJsonStr());
        $response = ApiAb::apiVsPost($request);
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());
    }

    public function testGet(): void
    {
        $request = ApiAd::fromJsonStr(self::MODEL_R);

        $response = ApiAb::apiPost($request->toArray());
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());

        $response = ApiAb::justPost($request->toArray());
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $response->toApiJsonStr());

        $model = new ApiAb();
        $this->assertTrue($model->successPost($request->toArray()));
        $this->assertTrue($model->isLastResponseSuccess());

        $model = new ApiAb();
        $this->assertSame(self::MODEL_A_ARR, $model->sendPost($request->toArray()));
        $this->assertTrue($model->isLastResponseSuccess());
    }

    public function testException(): void
    {
        $model = $this->getMockBuilder(ApiAb::class)
            ->onlyMethods(['logAnError'])
            ->getMock();

        $model->expects($this->once())
            ->method('logAnError')
            ->with(
                $this->equalTo('ERROR'),
                $this->logicalAnd(
                    $this->stringStartsWith('Not-Found-API '),
                    $this->stringEndsWith(' 404 http://127.0.0.1:22022/alpha-a')
                ),
                $this->equalTo(['action' => 'error'])
            );

        $request = ApiAd::fromJsonStr(self::MODEL_R);
        $request->requestStr = 'Ooops an ERROR :(';

        $model->sendPost($request->toArray());
        $this->assertFalse($model->isLastResponseSuccess());
        $this->assertSame(404, $model->errorCode());
        $this->assertSame('{"action":"error"}', $model->errorMsg());
    }
}
