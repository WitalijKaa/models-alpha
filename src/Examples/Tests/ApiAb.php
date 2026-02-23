<?php

namespace ModelsAlpha\Examples\Tests;

use ModelsAlpha\Attributes\PreventToArrayOnNull;

class ApiAb extends ApiAa
{
    public function apiEndPoint(): string { return 'alpha-a'; }

    #[PreventToArrayOnNull]
    public string $someStr;
    #[PreventToArrayOnNull]
    public int $someInt;

    public static function apiVsGet(): static
    {
        return static::apiGet();
    }

    public static function apiVsPost(ApiAd $request): static
    {
        return static::apiPost($request->toArray());
    }

    public static function apiVsPut(ApiAd $request): static
    {
        return static::apiPut($request->toArray());
    }

    public static function apiVsPatch(ApiAd $request): static
    {
        return static::apiPatch($request->toArray());
    }
}