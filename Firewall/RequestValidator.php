<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Firewall;

/**
 * Validates if request path is allowed for read-only access
 */
class RequestValidator
{
    /** @var array<string, bool> */
    private array $allowedPaths;

    /**
     * Constructor
     *
     * @param array $allowedPaths Path => true to allow, false to disallow
     */
    public function __construct(
        array $allowedPaths = []
    ) {
        $this->allowedPaths = array_filter($allowedPaths, fn(bool $allowed) => $allowed);
    }

    /**
     * Check if path is allowed for read-only access
     *
     * @param string $path
     * @return bool
     */
    public function isAllowed(string $path): bool
    {
        return array_any(
            array_keys($this->allowedPaths),
            fn(string $allowedPath) => str_contains($path, $allowedPath)
        );
    }
}
