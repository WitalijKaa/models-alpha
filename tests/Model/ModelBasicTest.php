<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\Model;

use ModelsAlpha\Examples\Tests\ModelAa;
use PHPUnit\Framework\TestCase;

final class ModelBasicTest extends TestCase
{
    private const string MODEL_A = '{"inConstructPublic":"Light","inConstructProtected":"Dark"}';
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
}
