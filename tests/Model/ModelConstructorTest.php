<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\Model;

use ModelsAlpha\Examples\Tests\ModelAa;
use ModelsAlpha\Examples\Tests\ModelAb;
use ModelsAlpha\Examples\Tests\ModelAc;
use PHPUnit\Framework\TestCase;

final class ModelConstructorTest extends TestCase
{
    private const string MODEL_A = '{"inConstructPublic":"Light","inConstructProtected":"Dark"}';
    private const string MODEL_A_NO_CARE = '{"inConstructPublic":"Light"}';
    private const string MODEL_A2 = '{"inConstructPublic":"Light","otherField":"Dark"}';
    private const string MODEL_A3 = '{"inConstructProtected":"Dark"}';
    private const string MODEL_A3_RETURN = '{"inConstructPublic":"NULL","inConstructProtected":"Dark"}';

    public function testConstruct(): void
    {
        $model = ModelAa::fromJsonStr(self::MODEL_A);
        $this->assertSame('Light', $model->inConstructPublic);
        $this->assertSame('Dark', $model->inConstructProtected);
        $this->assertNull($model->getProtectedProp());
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $model->toApiJsonStr());
    }

    public function testConstructNoCareAttrs(): void
    {
        $model = ModelAb::fromJsonStr(self::MODEL_A);
        $this->assertSame('Light', $model->inConstructPublic);
        $this->assertNull($model->getProtectedProp());
        $this->assertJsonStringEqualsJsonString(self::MODEL_A, $model->toApiJsonStr());

        $this->expectException(\ErrorException::class);
        $model->inConstructProtected;
    }

    public function testConstructNoCare(): void
    {
        $model = ModelAc::fromJsonStr(self::MODEL_A);
        $this->assertSame('Light', $model->inConstructPublic);
        $this->assertNull($model->getProtectedProp());
        $this->assertJsonStringEqualsJsonString(self::MODEL_A_NO_CARE, $model->toApiJsonStr());

        $this->expectException(\ErrorException::class);
        $model->inConstructProtected;
    }

    public function testConstructTwo(): void
    {
        $model = ModelAa::fromJsonStr(self::MODEL_A2);
        $this->assertSame('Light', $model->inConstructPublic);
        $this->assertSame('Dark', $model->otherField);
        $this->assertNull($model->getProtectedProp());
        $this->assertJsonStringEqualsJsonString(self::MODEL_A2, $model->toApiJsonStr());
    }

    public function testConstructThree(): void
    {
        $model = ModelAa::fromJsonStr(self::MODEL_A3);
        $this->assertSame('NULL', $model->inConstructPublic);
        $this->assertSame('Dark', $model->inConstructProtected);
        $this->assertNull($model->getProtectedProp());
        $this->assertJsonStringEqualsJsonString(self::MODEL_A3_RETURN, $model->toApiJsonStr());
    }

    public function testConstructCreateMethods(): void
    {
        $modelStr = ModelAa::fromJsonStr(self::MODEL_A);
        $modelArr = ModelAa::fromArray(json_decode(self::MODEL_A, true));
        $modelConStr = new ModelAa();
        $modelConStr->parseJsonStr(self::MODEL_A);
        $modelConArr = new ModelAa();
        $modelConArr->parseArray(json_decode(self::MODEL_A, true));

        $this->assertSame('Light', $modelStr->inConstructPublic);
        $this->assertSame('Light', $modelArr->inConstructPublic);
        $this->assertSame('NULL', $modelConStr->inConstructPublic);
        $this->assertSame('NULL', $modelConArr->inConstructPublic);

        $this->assertNull($modelStr->getProtectedProp());
        $this->assertNull($modelArr->getProtectedProp());
        $this->assertNull($modelConStr->getProtectedProp());
        $this->assertNull($modelConArr->getProtectedProp());

        $this->assertSame($modelStr->toApiJsonStr(), $modelArr->toApiJsonStr());
        $this->assertJsonStringNotEqualsJsonString($modelStr->toApiJsonStr(), $modelConStr->toApiJsonStr());
        $this->assertJsonStringNotEqualsJsonString($modelStr->toApiJsonStr(), $modelConArr->toApiJsonStr());
        $this->assertSame($modelConStr->toApiJsonStr(), $modelConArr->toApiJsonStr());
    }

    public function testConstructClone(): void
    {
        $modelA = ModelAa::fromJsonStr(self::MODEL_A);
        $modelB = ModelAa::fromJsonStr(self::MODEL_A2);
        $modelC = ModelAa::fromJsonStr(self::MODEL_A3);
        $this->assertJsonStringEqualsJsonString($modelA->toApiJsonStr(), (clone $modelA)->toApiJsonStr());
        $this->assertJsonStringEqualsJsonString($modelB->toApiJsonStr(), (clone $modelB)->toApiJsonStr());
        $this->assertJsonStringEqualsJsonString($modelC->toApiJsonStr(), (clone $modelC)->toApiJsonStr());
    }
}
