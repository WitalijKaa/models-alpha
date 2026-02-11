<?php

namespace ModelsAlpha\Helpers;

use IteratorAggregate;
use Traversable;
use ArrayIterator;

class Collection implements IteratorAggregate
{
    private array $arr = [];

    public function add($item): void
    {
        $this->arr[] = $item;
    }

    public function count(): int
    {
        return count($this->arr);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->arr);
    }
}
