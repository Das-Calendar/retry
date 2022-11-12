# PHP Retry

Based on [https://github.com/stechstudio/backoff]

Easily wrap your code with retry functionality. This library provides:

 1. 4 backoff strategies (plus the ability to use your own)
 2. Optional jitter / randomness to spread out retries and minimize collisions
 3. Wait time cap
 4. Callbacks for custom retry logic or error handling


## Defaults

This library provides sane defaults so you can hopefully just jump in for most of your use cases.

By default the backoff is quadratic with a 100ms base time (`attempt^2 * 100`), a max of 5 retries, and no jitter.


## Retry class usage

The Retry class work as builder and have available the nex method `setMaxAttempts`, `setStrategy`, `setWaitCap`, `enableJitter`.

```php
$retry = new Retry();
$retry->setMaxAttempts(10)
->setStrategy(new ExponentialStrategy(100))
->setWaitCap(10000)
->enableJitter();
$result = $retry->run(function() {
    return doSomeWorkThatMightFail();
});
```

Or if you are injecting the Retry class with a dependency container, you can set it up with setters after the fact. Note that setters are chainable.

```php
// Assuming a fresh instance of $retry was handed to you
$result = $retry
    ->setStrategy('constant')
    ->setMaxAttempts(10)
    ->enableJitter()
    ->run(function() {
        return doSomeWorkThatMightFail();
    });
```

## Strategies

There are four built-in strategies available: constant, linear, polynomial, and exponential.

The default base time for all strategies is 100 milliseconds.

### Constant

```php
$strategy = new ConstantStrategy(500);
```

This strategy will sleep for 500 milliseconds on each retry loop.

### Linear

```php
$strategy = new LinearStrategy(200);
```

This strategy will sleep for `attempt * baseTime`, providing linear backoff starting at 200 milliseconds.

### Polynomial

```php
$strategy = new PolynomialStrategy(100, 3);
```

This strategy will sleep for `(attempt^degree) * baseTime`, so in this case `(attempt^3) * 100`.

The default degree if none provided is 2, effectively quadratic time.

### Exponential

```php
$strategy = new ExponentialStrategy(100);
```

This strategy will sleep for `(2^attempt) * baseTime`.

## Specifying strategy

In our earlier code examples we specified the strategy as a string:

```php
$retry = new Retry();
$retry->setStrategy(new ConstantStrategy(100));
$retry->setMaxAttempts(10);
```

This would use the `ConstantStrategy` with defaults, effectively giving you a 100 millisecond sleep time.

You can create the strategy instance yourself in order to modify these defaults:

```php
$retry = new Retry();
$retry->setStrategy(new LinearStrategy(500));
$retry->setMaxAttempts(10)

```

Finally, you can use static constructors to speed things up

```php

$retry = new Retry::Default();

$retry = new Retry::ConstantStrategy(int $maxAttempt, int $waitCap = null, bool $useJitter = null, callable $decider = null);

$retry = new Retry::LinearStrategy(int $maxAttempt, int $waitCap = null, bool $useJitter = null, callable $decider = null);

$retry = new Retry::PolynomialStrategy(int $maxAttempt, int $waitCap = null, bool $useJitter = null, callable $decider = null);

$retry = new Retry::ExponentialStrategy(int $maxAttempt, int $waitCap = null, bool $useJitter = null, callable $decider = null);
```

## Wait cap

You may want to use a fast growing backoff time (like exponential) but then also set a max wait time so that it levels out after a while.

## Jitter

If you have a lot of clients starting a job at the same time and encountering failures, any of the above backoff strategies could mean the workers continue to collide at each retry.

The solution for this is to add randomness. See here for a good explanation:

https://www.awsarchitectureblog.com/2015/03/backoff.html

You can enable jitter using the `enableJitter()` method on the Retry class.

We use the "FullJitter" approach outlined in the above article, where a random number between 0 and the sleep time provided by your selected strategy is used.

## Custom retry decider

By default Retry will retry if an exception is encountered, and if it has not yet hit max retries.

You may provide your own retry decider for more advanced use cases. Perhaps you want to retry based on time rather than number of retries, or perhaps there are scenarios where you would want retry even when an exception was not encountered.

Provide the decider as a callback, or an instance of a class with an `__invoke` method. Retry will hand it four parameters: the current attempt, max attempts, the last result received, and the exception if one was encountered. Your decider needs to return true or false.

```php
$retry->setDecider(function($attempt, $maxAttempts, $result, $exception = null) {
    return someCustomLogic();
});
```

## Error handler callback

You can provide a custom error handler to be notified anytime an exception occurs, even if we have yet to reach max attempts. This is a useful place to do logging for example.

```php
$retry->setErrorHandler(function($exception, $attempt, $maxAttempts) {
    Log::error("On run $attempt we hit a problem: " . $exception->getMessage());
});
```