<?php
namespace DAS\Retry\Strategies;

class ExponentialStrategy extends AbstractStrategy
{
    public function getWaitTime(int $attempt): int
    {
        return (int) ($attempt == 1
            ? $this->base
            : pow(2, $attempt) * $this->base
        );
    }
}
