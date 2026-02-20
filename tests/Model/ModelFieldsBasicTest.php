<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\Model;

use ModelsAlpha\Examples\Tests\ModelBa;
use ModelsAlpha\Examples\Tests\ModelBb;
use ModelsAlpha\Examples\Tests\ModelBc;
use ModelsAlpha\Examples\Tests\ModelBd;
use ModelsAlpha\Examples\Tests\ModelCa;
use ModelsAlpha\Examples\Tests\ModelCb;
use ModelsAlpha\Examples\Tests\ModelCc;
use ModelsAlpha\Examples\Tests\ModelCd;
use ModelsAlpha\Examples\Tests\ModelCe;
use PHPUnit\Framework\TestCase;

final class ModelFieldsBasicTest extends TestCase
{
    private const string MODEL_B = '{"someInt":11,"kindOfAttributeInt":111}';
    private const string MODEL_B_CHECK = '{"someInt":11,"kindOfAttributeInt":111,"someIntMayBeNull":null,"some.int.renamed":null}';
    private const string MODEL_B_NO_CARE = '{"someInt":11,"someIntMayBeNull":null,"some.int.renamed":null}';
    private const string MODEL_B2 = '{"someInt":11,"kindOfAttributeInt":111,"someIntNullHidden":123,"some.int.renamed":555}';
    private const string MODEL_B2_CHECK = '{"someInt":11,"kindOfAttributeInt":111,"someIntNullHidden":123,"someIntMayBeNull":null,"some.int.renamed":555}';
    private const string MODEL_B3 = '{"someInt":11,"someJustInt":123}';
    private const string MODEL_B3_CHECK = '{"someInt":11,"someJustInt":123,"someIntMayBeNull":null}';

    private const string MODEL_C = '{"someStr":"aNote","kindOfAttributeStr":"aGlitch"}';
    private const string MODEL_C_CHECK = '{"someStr":"aNote","kindOfAttributeStr":"aGlitch","someStrMayBeNull":null,"some.stringo.renamed":null}';
    private const string MODEL_C_NO_CARE = '{"someStr":"aNote","someStrMayBeNull":null,"some.stringo.renamed":null}';
    private const string MODEL_C2 = '{"someStr":"aNote","kindOfAttributeStr":"aGlitch","someStrNullHidden":"aSecret","some.stringo.renamed":"NiceVal"}';
    private const string MODEL_C2_CHECK = '{"someStr":"aNote","kindOfAttributeStr":"aGlitch","someStrNullHidden":"aSecret","someStrMayBeNull":null,"some.stringo.renamed":"NiceVal"}';
    private const string MODEL_C3 = '{"someStr":"aNote","someJustStr":123}';
    private const string MODEL_C3_CHECK = '{"someStr":"aNote","someJustStr":123,"someStrMayBeNull":null}';

    private const string MODEL_C4 = '{}';
    private const string MODEL_C4_CHECK = '{"someStr":null,"someStrMayBeNull":null,"some.stringo.renamed":null}';

    private const string MODEL_P = '{"someInt":"404","someStr":500,"someFloat":12.34567,"someBool":0}';
    private const string MODEL_P_CHECK = '{"someInt":404,"someStr":"500","someFloat":12.34567,"someBool":false}';

    public function testInt(): void
    {
        $model = ModelBa::fromJsonStr(self::MODEL_B);
        $this->assertSame(11, $model->someInt);
        $this->assertNull($model->someIntMayBeNull);
        $this->assertNull($model->someIntNullHidden);
        $this->assertJsonStringEqualsJsonString(self::MODEL_B_CHECK, $model->toApiJsonStr());
        $this->assertSame(55, $model->getProtectedProp());

        $this->expectException(\ErrorException::class);
        $model->kindOfAttributeInt;
    }

    public function testIntHardOrHidden(): void
    {
        $model = ModelBa::fromJsonStr(self::MODEL_B2);
        $this->assertSame(11, $model->someInt);
        $this->assertNull($model->someIntMayBeNull);
        $this->assertSame(123, $model->someIntNullHidden);
        $this->assertSame(555, $model->someIntHard);
        $this->assertJsonStringEqualsJsonString(self::MODEL_B2_CHECK, $model->toApiJsonStr());
        $this->assertSame(55, $model->getProtectedProp());

        $this->expectException(\ErrorException::class);
        $model->kindOfAttributeInt;
    }

    public function testIntHardAndHidden(): void
    {
        $model = ModelBd::fromJsonStr(self::MODEL_B2);
        $this->assertSame(11, $model->someInt);
        $this->assertSame(555, $model->someIntHard);
        $this->assertJsonStringEqualsJsonString(self::MODEL_B2_CHECK, $model->toApiJsonStr());

        $model = ModelBd::fromJsonStr(self::MODEL_B3);
        $this->assertSame(11, $model->someInt);
        $this->assertNull($model->someIntHard);
        $this->assertJsonStringEqualsJsonString(self::MODEL_B3_CHECK, $model->toApiJsonStr());
    }

    public function testIntOriginalAsAttr(): void
    {
        $model = ModelBb::fromJsonStr(self::MODEL_B);
        $this->assertSame(11, $model->someInt);
        $this->assertSame(111, $model->kindOfAttributeInt);
        $this->assertJsonStringEqualsJsonString(self::MODEL_B_CHECK, $model->toApiJsonStr());
        $this->assertSame(55, $model->getProtectedProp());
    }

