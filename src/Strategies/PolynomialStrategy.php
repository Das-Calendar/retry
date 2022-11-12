<?php
namespace DAS\Retry\Strategies;

class PolynomialStrategy extends AbstractStrategy
{
    public function __construct(protected int $base = 100, public readonly int $degree = 2)
    {
        parent::__construct($base);
    }

    public function getWaitTime(int $attempt): int
    {
        return (int) pow($attempt, $this->degree) * $this->base;
    }
}
