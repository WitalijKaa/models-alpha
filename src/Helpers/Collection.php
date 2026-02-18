<?php

namespace ModelsAlpha\Helpers;

use ArrayAccess;
use IteratorAggregate;
use Traversable;
use ArrayIterator;

class Collection implements IteratorAggregate, ArrayAccess
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

    public function offsetExists(mixed $offset): bool { return isset($this->arr[$offset]); }
    public function offsetGet(mixed $offset): mixed { return $this->arr[$offset]; }
    public function offsetSet(mixed $offset, mixed $value): void { is_null($offset) ? ($this->arr[] = $value) : $this->arr[$offset] = $value; }
    public function offsetUnset(mixed $offset): void { unset($this->arr[$offset]); }
}
