<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\ApiModel;

use ModelsAlpha\Examples\Tests\ApiAc;
use ModelsAlpha\Exceptions\ApiException;

final class ModelApiGetErrorTest extends AbstractApi
{
    private const string MODEL_A = '{"someInt":808,"someStr":"Eight"}';

    protected static string $fakeServerResponse = self::MODEL_A;
    protected static string $fakeCodeError = '442';

    public function testRequest(): void
    {
        $model = ApiAc::fromJsonStr(self::MODEL_A);
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $model->toApiJsonStr());
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('ApiAc GET API-error #### {"action":"error"}');
        $this->expectExceptionCode(442);
        $model->apiVsGet();
    }

    public function testException(): void
    {
        $model = $this->getMockBuilder(ApiAc::class)
            ->onlyMethods(['logAnError'])
            ->getMock();
        //$model->parseJsonStr(self::MODEL_A);

        $model->expects($this->once())
            ->method('logAnError')
            ->with(
                $this->equalTo('ERROR'),
                $this->logicalAnd(
                    $this->stringStartsWith('Fail-API '),
                    $this->stringEndsWith(' 442 http://127.0.0.1:22022/alpha-error')
                ),
                $this->equalTo(['action' => 'error'])
            );

        $model->sendGet();
        $this->assertFalse($model->isLastResponseSuccess());
        $this->assertSame(442, $model->errorCode());
        $this->assertSame('{"action":"error"}', $model->errorMsg());
    }
}
