<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PushON\LiveSearchReadOnly\Util\PemKeyNormalizer;

/**
 * LiveSearch ReadOnly credentials configuration reader
 */
class Credentials
{
    private const XML_PATH_ENABLED = 'pushon_livesearch_readonly/credentials/enabled';
    private const XML_PATH_FALLBACK_ENABLED = 'pushon_livesearch_readonly/credentials/fallback_enabled';
    private const XML_PATH_API_KEY = 'pushon_livesearch_readonly/credentials/api_key';
    private const XML_PATH_PRIVATE_KEY = 'pushon_livesearch_readonly/credentials/private_key';
    private const XML_PATH_ENVIRONMENT_ID = 'pushon_livesearch_readonly/credentials/environment_id';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param PemKeyNormalizer $pemKeyNormalizer
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly PemKeyNormalizer $pemKeyNormalizer
    ) {
    }

    /**
     * Check if custom credentials are enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED);
    }

    /**
     * Check if fallback to original SaaS credentials is enabled
     *
     * @return bool
     */
    public function isFallbackEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_FALLBACK_ENABLED);
    }

    /**
     * Get custom API key
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_KEY);
    }

    /**
     * Get custom private key with PEM format normalization
     *
     * @return string|null
     */
    public function getPrivateKey(): ?string
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_PRIVATE_KEY);
        if (!$value) {
            return null;
        }

        return $this->pemKeyNormalizer->normalize($value);
    }

    /**
     * Get custom environment ID
     *
     * @return string|null
     */
    public function getEnvironmentId(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ENVIRONMENT_ID);
    }
}
