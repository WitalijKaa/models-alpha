<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\Model;

use ModelsAlpha\Examples\Tests\ModelDb;
use ModelsAlpha\Examples\Tests\ModelDc;
use ModelsAlpha\Examples\Tests\ModelDe;
use ModelsAlpha\Examples\Tests\ModelEa;
use ModelsAlpha\Examples\Tests\ModelEb;
use ModelsAlpha\Examples\Tests\ModelEc;
use ModelsAlpha\Examples\Tests\ModelEd;
use ModelsAlpha\Helpers\Collection;
use PHPUnit\Framework\TestCase;

final class ModelFieldsSubmodelTest extends TestCase
{
    private const string MODEL_E = '{"the.Special--":{"someInt":404},"someMod":{"someBool":true,"someInt":555},"otherMod":{"otherInt":2,"otherStr":"Cool"}}';
    private const string MODEL_E2 = '{"the.Special--":{"someInt":404}}';
    private const string MODEL_E3 = '{"the.child-!-":' . self::MODEL_E . ',"someModa":' . self::MODEL_E . '}';
    private const string MODEL_E4 = '{"someBig":' . self::MODEL_E3 . '}';
    private const string MODEL_E4_CHECK = '{"someBig":' . self::MODEL_E3 . ',"someBigClt":[]}';

    private const string MODEL_E5_CHILD = '{"someBig":' . self::MODEL_E3 . ',"the.clt-!-":[{"someBool":false},{"someBool":true,"someInt":555,"otherInt":111},{"someInt":0,"otherInt":2,"otherStr":"Cool"}],"someBigClt":[' . self::MODEL_E3 . ',' . self::MODEL_E3 . ']}';
    private const string MODEL_E5 = '{"subModClt":' . self::MODEL_E5_CHILD . '}';

    public function testSubModels(): void
    {
        $model = ModelEa::fromJsonStr(self::MODEL_E);
        $this->testEa($model);
    }

    private function testEa(ModelEa $model): void
    {
        $this->assertInstanceOf(ModelDb::class, $model->someSpecialMod);
        $this->assertSame(404, $model->someSpecialMod->someInt);
        $this->assertInstanceOf(ModelDb::class, $model->someMod);
        $this->assertSame(555, $model->someMod->someInt);
        $this->assertSame(true, $model->someMod->someBool);
        $this->assertInstanceOf(ModelDc::class, $model->otherMod);
        $this->assertSame(2, $model->otherMod->otherInt);
        $this->assertSame('Cool', $model->otherMod->otherStr);
        $this->assertJsonStringEqualsJsonString(self::MODEL_E, $model->toApiJsonStr());

        $this->expectException(\ErrorException::class);
        $model->protMod;
    }

    public function testSubModelsNotInitilized(): void
    {
        $model = ModelEa::fromJsonStr(self::MODEL_E2);
        $this->assertInstanceOf(ModelDb::class, $model->someSpecialMod);
        $this->assertSame(404, $model->someSpecialMod->someInt);

        $this->expectException(\Error::class);
        $model->someMod;
    }

    public function testSubSubModels(): void
    {
        $model = ModelEb::fromJsonStr(self::MODEL_E3);
        $this->assertInstanceOf(ModelEa::class, $model->someModa);
        $this->assertInstanceOf(ModelEa::class, $model->someSpecialModa);
        $this->testEa($model->someModa);
        $this->testEa($model->someSpecialModa);
        $this->assertJsonStringEqualsJsonString(self::MODEL_E3, $model->toApiJsonStr());
    }

    public function testSubSubSubModels(): void
    {
        $model = ModelEc::fromJsonStr(self::MODEL_E4);
        $this->assertInstanceOf(ModelEb::class, $model->someBig);
        $this->testEa($model->someBig->someModa);
        $this->testEa($model->someBig->someSpecialModa);
        $this->assertJsonStringEqualsJsonString(self::MODEL_E4_CHECK, $model->toApiJsonStr());
    }

    public function testSubCltSubModels(): void
    {
        $model = ModelEd::fromJsonStr(self::MODEL_E5);
        $this->assertInstanceOf(ModelEc::class, $model->subModClt);
        $this->assertInstanceOf(ModelEb::class, $model->subModClt->someBig);
        $this->assertInstanceOf(Collection::class, $model->subModClt->someClt);
        $this->assertInstanceOf(Collection::class, $model->subModClt->someBigClt);

        self::assertEquals(3, $model->subModClt->someClt->count());
        $this->assertInstanceOf(ModelDb::class, $model->subModClt->someClt[0]);
        $this->assertInstanceOf(ModelDb::class, $model->subModClt->someClt[1]);
        $this->assertInstanceOf(ModelDc::class, $model->subModClt->someClt[2]);

        self::assertEquals(2, $model->subModClt->someBigClt->count());
        $this->assertInstanceOf(ModelEb::class, $model->subModClt->someBigClt[0]);
        $this->assertInstanceOf(ModelEb::class, $model->subModClt->someBigClt[1]);

        $this->assertJsonStringEqualsJsonString(self::MODEL_E5, $model->toApiJsonStr());
    }
}
