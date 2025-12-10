<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\HealthCheck;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Check gateway URL configuration
 */
class GatewayUrlCheck implements CheckInterface
{
    private const GATEWAY_URLS = [
        'production' => 'https://commerce.adobe.io/',
        'sandbox' => 'https://commerce-beta.adobe.io/',
    ];

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
        $gatewayPath = 'services_connector/' . $environment . '_gateway_url';
        $gatewayUrl = $this->scopeConfig->getValue($gatewayPath);
        $expectedUrl = self::GATEWAY_URLS[$environment] ?? self::GATEWAY_URLS['production'];

        $hasUrl = !empty($gatewayUrl);

        $statuses = [
            new StatusLine('Gateway URL', $hasUrl, $gatewayUrl ?: 'NOT SET'),
        ];

        $messages = [];
        if ($gatewayUrl && $gatewayUrl !== $expectedUrl) {
            $messages[] = sprintf('Expected for %s: %s', $environment, $expectedUrl);
        }

        return new CheckResult('Gateway Configuration', $statuses, $messages, $hasUrl);
    }
}
