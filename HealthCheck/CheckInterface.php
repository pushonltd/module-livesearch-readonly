<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\HealthCheck;

/**
 * Interface for health check implementations
 */
interface CheckInterface
{
    /**
     * Execute the health check
     *
     * @return CheckResult
     */
    public function execute(): CheckResult;
}
