<?php
namespace DAS\Retry\Strategies;

use PHPUnit\Framework\TestCase;

class ConstantStrategyTest extends TestCase
{
    public function testDefaults()
    {
        $s = new ConstantStrategy();

        $this->assertEquals(100, $s->base());
    }

    public function testWaitTimes()
    {
        $s = new ConstantStrategy(100);

        $this->assertEquals(100, $s->getWaitTime(1));
        $this->assertEquals(100, $s->getWaitTime(2));
        $this->assertEquals(100, $s->getWaitTime(3));
    }
}
