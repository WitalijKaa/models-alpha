<?php

declare(strict_types=1);

namespace ModelsAlpha\Tests\Helpers;

use ModelsAlpha\Helpers\Str;
use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    public function testSnakeCase(): void
    {
        $this->assertSame('some_whatever_value', Str::snake('some_whatever_value'));
        $this->assertSame('some_whatever_value', Str::snake('SomeWhateverValue'));
        $this->assertSame('some_whatever_value', Str::snake('Some Whatever Value'));
        $this->assertSame('some_whatever_value22', Str::snake('Some Whatever Value 22'));
    }

    public function testCamelCase(): void
    {
        $this->assertSame('someWhateverValue', Str::camel('SomeWhateverValue'));
        $this->assertSame('someWhateverValue', Str::camel('some_whatever_value'));
        $this->assertSame('someWhateverValue', Str::camel('Some Whatever Value'));
        $this->assertSame('someWhateverValue22', Str::camel('Some Whatever Value 22'));
    }
}
