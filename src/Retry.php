<?php
namespace DAS\Retry;

use DAS\Retry\Strategies\ConstantStrategy;
use DAS\Retry\Strategies\ExponentialStrategy;
use DAS\Retry\Strategies\IStrategy;
use DAS\Retry\Strategies\LinearStrategy;
use DAS\Retry\Strategies\PolynomialStrategy;
use Exception;

class Retry
{

    private const DefaultMaxAttempts = 5;
    private const DefaultJitterEnabled = false;

    protected IStrategy $strategy;

    protected int $maxAttempts;
    protected ?int $waitCap = null;
    protected bool $useJitter = false;
    protected array $exceptions = [];
    protected $decider = null;
    protected $errorHandler = null;

    public static function Default(): self{
        $iRetry = new self();
        $iRetry->setMaxAttempts(self::DefaultMaxAttempts);
        $iRetry->setStrategy(new PolynomialStrategy());
        $iRetry->setJitter(self::DefaultJitterEnabled);
        $iRetry->setDecider($iRetry->getDefaultDecider());
        return $iRetry;
    }

    public static function ConstantStrategy(int $maxAttempt, int $waitCap = null, bool $useJitter = null, callable $decider = null): self{
        $iRetry = new self();
        $iRetry->setMaxAttempts(self::DefaultMaxAttempts);
        $iRetry->setStrategy(new ConstantStrategy());
        $iRetry->setJitter(self::DefaultMaxAttempts);
        $iRetry->setDecider($iRetry->getDefaultDecider());
        return $iRetry;
    }

    public static function LinearStrategy(int $maxAttempt, int $waitCap = null, bool $useJitter = null, callable $decider = null): self{
        $iRetry = new self();
        $iRetry->setMaxAttempts(self::DefaultMaxAttempts);
        $iRetry->setStrategy(new LinearStrategy());
        $iRetry->setJitter(self::DefaultMaxAttempts);
        $iRetry->setDecider($iRetry->getDefaultDecider());
        return $iRetry;
    }

    public static function PolynomialStrategy(int $maxAttempt, int $waitCap = null, bool $useJitter = null, callable $decider = null): self{
        $iRetry = new self();
        $iRetry->setMaxAttempts(self::DefaultMaxAttempts);
        $iRetry->setStrategy(new PolynomialStrategy());
        $iRetry->setJitter(self::DefaultMaxAttempts);
        $iRetry->setDecider($iRetry->getDefaultDecider());
        return $iRetry;
    }

    public static function ExponentialStrategy(int $maxAttempt, int $waitCap = null, bool $useJitter = null, callable $decider = null): self{
        $iRetry = new self();
        $iRetry->setMaxAttempts(self::DefaultMaxAttempts);
        $iRetry->setStrategy(new ExponentialStrategy());
        $iRetry->setJitter(self::DefaultMaxAttempts);
        $iRetry->setDecider($iRetry->getDefaultDecider());
        return $iRetry;
    }

    public function setMaxAttempts(int $attempts): self
    {
        $this->maxAttempts = $attempts;
        
         return $this;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function setWaitCap(?int $cap)
    {
        $this->waitCap = $cap;

        return $this;
    }

    public function getWaitCap(): int|null
    {
        return $this->waitCap;
    }

    public function setJitter(bool $useJitter): self
    {
        $this->useJitter = $useJitter;

        return $this;
    }

    public function enableJitter(): self
    {
        $this->setJitter(true);

        return $this;
    }

    public function disableJitter(): self
    {
        $this->setJitter(false);

        return $this;
    }

    public function jitterEnabled(): bool
    {
        return $this->useJitter;
    }

    public function getStrategy(): callable
    {
        return $this->strategy;
    }

    public function setStrategy(IStrategy $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    public function run(callable $callback): mixed
    {
        $attempt = 0;
        $try = true;

        while ($try) {

            $result = null;
            $exception = null;

            $this->wait($attempt);
            try {
                $result = call_user_func($callback);
            } catch (\Throwable $e) {
                if ($e instanceof \Error) {
                    $e = new Exception($e->getMessage(), $e->getCode(), $e);
                }
                $this->exceptions[] = $e;
                $exception = $e;
            } catch (Exception $e) {
                $this->exceptions[] = $e;
                $exception = $e;
            }
            $exception = call_user_func($this->decider(), ++$attempt, $this->getMaxAttempts(), $result, $exception);

            if($try && isset($this->errorHandler)) {
                call_user_func($this->errorHandler, $exception, $attempt, $this->getMaxAttempts());
            }
        }

        return $result;
    }

    public function decider(): callable{
        return $this->decider ?? $this->getDefaultDecider();
    }

    public function setDecider(callable $callback): self
    {
        $this->decider = $callback;
        return $this;
    }

    public function setErrorHandler(callable $callback): self
    {
        $this->errorHandler = $callback;
        return $this;
    }

    protected function getDefaultDecider()
    {
        return function ($retry, $maxAttempts, $result = null, $exception = null) {
            if($retry >= $maxAttempts && ! is_null($exception)) {
                return false;
            }

            return $retry < $maxAttempts && !is_null($exception);
        };
    }

    public function wait($attempt): void 
    {
        if ($attempt == 0) {
            return;
        }

        usleep($this->getWaitTime($attempt) * 1000);
    }

    public function getWaitTime($attempt): int
    {
        $waitTime = call_user_func($this->getStrategy(), $attempt);

        return $this->jitter($this->cap($waitTime));
    }

    protected function cap(int $waitTime): int
    {
        return is_int($this->getWaitCap())
            ? min($this->getWaitCap(), $waitTime)
            : $waitTime;
    }

    protected function jitter(int $waitTime): int
    {
        return $this->jitterEnabled()
            ? mt_rand(0, $waitTime)
            : $waitTime;
    }
}
