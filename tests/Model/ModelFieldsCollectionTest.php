<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\Model;

use ModelsAlpha\Examples\Tests\ModelDa;
use ModelsAlpha\Examples\Tests\ModelDb;
use ModelsAlpha\Examples\Tests\ModelDc;
use ModelsAlpha\Examples\Tests\ModelDe;
use PHPUnit\Framework\TestCase;

final class ModelFieldsCollectionTest extends TestCase
{
    private const string MODEL_D = '{"theItems":[{"someBool":true},{"someInt":404}]}';
    private const string MODEL_D_ADD = '{"theItems":[{"someBool":true},{"someInt":404},{"someInt":300}]}';

    private const string MODEL_D2 = '{"the.Mass_Effect":[{"someBool":true},{"otherInt":404},{"someBool":true,"someInt":555,"otherInt":111},{"someInt":0,"otherInt":2,"otherStr":"Cool"},{"dtoPropStr":"LaLaLa"}]}';
    private const string MODEL_D2_CHECK = '{"the.Mass_Effect":[{"someBool":true},{"otherInt":404},{"someBool":true,"someInt":555,"otherInt":111},{"someInt":0,"otherInt":2,"otherStr":"Cool"},{"dtoPropStr":"LaLaLa"}],"theItems":[]}';

    public function testCollection(): void
    {
        $model = ModelDa::fromJsonStr(self::MODEL_D);
        foreach ($model->theItems as $item) {
            $this->assertInstanceOf(ModelDb::class, $item);
        }
        self::assertEquals(2, $model->theItems->count());
        $this->assertJsonStringEqualsJsonString(self::MODEL_D, $model->toApiJsonStr());

        $add = clone $model->theItems[1];
        $add->someInt = 300;
        $model->theItems->add($add);

        self::assertEquals(3, $model->theItems->count());
        $this->assertJsonStringEqualsJsonString(self::MODEL_D_ADD, $model->toApiJsonStr());
    }

    public function testCollectionVariety(): void
    {
        $model = ModelDa::fromJsonStr(self::MODEL_D2);
        self::assertEquals(5, $model->theMass->count());
        $this->assertInstanceOf(ModelDb::class, $model->theMass[0]);
        $this->assertInstanceOf(ModelDc::class, $model->theMass[1]);
        $this->assertInstanceOf(ModelDb::class, $model->theMass[2]);
        $this->assertInstanceOf(ModelDc::class, $model->theMass[3]);
        $this->assertInstanceOf(ModelDe::class, $model->theMass[4]);
        $this->assertJsonStringEqualsJsonString(self::MODEL_D2_CHECK, $model->toApiJsonStr());
    }

}
