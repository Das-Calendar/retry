<?php
namespace DAS\Retry\Strategies;


abstract class AbstractStrategy  implements IStrategy
{
    protected $jitter = true;

    public function __construct(protected int $base = 100)
    {
    }

    abstract public function getWaitTime(int $attempt): int;

    public function __invoke(int $attempt): int
    {
        return $this->getWaitTime($attempt);
    }

    public function base(): int{
        return $this->base;
    }
}