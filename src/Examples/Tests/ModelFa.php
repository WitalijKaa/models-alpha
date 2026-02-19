<?php

namespace ModelsAlpha\Examples\Tests;

use Carbon\Carbon;
use ModelsAlpha\Attributes\HardName;
use ModelsAlpha\Attributes\TimeFormats\YmdHisMultiTimeFormat;
use ModelsAlpha\Attributes\TimeFormats\YmdHisTimeFormat;
use ModelsAlpha\Attributes\TimeFormats\YmdTHisPTimeFormat;
use ModelsAlpha\Attributes\TimeFormats\YmdTimeFormat;
use ModelsAlpha\Attributes\TimeZones\CaliforniaTimeZone;
use ModelsAlpha\BaseModel;

class ModelFa extends BaseModel
{
    #[YmdTimeFormat]
    public Carbon $ymd;

    #[YmdTimeFormat, HardName('y.m.d..')]
    public Carbon $ymdHard;

    #[YmdHisTimeFormat]
    public Carbon $ymdhis;
    #[YmdHisMultiTimeFormat]
    public Carbon $ymdhisMu;
    #[YmdHisMultiTimeFormat]
    public Carbon $ymdhisMult;

    #[YmdTHisPTimeFormat, CaliforniaTimeZone]
    public Carbon $ymdthis;
}