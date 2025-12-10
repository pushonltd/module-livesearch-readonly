<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Config;

use Magento\ServicesId\Model\ServicesConfigInterface;

/**
 * Decorator for ServicesConfigInterface that overrides environment ID and API key check
 * with custom credentials when configured.
 */
class ServicesConfigDecorator implements ServicesConfigInterface
{
    /**
     * @param ServicesConfigInterface $decorated
     * @param Credentials $credentials
     */
    public function __construct(
        private readonly ServicesConfigInterface $decorated,
        private readonly Credentials $credentials
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getEnvironmentId(): ?string
    {
        if (!$this->credentials->isEnabled()) {
            return $this->decorated->getEnvironmentId();
        }

        $customId = $this->credentials->getEnvironmentId();
        if ($customId) {
            return $customId;
        }

        if ($this->credentials->isFallbackEnabled()) {
            return $this->decorated->getEnvironmentId();
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function isApiKeySet(): bool
    {
        if (!$this->credentials->isEnabled()) {
            return $this->decorated->isApiKeySet();
        }

        if ($this->credentials->getApiKey() && $this->credentials->getPrivateKey()) {
            return true;
        }

        if ($this->credentials->isFallbackEnabled()) {
            return $this->decorated->isApiKeySet();
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getProjectId(): ?string
    {
        return $this->decorated->getProjectId();
    }

    /**
     * @inheritdoc
     */
    public function getProjectName(): ?string
    {
        return $this->decorated->getProjectName();
    }

    /**
     * @inheritdoc
     */
    public function getEnvironmentName(): ?string
    {
        return $this->decorated->getEnvironmentName();
    }

    /**
     * @inheritdoc
     */
    public function getEnvironmentType(): ?string
    {
        return $this->decorated->getEnvironmentType();
    }

    /**
     * @inheritdoc
     */
    public function getSandboxApiKey(): ?string
    {
        return $this->decorated->getSandboxApiKey();
    }

    /**
     * @inheritdoc
     */
    public function getSandboxPrivateKey(): ?string
    {
        return $this->decorated->getSandboxPrivateKey();
    }

    /**
     * @inheritdoc
     */
    public function getProductionApiKey(): ?string
    {
        return $this->decorated->getProductionApiKey();
    }

    /**
     * @inheritdoc
     */
    public function getProductionPrivateKey(): ?string
    {
        return $this->decorated->getProductionPrivateKey();
    }

    /**
     * @inheritdoc
     */
    public function getImsOrganizationId(): ?string
    {
        return $this->decorated->getImsOrganizationId();
    }

    /**
     * @inheritdoc
     */
    public function getCloudId(): ?string
    {
        return $this->decorated->getCloudId();
    }

    /**
     * @inheritdoc
     */
    public function getRegistryApiUrl(string $uri): string
    {
        return $this->decorated->getRegistryApiUrl($uri);
    }

    /**
     * @inheritdoc
     */
    public function setConfigValues(array $configs): void
    {
        $this->decorated->setConfigValues($configs);
    }

    /**
     * @inheritdoc
     */
    public function getDisabledFields(): array
    {
        return $this->decorated->getDisabledFields();
    }
}
