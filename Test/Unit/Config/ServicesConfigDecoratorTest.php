<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Test\Unit\Config;

use Magento\ServicesId\Model\ServicesConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PushON\LiveSearchReadOnly\Config\Credentials;
use PushON\LiveSearchReadOnly\Config\ServicesConfigDecorator;

class ServicesConfigDecoratorTest extends TestCase
{
    private MockObject&ServicesConfigInterface $decorated;
    private MockObject&Credentials $credentials;
    private ServicesConfigDecorator $decorator;

    protected function setUp(): void
    {
        $this->decorated = $this->createMock(ServicesConfigInterface::class);
        $this->credentials = $this->createMock(Credentials::class);
        $this->decorator = new ServicesConfigDecorator($this->decorated, $this->credentials);
    }

    public function testGetEnvironmentIdDelegatesToOriginalWhenDisabled(): void
    {
        $this->credentials->method('isEnabled')->willReturn(false);
        $this->decorated->method('getEnvironmentId')->willReturn('original-env-id');

        $this->assertSame('original-env-id', $this->decorator->getEnvironmentId());
    }

    public function testGetEnvironmentIdReturnsCustomWhenEnabled(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getEnvironmentId')->willReturn('custom-env-id');

        $this->assertSame('custom-env-id', $this->decorator->getEnvironmentId());
    }

    public function testGetEnvironmentIdFallsBackWhenEnabledButNoCustomId(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getEnvironmentId')->willReturn(null);
        $this->credentials->method('isFallbackEnabled')->willReturn(true);
        $this->decorated->method('getEnvironmentId')->willReturn('fallback-env-id');

        $this->assertSame('fallback-env-id', $this->decorator->getEnvironmentId());
    }

    public function testGetEnvironmentIdReturnsNullWhenNoCustomAndNoFallback(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getEnvironmentId')->willReturn(null);
        $this->credentials->method('isFallbackEnabled')->willReturn(false);

        $this->assertNull($this->decorator->getEnvironmentId());
    }

    public function testIsApiKeySetDelegatesToOriginalWhenDisabled(): void
    {
        $this->credentials->method('isEnabled')->willReturn(false);
        $this->decorated->method('isApiKeySet')->willReturn(true);

        $this->assertTrue($this->decorator->isApiKeySet());
    }

    public function testIsApiKeySetReturnsTrueWhenCustomCredentialsSet(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn('api-key');
        $this->credentials->method('getPrivateKey')->willReturn('private-key');

        $this->assertTrue($this->decorator->isApiKeySet());
    }

    public function testIsApiKeySetReturnsFalseWhenOnlyApiKeySet(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn('api-key');
        $this->credentials->method('getPrivateKey')->willReturn(null);
        $this->credentials->method('isFallbackEnabled')->willReturn(false);

        $this->assertFalse($this->decorator->isApiKeySet());
    }

    public function testIsApiKeySetFallsBackWhenNoCustomCredentials(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn(null);
        $this->credentials->method('getPrivateKey')->willReturn(null);
        $this->credentials->method('isFallbackEnabled')->willReturn(true);
        $this->decorated->method('isApiKeySet')->willReturn(true);

        $this->assertTrue($this->decorator->isApiKeySet());
    }

    public function testDelegatedMethodsPassThrough(): void
    {
        $this->decorated->method('getProjectId')->willReturn('project-id');
        $this->decorated->method('getProjectName')->willReturn('project-name');
        $this->decorated->method('getEnvironmentName')->willReturn('env-name');
        $this->decorated->method('getEnvironmentType')->willReturn('production');
        $this->decorated->method('getImsOrganizationId')->willReturn('ims-org-id');
        $this->decorated->method('getCloudId')->willReturn('cloud-id');

        $this->assertSame('project-id', $this->decorator->getProjectId());
        $this->assertSame('project-name', $this->decorator->getProjectName());
        $this->assertSame('env-name', $this->decorator->getEnvironmentName());
        $this->assertSame('production', $this->decorator->getEnvironmentType());
        $this->assertSame('ims-org-id', $this->decorator->getImsOrganizationId());
        $this->assertSame('cloud-id', $this->decorator->getCloudId());
    }
}
