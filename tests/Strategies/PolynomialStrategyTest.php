<?php
namespace DAS\Retry\Strategies;

use PHPUnit\Framework\TestCase;

class PolynomialStrategyTest extends TestCase
{
    public function testDefaults()
    {
        $s = new PolynomialStrategy();

        $this->assertEquals(100, $s->base());
        $this->assertEquals(2, $s->degree);
    }

    public function testWaitTimes()
    {
        $s = new PolynomialStrategy(200, 2);

        $this->assertEquals(200, $s->getWaitTime(1));
        $this->assertEquals(800, $s->getWaitTime(2));
        $this->assertEquals(1800, $s->getWaitTime(3));
        $this->assertEquals(3200, $s->getWaitTime(4));
        $this->assertEquals(5000, $s->getWaitTime(5));
        $this->assertEquals(7200, $s->getWaitTime(6));
        $this->assertEquals(9800, $s->getWaitTime(7));
        $this->assertEquals(12800, $s->getWaitTime(8));
    }
}
