<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\HealthCheck;

/**
 * Health check result value object
 */
class CheckResult
{
    /**
     * @param string $title
     * @param array $statuses
     * @param array $messages
     * @param bool $passed
     */
    public function __construct(
        private readonly string $title,
        private readonly array $statuses,
        private readonly array $messages,
        private readonly bool $passed
    ) {
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get statuses
     *
     * @return array
     */
    public function getStatuses(): array
    {
        return $this->statuses;
    }

    /**
     * Get messages
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Check if passed
     *
     * @return bool
     */
    public function isPassed(): bool
    {
        return $this->passed;
    }
}
