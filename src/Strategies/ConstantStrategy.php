<?php
namespace DAS\Retry\Strategies;

/**
 * Class ConstantStrategy
 * @package DAS\Retry\Strategies
 */
class ConstantStrategy extends AbstractStrategy
{
    /**
     * @param int $attempt
     *
     * @return int
     */
    public function getWaitTime(int $attempt): int
    {
        return $this->base;
    }
}
