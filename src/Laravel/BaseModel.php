<?php

namespace ModelsAlpha\Laravel;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

abstract class BaseModel extends \ModelsAlpha\BaseModel implements Arrayable {

    protected static array $collectionClasses = [
        'Illuminate\Support\Collection',
        'Illuminate\Database\Eloquent\Collection',
    ];

    protected static array $carbonClasses = [
        Carbon::class,
        'Illuminate\Support\Carbon',
    ];


}
