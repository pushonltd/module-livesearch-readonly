<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Test\Unit\Util;

use PHPUnit\Framework\TestCase;
use PushON\LiveSearchReadOnly\Util\PemKeyNormalizer;

class PemKeyNormalizerTest extends TestCase
{
    private PemKeyNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new PemKeyNormalizer();
    }

    public function testReturnsKeyUnchangedIfAlreadyHasNewlines(): void
    {
        $key = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBg\n-----END PRIVATE KEY-----";

        $result = $this->normalizer->normalize($key);

        $this->assertSame($key, $result);
    }

    public function testNormalizesKeyWithoutNewlines(): void
    {
        $key = '-----BEGIN PRIVATE KEY-----MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC-----END PRIVATE KEY-----';

        $result = $this->normalizer->normalize($key);

        $this->assertStringContainsString("\n", $result);
        $this->assertStringStartsWith("-----BEGIN PRIVATE KEY-----\n", $result);
        $this->assertStringEndsWith("\n-----END PRIVATE KEY-----", $result);
    }

    public function testWrapsContentAt64Characters(): void
    {
        $content = str_repeat('A', 128);
        $key = '-----BEGIN PRIVATE KEY-----' . $content . '-----END PRIVATE KEY-----';

        $result = $this->normalizer->normalize($key);

        $lines = explode("\n", $result);
        // Header, 2 content lines (64 chars each), footer
        $this->assertCount(4, $lines);
        $this->assertSame(64, strlen($lines[1]));
        $this->assertSame(64, strlen($lines[2]));
    }

    public function testReturnsNonPemKeyUnchanged(): void
    {
        $key = 'not-a-pem-key';

        $result = $this->normalizer->normalize($key);

        $this->assertSame($key, $result);
    }

    public function testHandlesDifferentKeyTypes(): void
    {
        $key = '-----BEGIN RSA PRIVATE KEY-----MIIEvQIBADANBg-----END RSA PRIVATE KEY-----';

        $result = $this->normalizer->normalize($key);

        $this->assertStringStartsWith("-----BEGIN RSA PRIVATE KEY-----\n", $result);
        $this->assertStringEndsWith("\n-----END RSA PRIVATE KEY-----", $result);
    }
}
