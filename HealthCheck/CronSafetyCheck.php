<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\HealthCheck;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Check if SaaS credentials are set (cron safety)
 */
class CronSafetyCheck implements CheckInterface
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
        $environment = $this->scopeConfig->getValue('magento_saas/environment') ?: 'production';
        $apiKeyPath = 'services_connector/services_connector_integration/' . $environment . '_api_key';
        $privateKeyPath = 'services_connector/services_connector_integration/' . $environment . '_private_key';

        $saasApiKey = $this->scopeConfig->getValue($apiKeyPath);
        $saasPrivateKey = $this->scopeConfig->getValue($privateKeyPath);

        $hasSaasCredentials = !empty($saasApiKey) && !empty($saasPrivateKey);

        if ($hasSaasCredentials) {
            return new CheckResult(
                'Cron Safety',
                [new StatusLine('SaaS Credentials', false, 'SET - CRON MAY SYNC DATA', false)],
                [
                    'error:WARNING: Running cron may sync local catalog to production!',
                    'To prevent this, remove SaaS credentials:',
                    'cmd:bin/magento config:delete ' . $apiKeyPath,
                    'cmd:bin/magento config:delete ' . $privateKeyPath,
                ],
                true
            );
        }

        return new CheckResult(
            'Cron Safety',
            [new StatusLine('SaaS Credentials', true, 'NOT SET - Safe to run cron')],
            ['Cron will not sync data to production'],
            true
        );
    }
}
