<?php

declare(strict_types=1);

namespace Inspector\Laravel\Tests;

use Inspector\Inspector;

class HelpersTest extends BasicTestCase
{
    public function testGenerateInstance(): void
    {
        $this->assertInstanceOf(Inspector::class, inspector());
    }
}
