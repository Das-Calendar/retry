<?php
namespace DAS\Retry\Strategies;

/**
 * Class ExponentialStrategy
 * @package DAS\Retry\Strategies
 */
class ExponentialStrategy extends AbstractStrategy
{
    /**
     * @param int $attempt
     *
     * @return int
     */
    public function getWaitTime(int $attempt): int
    {
        return (int) ($attempt == 1
            ? $this->base
            : pow(2, $attempt) * $this->base
        );
    }
}
