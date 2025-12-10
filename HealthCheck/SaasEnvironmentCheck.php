<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\HealthCheck;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Check SaaS environment configuration
 */
class SaasEnvironmentCheck implements CheckInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(): CheckResult
    {
        $environment = $this->scopeConfig->getValue('magento_saas/environment');

        $displayEnv = $environment ?: 'production (default)';
        $effectiveEnv = $environment ?: 'production';
        $isValid = in_array($effectiveEnv, ['production', 'sandbox'], true);

        $statuses = [
            new StatusLine('Environment', $isValid, $displayEnv),
        ];

        $messages = [];
        if (!$environment) {
            $messages[] = 'Not set globally, defaults to production for LiveSearch';
        }

        return new CheckResult('SaaS Environment', $statuses, $messages, $isValid);
    }
}
