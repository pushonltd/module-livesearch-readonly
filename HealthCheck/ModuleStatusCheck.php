<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\HealthCheck;

use PushON\LiveSearchReadOnly\Config\Credentials;

/**
 * Check module enabled status
 */
class ModuleStatusCheck implements CheckInterface
{
    /**
     * @param Credentials $credentials
     */
    public function __construct(
        private readonly Credentials $credentials
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(): CheckResult
    {
        $enabled = $this->credentials->isEnabled();
        $fallback = $this->credentials->isFallbackEnabled();

        $statuses = [];
        $messages = [];

        if (!$enabled) {
            $statuses[] = new StatusLine('Module Enabled', false, 'DISABLED');
            $messages[] = 'LiveSearch uses default Magento SaaS credentials';
            return new CheckResult('Module Status', $statuses, $messages, true);
        }

        $statuses[] = new StatusLine('Module Enabled', true);
        $statuses[] = new StatusLine('Fallback to SaaS', $fallback, $fallback ? 'ON' : 'OFF', false);

        if ($fallback) {
            $messages[] = 'Will use SaaS credentials if custom ones are missing';
        }

        return new CheckResult('Module Status', $statuses, $messages, true);
    }
}
