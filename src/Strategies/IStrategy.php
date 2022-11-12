<?php
namespace DAS\Retry\Strategies;
interface IStrategy{
    public function getWaitTime(int $attempt): int;
}