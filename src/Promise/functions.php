<?php

namespace Blackjack\Promise;

use React\Promise\Promise;

/**
 * @param Promise $promise
 * @param float                    $timeoutInSeconds
 *
 * @return Promise
 */
function timeout($promise, $timeoutInSeconds = 3.0)
{
    global $container;

    return \React\Promise\Timer\timeout($promise, $timeoutInSeconds, $container['loop']);
}

/**
 * @param float $timeoutInSeconds
 * @param       $value
 *
 * @return Promise
 */
function timedResolve($timeoutInSeconds, $value = null)
{
    global $container;

    return \React\Promise\Timer\resolve($timeoutInSeconds, $container['loop'])
        ->then(function () use ($value) {
            return $value;
        });
}

/**
 * @param Promise[] $promises
 *
 * @return Promise
 */
function serial($promises)
{
    if (!count($promises)) {
        return \React\Promise\resolve([]);
    }

    $promise = array_shift($promises);

    return $promise->then(function ($result) use ($promises) {
        return serial($promises)
            ->then(function ($childResults) use ($result) {
                return \React\Promise\resolve(array_merge([$result], $childResults));
            });
    });
}
