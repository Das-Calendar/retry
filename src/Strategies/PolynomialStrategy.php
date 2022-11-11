<?php
namespace DAS\Retry\Strategies;

/**
 * Class PolynomialStrategy
 * @package DAS\Retry\Strategies
 */
class PolynomialStrategy extends AbstractStrategy
{
    /**
     * @var int
     */
    protected $degree = 2;

    /**
     * PolynomialStrategy constructor.
     *
     * @param int $degree
     * @param int $base
     */
    public function __construct(int $base = null, int $degree = null)
    {
        if(!is_null($degree)) {
            $this->degree = $degree;
        }

        parent::__construct($base);
    }

    /**
     * @param int $attempt
     *
     * @return int
     */
    public function getWaitTime(int $attempt): int
    {
        return (int) pow($attempt, $this->degree) * $this->base;
    }

    /**
     * @return int
     */
    public function getDegree(): int
    {
        return $this->degree;
    }
}
