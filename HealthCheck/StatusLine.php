<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\HealthCheck;

/**
 * Status line value object for health check output
 */
class StatusLine
{
    /**
     * @param string $label
     * @param bool $ok
     * @param string|null $value
     * @param bool $critical
     */
    public function __construct(
        private readonly string $label,
        private readonly bool $ok,
        private readonly ?string $value = null,
        private readonly bool $critical = true
    ) {
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->ok;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isCritical(): bool
    {
        return $this->critical;
    }
}
