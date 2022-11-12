<?php
namespace DAS\Retry;

use DAS\Retry\Strategies\ConstantStrategy;
use DAS\Retry\Strategies\ExponentialStrategy;
use DAS\Retry\Strategies\LinearStrategy;
use DAS\Retry\Strategies\PolynomialStrategy;
use Exception;
use PHPUnit\Framework\TestCase;
use TypeError;

class RetryTest extends TestCase
{
    public function testDefaults()
    {
        $b = Retry::Default();

        $this->assertEquals(5, $b->getMaxAttempts());
        $this->assertInstanceOf(PolynomialStrategy::class, $b->getStrategy());
        $this->assertFalse($b->jitterEnabled());
    }

    public function testFluidApi()
    {
        $b = new Retry();
        $result = $b
          ->setStrategy(new ConstantStrategy(10))
          ->setMaxAttempts(10)
          ->setWaitCap(5)
          ->enableJitter();

        $this->assertEquals(10, $b->getMaxAttempts());
        $this->assertEquals(5, $b->getWaitCap());
        $this->assertTrue($b->jitterEnabled());
        $this->assertInstanceOf(ConstantStrategy::class, $b->getStrategy());
    }

    public function testStrategyInstances()
    {
        $b = new Retry();

        $b->setStrategy(new ConstantStrategy());
        $this->assertInstanceOf(ConstantStrategy::class, $b->getStrategy());

        $b->setStrategy(new LinearStrategy());
        $this->assertInstanceOf(LinearStrategy::class, $b->getStrategy());

        $b->setStrategy(new PolynomialStrategy());
        $this->assertInstanceOf(PolynomialStrategy::class, $b->getStrategy());

        $b->setStrategy(new ExponentialStrategy());
        $this->assertInstanceOf(ExponentialStrategy::class, $b->getStrategy());
    }

    public function testStaticBuilderStrategies()
    {
        $this->assertInstanceOf(ConstantStrategy::class, Retry::ConstantStrategy(1)->getStrategy());

        $this->assertInstanceOf(LinearStrategy::class, Retry::LinearStrategy(1)->getStrategy());

        $this->assertInstanceOf(PolynomialStrategy::class, Retry::PolynomialStrategy(1)->getStrategy());

        $this->assertInstanceOf(ExponentialStrategy::class, Retry::ExponentialStrategy(1)->getStrategy());
    }


    public function testInvalidStrategy()
    {
        $b = new Retry();

        $this->expectException(TypeError::class);
        $b->setStrategy("foo");
    }

    public function testWaitTimes()
    {
        $b = new Retry();
        $b->setMaxAttempts(1)
        ->setStrategy(new LinearStrategy());
        $this->assertEquals(100, $b->getStrategy()->base());

        $this->assertEquals(100, $b->getWaitTime(1));
        $this->assertEquals(200, $b->getWaitTime(2));
    }

    public function testWaitCap()
    {
        $b = new Retry();
        $b->setMaxAttempts(1)
        ->setStrategy(new LinearStrategy(5000));
        $this->assertEquals(10000, $b->getWaitTime(2));

        $b->setWaitCap(5000);

        $this->assertEquals(5000, $b->getWaitTime(2));
    }

    public function testWait()
    {
        $b = new Retry();
        $b->setMaxAttempts(1)
        ->setStrategy(new LinearStrategy(50));
        $start = microtime(true);

        $b->wait(2);

        $end = microtime(true);

        $elapsedMS =  ($end - $start) * 1000;

        // We expect that this took just barely over the 100ms we asked for
        $this->assertTrue($elapsedMS > 90 && $elapsedMS < 150,
            sprintf("Expected elapsedMS between 100 & 110, got: $elapsedMS\n"));
    }

    public function testSuccessfulWork()
    {
        $b =  Retry::Default();

        $result = $b->run(function () {
            return "done";
        });

        $this->assertEquals("done", $result);
    }

    public function testFailedWorkReThrowsException()
    {
        $b =  Retry::Default();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("failure");

        $b->run(function () {
            throw new \Exception("failure");
        });
    }

    public function testHandleErrorsPhp7()
    {
        $b =  Retry::Default();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Modulo by zero");

        $b->run(function () {
            if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
                return 1 % 0;
            } else {
                // Handle version < 7
                throw new Exception("Modulo by zero");
            }
        });
    }

    public function testAttempts()
    {
        $b =  Retry::Default();
        $attempt = 0;

        $result = $b->run(function () use (&$attempt) {
            $attempt++;

            if ($attempt < 5) {
                throw new \Exception("failure");
            }

            return "success";
        });

        $this->assertEquals(5, $attempt);
        $this->assertEquals("success", $result);
    }

    public function testCustomDeciderAttempts()
    {
        $b = new Retry();
        $b->setStrategy(new ConstantStrategy(0));
        $b->setMaxAttempts(10);
        $b->setDecider(
            function ($retry, $maxAttempts, $result = null, $exception = null) {
                if ($retry >= $maxAttempts || $result == "success") {
                    return false;
                }
                return true;
            }
        );

        $attempt = 0;

        $result = $b->run(function () use (&$attempt) {
            $attempt++;

            if ($attempt < 5) {
                throw new \Exception("failure");
            }

            if ($attempt < 7) {
                return 'not yet';
            }

            return "success";
        });

        $this->assertEquals(7, $attempt);
        $this->assertEquals("success", $result);
    }

    public function testErrorHandler()
    {
        $log = [];

        $b = new Retry();
        $b->setStrategy(new ConstantStrategy(0));
        $b->setMaxAttempts(10);
        $b->setErrorHandler(function($exception, $attempt, $maxAttempts) use(&$log) {
            $log[] = "Attempt $attempt of $maxAttempts: " . $exception->getMessage();
        });

        $attempt = 0;

        $result = $b->run(function () use (&$attempt) {
            $attempt++;

            if ($attempt < 5) {
                throw new \Exception("failure");
            }

            return "success";
        });

        $this->assertEquals(4, count($log));
        $this->assertEquals("Attempt 4 of 10: failure", array_pop($log));
        $this->assertEquals("success", $result);
    }

    public function testJitter()
    {
        $b = new Retry(10);
        $b->setStrategy(new ConstantStrategy(1000))
        ->setMaxAttempts(10);
        // First without jitter
        $this->assertEquals(1000, $b->getWaitTime(1));

        // Now with jitter
        $b->enableJitter();

        // Because it's still possible that I could get 1000 back even with jitter, I'm going to generate two
        $waitTime1 = $b->getWaitTime(1);
        $waitTime2 = $b->getWaitTime(1);

        // And I'm banking that I didn't hit the _extremely_ rare chance that both were randomly chosen to be 1000 still
        $this->assertTrue($waitTime1 < 1000 || $waitTime2 < 1000);
    }
}
