<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\HealthCheck;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PushON\LiveSearchReadOnly\Config\Credentials;
use PushON\LiveSearchReadOnly\Firewall\BlockingMiddlewareFactory;

/**
 * Check firewall rules by making real requests through the middleware stack
 */
class FirewallCheck implements CheckInterface
{
    private const TEST_ALLOWED_PATHS = [
        '/search/graphql',
        '/search/auth-graphql',
        '/search-admin/graphql',
    ];

    private const TEST_BLOCKED_PATHS = [
        '/catalog/sync',
        '/orders/submit',
        '/products/feed',
    ];

    /**
     * @param Credentials $credentials
     * @param BlockingMiddlewareFactory $blockingMiddlewareFactory
     */
    public function __construct(
        private readonly Credentials $credentials,
        private readonly BlockingMiddlewareFactory $blockingMiddlewareFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(): CheckResult
    {
        if (!$this->credentials->isEnabled()) {
            return new CheckResult('Firewall', [], [], true);
        }

        $statuses = [];
        $allPassed = true;

        foreach (self::TEST_ALLOWED_PATHS as $path) {
            $result = $this->testPath($path);
            $isAllowed = $result === 'allowed';
            $statuses[] = new StatusLine(
                $path,
                $isAllowed,
                $isAllowed ? 'ALLOWED' : 'BLOCKED (ERROR)'
            );
            if (!$isAllowed) {
                $allPassed = false;
            }
        }

        foreach (self::TEST_BLOCKED_PATHS as $path) {
            $result = $this->testPath($path);
            $isBlocked = $result === 'blocked';
            $statuses[] = new StatusLine(
                $path,
                $isBlocked,
                $isBlocked ? 'BLOCKED' : 'ALLOWED (ERROR)'
            );
            if (!$isBlocked) {
                $allPassed = false;
            }
        }

        $messages = ['Firewall blocks all non-search API paths'];

        return new CheckResult('Firewall', $statuses, $messages, $allPassed);
    }

    /**
     * Test a path through the actual middleware stack
     *
     * @param string $path
     * @return string 'allowed' or 'blocked'
     */
    private function testPath(string $path): string
    {
        // Mock handler that returns success if request reaches it (not blocked)
        $mockHandler = new MockHandler([
            new Response(200, [], (string) json_encode(['status' => 'allowed']))
        ]);

        $stack = HandlerStack::create($mockHandler);
        $stack->push($this->blockingMiddlewareFactory->create(), 'firewall');

        $client = new Client(['handler' => $stack]);

        try {
            $response = $client->request('POST', 'https://test.example.com' . $path);
            $body = json_decode((string) $response->getBody(), true);

            return $body['status'] ?? 'unknown';
        } catch (\Exception $e) {
            return 'error';
        }
    }
}
