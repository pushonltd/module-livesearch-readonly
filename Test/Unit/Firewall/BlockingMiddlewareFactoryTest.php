<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Test\Unit\Firewall;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PushON\LiveSearchReadOnly\Firewall\BlockedResponseFactory;
use PushON\LiveSearchReadOnly\Firewall\BlockingMiddlewareFactory;
use PushON\LiveSearchReadOnly\Firewall\RequestValidator;

class BlockingMiddlewareFactoryTest extends TestCase
{
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testAllowedRequestPassesThrough(): void
    {
        $validator = new RequestValidator(['search/graphql' => true]);
        $factory = new BlockingMiddlewareFactory(
            $validator,
            new BlockedResponseFactory(),
            $this->logger
        );

        $mockHandler = new MockHandler([
            new Response(200, [], '{"status":"success"}')
        ]);

        $stack = HandlerStack::create($mockHandler);
        $stack->push($factory->create());

        $client = new Client(['handler' => $stack]);
        $response = $client->request('POST', 'https://example.com/search/graphql');

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('success', $body['status']);
    }

    public function testBlockedRequestReturnsBlockedResponse(): void
    {
        $validator = new RequestValidator(['search/graphql' => true]);
        $factory = new BlockingMiddlewareFactory(
            $validator,
            new BlockedResponseFactory(),
            $this->logger
        );

        $mockHandler = new MockHandler([
            new Response(200, [], '{"status":"success"}')
        ]);

        $stack = HandlerStack::create($mockHandler);
        $stack->push($factory->create());

        $client = new Client(['handler' => $stack]);
        $response = $client->request('POST', 'https://example.com/catalog/sync');

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('blocked', $body['status']);
    }

    public function testBlockedRequestLogsWarning(): void
    {
        $validator = new RequestValidator([]);
        $factory = new BlockingMiddlewareFactory(
            $validator,
            new BlockedResponseFactory(),
            $this->logger
        );

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                '[LiveSearchReadOnly] Blocked non-search request',
                $this->callback(function ($context) {
                    return $context['method'] === 'POST'
                        && str_contains($context['uri'], '/catalog/sync');
                })
            );

        $stack = HandlerStack::create(new MockHandler([new Response(200)]));
        $stack->push($factory->create());

        $client = new Client(['handler' => $stack]);
        $client->request('POST', 'https://example.com/catalog/sync');
    }
}
