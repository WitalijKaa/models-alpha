<?php

namespace ModelsAlpha\Core;

readonly class CurlOptionsDto
{
    public function __construct(
        public array $headers = [],
        public ?string $query = null,
        public array|string|null $body = null,
        public float $timeout = 0.0,
    ) { }
}