<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\Model;

use Carbon\Carbon;
use ModelsAlpha\Attributes\TimeZones\CaliforniaTimeZone;
use ModelsAlpha\Attributes\TimeZones\UtcTimeZone;
use ModelsAlpha\Examples\Tests\ModelFa;
use PHPUnit\Framework\TestCase;

final class ModelFieldsTimeTest extends TestCase
{
    private const string MODEL_F = '{"ymd":"2010-11-21","y.m.d..":"2010-11-22","ymdhis":"2010-11-23 23:24:25","ymdhisMu":"2010-11-24 23:24:25","ymdhisMult":"2010-11-25","ymdthis":"2010-11-26T02:24:25+04:00"}';
    private const string MODEL_F_CHECK = '{"ymd":"2010-11-21","y.m.d..":"2010-11-22","ymdhis":"2010-11-23 23:24:25","ymdhisMu":"2010-11-24 23:24:25","ymdhisMult":"2010-11-25 00:00:00","ymdthis":"2010-11-25T14:24:25-08:00"}';

    public function testYmd(): void
    {

        $model = ModelFa::fromJsonStr(self::MODEL_F);

        $this->assertInstanceOf(Carbon::class, $model->ymd);
        $this->assertSame(2010, $model->ymd->year);
        $this->assertSame(11, $model->ymd->month);
        $this->assertSame(21, $model->ymd->day);
        $this->assertSame(0, $model->ymd->hour);
        $this->assertSame(0, $model->ymd->minute);
        $this->assertSame(0, $model->ymd->second);
        $model->ymd->setTimezone(CaliforniaTimeZone::invoke());
        $this->assertSame(2010, $model->ymd->year);
        $this->assertSame(11, $model->ymd->month);
        $this->assertSame(20, $model->ymd->day);
        $this->assertSame(16, $model->ymd->hour);
        $this->assertSame(0, $model->ymd->minute);
        $this->assertSame(0, $model->ymd->second);

        $this->assertInstanceOf(Carbon::class, $model->ymdHard);
        $this->assertSame(2010, $model->ymdHard->year);
        $this->assertSame(11, $model->ymdHard->month);
        $this->assertSame(22, $model->ymdHard->day);
        $this->assertSame(0, $model->ymdHard->hour);
        $this->assertSame(0, $model->ymdHard->minute);
        $this->assertSame(0, $model->ymdHard->second);

        $this->assertInstanceOf(Carbon::class, $model->ymdhis);
        $this->assertSame(2010, $model->ymdhis->year);
        $this->assertSame(11, $model->ymdhis->month);
        $this->assertSame(23, $model->ymdhis->day);
        $this->assertSame(23, $model->ymdhis->hour);
        $this->assertSame(24, $model->ymdhis->minute);
        $this->assertSame(25, $model->ymdhis->second);

        $this->assertInstanceOf(Carbon::class, $model->ymdhisMu);
        $this->assertSame(2010, $model->ymdhisMu->year);
        $this->assertSame(11, $model->ymdhisMu->month);
        $this->assertSame(24, $model->ymdhisMu->day);
        $this->assertSame(23, $model->ymdhisMu->hour);
        $this->assertSame(24, $model->ymdhisMu->minute);
        $this->assertSame(25, $model->ymdhisMu->second);

        $this->assertInstanceOf(Carbon::class, $model->ymdhisMult);
        $this->assertSame(2010, $model->ymdhisMult->year);
        $this->assertSame(11, $model->ymdhisMult->month);
        $this->assertSame(25, $model->ymdhisMult->day);
        $this->assertSame(0, $model->ymdhisMult->hour);
        $this->assertSame(0, $model->ymdhisMult->minute);
        $this->assertSame(0, $model->ymdhisMult->second);

        // "ymdthis":"2010-11-26T02:24:25+04:00" -> UTC -25 22:24 -> "ymdthis":"2010-11-25T18:24:25-08:00"
        $this->assertInstanceOf(Carbon::class, $model->ymdthis);
        $this->assertSame(2010, $model->ymdthis->year);
        $this->assertSame(11, $model->ymdthis->month);
        $this->assertSame(26, $model->ymdthis->day);
        $this->assertSame(2, $model->ymdthis->hour);
        $this->assertSame(24, $model->ymdthis->minute);
        $this->assertSame(25, $model->ymdthis->second);
        $model->ymdthis->setTimezone(UtcTimeZone::invoke());
        $this->assertSame(2010, $model->ymdthis->year);
        $this->assertSame(11, $model->ymdthis->month);
        $this->assertSame(25, $model->ymdthis->day);
        $this->assertSame(22, $model->ymdthis->hour);
        $this->assertSame(24, $model->ymdthis->minute);
        $this->assertSame(25, $model->ymdthis->second);

        $this->assertJsonStringEqualsJsonString(self::MODEL_F_CHECK, $model->toApiJsonStr());
    }
}
