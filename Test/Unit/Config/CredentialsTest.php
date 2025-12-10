<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Test\Unit\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PushON\LiveSearchReadOnly\Config\Credentials;
use PushON\LiveSearchReadOnly\Util\PemKeyNormalizer;

class CredentialsTest extends TestCase
{
    private MockObject&ScopeConfigInterface $scopeConfig;
    private MockObject&PemKeyNormalizer $pemKeyNormalizer;
    private Credentials $credentials;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->pemKeyNormalizer = $this->createMock(PemKeyNormalizer::class);
        $this->credentials = new Credentials($this->scopeConfig, $this->pemKeyNormalizer);
    }

    public function testIsEnabledReturnsTrue(): void
    {
        $this->scopeConfig->method('isSetFlag')
            ->with('pushon_livesearch_readonly/credentials/enabled')
            ->willReturn(true);

        $this->assertTrue($this->credentials->isEnabled());
    }

    public function testIsEnabledReturnsFalse(): void
    {
        $this->scopeConfig->method('isSetFlag')
            ->with('pushon_livesearch_readonly/credentials/enabled')
            ->willReturn(false);

        $this->assertFalse($this->credentials->isEnabled());
    }

    public function testIsFallbackEnabledReturnsTrue(): void
    {
        $this->scopeConfig->method('isSetFlag')
            ->with('pushon_livesearch_readonly/credentials/fallback_enabled')
            ->willReturn(true);

        $this->assertTrue($this->credentials->isFallbackEnabled());
    }

    public function testGetApiKeyReturnsValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('pushon_livesearch_readonly/credentials/api_key')
            ->willReturn('test-api-key');

        $this->assertSame('test-api-key', $this->credentials->getApiKey());
    }

    public function testGetApiKeyReturnsNullWhenNotSet(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('pushon_livesearch_readonly/credentials/api_key')
            ->willReturn(null);

        $this->assertNull($this->credentials->getApiKey());
    }

    public function testGetPrivateKeyNormalizesValue(): void
    {
        $rawKey = '-----BEGIN PRIVATE KEY-----ABC-----END PRIVATE KEY-----';
        $normalizedKey = "-----BEGIN PRIVATE KEY-----\nABC\n-----END PRIVATE KEY-----";

        $this->scopeConfig->method('getValue')
            ->with('pushon_livesearch_readonly/credentials/private_key')
            ->willReturn($rawKey);

        $this->pemKeyNormalizer->method('normalize')
            ->with($rawKey)
            ->willReturn($normalizedKey);

        $this->assertSame($normalizedKey, $this->credentials->getPrivateKey());
    }

    public function testGetPrivateKeyReturnsNullWhenNotSet(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('pushon_livesearch_readonly/credentials/private_key')
            ->willReturn(null);

        $this->assertNull($this->credentials->getPrivateKey());
    }

    public function testGetEnvironmentIdReturnsValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('pushon_livesearch_readonly/credentials/environment_id')
            ->willReturn('env-123');

        $this->assertSame('env-123', $this->credentials->getEnvironmentId());
    }
}