    public function testIntNoCare(): void
    {
        $model = ModelBc::fromJsonStr(self::MODEL_B);
        $this->assertSame(11, $model->someInt);
        $this->assertJsonStringEqualsJsonString(self::MODEL_B_NO_CARE, $model->toApiJsonStr());
        $this->assertSame(55, $model->getProtectedProp());

        $this->expectException(\ErrorException::class);
        $model->kindOfAttributeInt;
    }

    public function testIntCreateMethods(): void
    {
        $modelStr = ModelBa::fromJsonStr(self::MODEL_B);
        $modelArr = ModelBa::fromArray(json_decode(self::MODEL_B, true));

        $this->assertSame(11, $modelStr->someInt);
        $this->assertSame(11, $modelArr->someInt);
        $this->assertSame(55, $modelStr->getProtectedProp());
        $this->assertSame(55, $modelArr->getProtectedProp());

        $this->assertSame($modelStr->toApiJsonStr(), $modelArr->toApiJsonStr());
    }

    public function testIntClone(): void
    {
        $modelA = ModelBa::fromJsonStr(self::MODEL_B);
        $this->assertJsonStringEqualsJsonString($modelA->toApiJsonStr(), (clone $modelA)->toApiJsonStr());
    }

    public function testString(): void
    {
        $model = ModelCa::fromJsonStr(self::MODEL_C);
        $this->assertSame('aNote', $model->someStr);
        $this->assertNull($model->someStrMayBeNull);
        $this->assertNull($model->someStrNullHidden);
        $this->assertJsonStringEqualsJsonString(self::MODEL_C_CHECK, $model->toApiJsonStr());
        $this->assertSame('uCantSeeMe_iAmNotAnAttr', $model->getProtectedProp());

        $this->expectException(\ErrorException::class);
        $model->kindOfAttributeStr;
    }

    public function testNothing(): void
    {
        $model = ModelCa::fromJsonStr(self::MODEL_C4);
        $this->assertJsonStringEqualsJsonString(self::MODEL_C4_CHECK, $model->toApiJsonStr());

        $this->expectException(\Error::class);
        $model->someStr;
    }

    public function testStringHardOrHidden(): void
    {
        $model = ModelCa::fromJsonStr(self::MODEL_C2);
        $this->assertSame('aNote', $model->someStr);
        $this->assertNull($model->someStrMayBeNull);
        $this->assertSame('aSecret', $model->someStrNullHidden);
        $this->assertSame('NiceVal', $model->someStrHard);
        $this->assertJsonStringEqualsJsonString(self::MODEL_C2_CHECK, $model->toApiJsonStr());
        $this->assertSame('uCantSeeMe_iAmNotAnAttr', $model->getProtectedProp());

        $this->expectException(\ErrorException::class);
        $model->kindOfAttributeStr;
    }

    public function testStringHardAndHidden(): void
    {
        $model = ModelCd::fromJsonStr(self::MODEL_C2);
        $this->assertSame('aNote', $model->someStr);
        $this->assertSame('NiceVal', $model->someStrHard);
        $this->assertJsonStringEqualsJsonString(self::MODEL_C2_CHECK, $model->toApiJsonStr());

        $model = ModelCd::fromJsonStr(self::MODEL_C3);
        $this->assertSame('aNote', $model->someStr);
        $this->assertNull($model->someStrHard);
        $this->assertJsonStringEqualsJsonString(self::MODEL_C3_CHECK, $model->toApiJsonStr());
    }

    public function testStringOriginalAsAttr(): void
    {
        $model = ModelCb::fromJsonStr(self::MODEL_C);
        $this->assertSame('aNote', $model->someStr);
        $this->assertSame('aGlitch', $model->kindOfAttributeStr);
        $this->assertJsonStringEqualsJsonString(self::MODEL_C_CHECK, $model->toApiJsonStr());
        $this->assertSame('uCantSeeMe_iAmNotAnAttr', $model->getProtectedProp());
    }

    public function testStringNoCare(): void
    {
        $model = ModelCc::fromJsonStr(self::MODEL_C);
        $this->assertSame('aNote', $model->someStr);
        $this->assertJsonStringEqualsJsonString(self::MODEL_C_NO_CARE, $model->toApiJsonStr());
        $this->assertSame('uCantSeeMe_iAmNotAnAttr', $model->getProtectedProp());

        $this->expectException(\ErrorException::class);
        $model->kindOfAttributeStr;
    }

    public function testStringCreateMethods(): void
    {
        $modelStr = ModelCa::fromJsonStr(self::MODEL_C);
        $modelArr = ModelCa::fromArray(json_decode(self::MODEL_C, true));

        $this->assertSame('aNote', $modelStr->someStr);
        $this->assertSame('aNote', $modelArr->someStr);
        $this->assertSame('uCantSeeMe_iAmNotAnAttr', $modelStr->getProtectedProp());
        $this->assertSame('uCantSeeMe_iAmNotAnAttr', $modelArr->getProtectedProp());

        $this->assertSame($modelStr->toApiJsonStr(), $modelArr->toApiJsonStr());
    }

    public function testStringClone(): void
    {
        $modelA = ModelCa::fromJsonStr(self::MODEL_C);
        $this->assertJsonStringEqualsJsonString($modelA->toApiJsonStr(), (clone $modelA)->toApiJsonStr());
    }

    public function testPrimitives(): void
    {
        $model = ModelCe::fromJsonStr(self::MODEL_P);
        $this->assertSame(404, $model->someInt);
        $this->assertSame('500', $model->someStr);
        $this->assertSame(12.34567, $model->someFloat);
        $this->assertSame(false, $model->someBool);
        $this->assertJsonStringEqualsJsonString(self::MODEL_P_CHECK, $model->toApiJsonStr());
    }
}
