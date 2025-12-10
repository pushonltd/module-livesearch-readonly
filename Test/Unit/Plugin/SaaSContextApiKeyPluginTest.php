<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Test\Unit\Plugin;

use Magento\LiveSearchProductListing\Block\Frontend\SaaSContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PushON\LiveSearchReadOnly\Config\Credentials;
use PushON\LiveSearchReadOnly\Plugin\SaaSContextApiKeyPlugin;

class SaaSContextApiKeyPluginTest extends TestCase
{
    private MockObject&Credentials $credentials;
    private MockObject&SaaSContext $subject;
    private SaaSContextApiKeyPlugin $plugin;

    protected function setUp(): void
    {
        $this->credentials = $this->createMock(Credentials::class);
        $this->subject = $this->createMock(SaaSContext::class);
        $this->plugin = new SaaSContextApiKeyPlugin($this->credentials);
    }

    public function testReturnsOriginalResultWhenDisabled(): void
    {
        $this->credentials->method('isEnabled')->willReturn(false);

        $result = $this->plugin->afterGetApiKey($this->subject, 'original-key');

        $this->assertSame('original-key', $result);
    }

    public function testReturnsCustomApiKeyWhenEnabled(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn('custom-api-key');

        $result = $this->plugin->afterGetApiKey($this->subject, 'original-key');

        $this->assertSame('custom-api-key', $result);
    }

    public function testFallsBackToOriginalWhenNoCustomKeyAndFallbackEnabled(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn(null);
        $this->credentials->method('isFallbackEnabled')->willReturn(true);

        $result = $this->plugin->afterGetApiKey($this->subject, 'original-key');

        $this->assertSame('original-key', $result);
    }

    public function testReturnsNullWhenNoCustomKeyAndFallbackDisabled(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn(null);
        $this->credentials->method('isFallbackEnabled')->willReturn(false);

        $result = $this->plugin->afterGetApiKey($this->subject, 'original-key');

        $this->assertNull($result);
    }

    public function testHandlesNullOriginalResult(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn('custom-api-key');

        $result = $this->plugin->afterGetApiKey($this->subject, null);

        $this->assertSame('custom-api-key', $result);
    }
}
