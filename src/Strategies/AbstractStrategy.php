<?php
namespace DAS\Retry\Strategies;

/**
 * Class AbstractStrategy
 * @package DAS\Retry\Strategies
 */
abstract class AbstractStrategy
{
    /**
     * Base wait time in ms
     * @var int
     */
    protected $base = 100;

    /**
     * @var bool
     */
    protected $jitter = true;

    /**
     * AbstractStrategy constructor.
     *
     * @param int $base
     */
    public function __construct($base = null)
    {
        if(is_int($base)) {
            $this->base = $base;
        }
    }

    /**
     * @param int $attempt
     *
     * @return int      Time to wait in ms
     */
    abstract public function getWaitTime(int $attempt): int;

    /**
     * @param int $attempt
     *
     * @return int
     */
    public function __invoke(int $attempt): int
    {
        return $this->getWaitTime($attempt);
    }

    /**
     * @return int
     */
    public function getBase(): int
    {
        return $this->base;
    }
}
