<?php
namespace DAS\Retry\Strategies;

/**
 * Class LinearStrategy
 * @package DAS\Retry\Strategies
 */
class LinearStrategy extends AbstractStrategy
{
    /**
     * @param int $attempt
     *
     * @return int
     */
    public function getWaitTime(int $attempt): int
    {
        return $attempt * $this->base;
    }
}
