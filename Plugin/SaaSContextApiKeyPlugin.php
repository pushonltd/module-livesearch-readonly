<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Plugin;

use Magento\LiveSearchProductListing\Block\Frontend\SaaSContext;
use PushON\LiveSearchReadOnly\Config\Credentials;

/**
 * Plugin to override API key in LiveSearch frontend SaaSContext block
 */
class SaaSContextApiKeyPlugin
{
    /**
     * @param Credentials $credentials
     */
    public function __construct(
        private readonly Credentials $credentials
    ) {
    }

    /**
     * Override getApiKey() to return custom API key when module is enabled
     *
     * @param SaaSContext $subject
     * @param string|null $result
     * @return string|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetApiKey(SaaSContext $subject, ?string $result): ?string
    {
        if (!$this->credentials->isEnabled()) {
            return $result;
        }

        $customApiKey = $this->credentials->getApiKey();
        if ($customApiKey) {
            return $customApiKey;
        }

        if ($this->credentials->isFallbackEnabled()) {
            return $result;
        }

        return null;
    }
}
