<?php
namespace DAS\Retry\Strategies;

class LinearStrategy extends AbstractStrategy
{
    public function getWaitTime(int $attempt): int
    {
        return $attempt * $this->base;
    }
}
