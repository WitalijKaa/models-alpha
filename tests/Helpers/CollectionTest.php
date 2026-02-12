<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\Helpers;

use ModelsAlpha\Helpers\Collection;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    public function testAddCount(): void
    {
        $collection = new Collection();

        $collection->add('first');
        $collection->add(2);

        $this->assertSame(2, $collection->count());
    }

    public function testIteration(): void
    {
        $collection = new Collection();

        $collection->add('first');
        $collection->add('second');

        $this->assertSame(
            ['first', 'second'],
            iterator_to_array($collection)
        );

        foreach ($collection as $item) {
            $this->assertSame('first', $item);
            break;
        }
    }
}
