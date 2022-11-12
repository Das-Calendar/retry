<?php
namespace DAS\Retry\Strategies;

class ConstantStrategy extends AbstractStrategy
{
    public function getWaitTime(int $attempt): int
    {
        return $this->base;
    }
}
