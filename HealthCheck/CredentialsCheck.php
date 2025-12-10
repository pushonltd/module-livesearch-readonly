<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\HealthCheck;

use PushON\LiveSearchReadOnly\Config\Credentials;

/**
 * Check custom credentials configuration
 */
class CredentialsCheck implements CheckInterface
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
        if (!$this->credentials->isEnabled()) {
            return new CheckResult('Credentials', [], [], true);
        }

        $apiKey = $this->credentials->getApiKey();
        $privateKey = $this->credentials->getPrivateKey();
        $environmentId = $this->credentials->getEnvironmentId();

        $apiKeyOk = !empty($apiKey);
        $privateKeyOk = !empty($privateKey);
        $envIdOk = !empty($environmentId);

        $statuses = [
            new StatusLine('API Key', $apiKeyOk, $apiKeyOk ? $this->mask($apiKey) : 'MISSING'),
            new StatusLine('Private Key', $privateKeyOk, $privateKeyOk ? $this->mask($privateKey) : 'MISSING'),
            new StatusLine('Environment ID', $envIdOk, $envIdOk ? $environmentId : 'MISSING'),
        ];

        $messages = [];
        if (!$apiKeyOk || !$privateKeyOk || !$envIdOk) {
            $messages = $this->getCredentialsHelpMessages();
        }

        $passed = $this->credentials->isFallbackEnabled() || ($apiKeyOk && $privateKeyOk && $envIdOk);

        return new CheckResult('Credentials', $statuses, $messages, $passed);
    }

    /**
     * @param string $value
     * @return string
     */
    private function mask(string $value): string
    {
        $length = strlen($value);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 4) . '...' . substr($value, -4) . ' (' . $length . ' chars)';
    }

    /**
     * @return array
     */
    private function getCredentialsHelpMessages(): array
    {
        return [
            'help:How to get credentials:',
            '1. SSH into the environment you want to source LiveSearch from',
            '2. Run these commands to get the credentials:',
            'cmd:bin/magento config:show services_connector/services_connector_integration/production_api_key',
            'cmd:bin/magento config:show services_connector/services_connector_integration/production_private_key',
            'cmd:bin/magento config:show services_connector/services_id/environment_id',
            '3. Copy values to Admin Panel:',
            'cmd:Stores > Configuration > Services > LiveSearch Read-Only Credentials',
        ];
    }
}
