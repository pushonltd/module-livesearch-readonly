<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Test\Unit\Firewall;

use PHPUnit\Framework\TestCase;
use PushON\LiveSearchReadOnly\Firewall\RequestValidator;

class RequestValidatorTest extends TestCase
{
    public function testAllowsConfiguredPath(): void
    {
        $validator = new RequestValidator([
            'search/graphql' => true,
        ]);

        $this->assertTrue($validator->isAllowed('/search/graphql'));
    }

    public function testAllowsPathContainingConfiguredPattern(): void
    {
        $validator = new RequestValidator([
            'search/graphql' => true,
        ]);

        $this->assertTrue($validator->isAllowed('/api/v1/search/graphql/query'));
    }

    public function testBlocksNonConfiguredPath(): void
    {
        $validator = new RequestValidator([
            'search/graphql' => true,
        ]);

        $this->assertFalse($validator->isAllowed('/catalog/sync'));
    }

    public function testDisabledPathIsBlocked(): void
    {
        $validator = new RequestValidator([
            'search/graphql' => false,
        ]);

        $this->assertFalse($validator->isAllowed('/search/graphql'));
    }

    public function testEmptyConfigBlocksAllPaths(): void
    {
        $validator = new RequestValidator([]);

        $this->assertFalse($validator->isAllowed('/search/graphql'));
        $this->assertFalse($validator->isAllowed('/catalog/sync'));
    }

    public function testMultipleAllowedPaths(): void
    {
        $validator = new RequestValidator([
            'search/graphql' => true,
            'search/auth-graphql' => true,
            'search-admin/graphql' => true,
        ]);

        $this->assertTrue($validator->isAllowed('/search/graphql'));
        $this->assertTrue($validator->isAllowed('/search/auth-graphql'));
        $this->assertTrue($validator->isAllowed('/search-admin/graphql'));
        $this->assertFalse($validator->isAllowed('/catalog/sync'));
    }

    public function testMixedAllowAndDisallow(): void
    {
        $validator = new RequestValidator([
            'search/graphql' => true,
            'search/auth-graphql' => false,
        ]);

        $this->assertTrue($validator->isAllowed('/search/graphql'));
        $this->assertFalse($validator->isAllowed('/search/auth-graphql'));
    }
}
