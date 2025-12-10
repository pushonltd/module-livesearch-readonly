<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Firewall;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Creates blocked response for disallowed requests
 */
class BlockedResponseFactory
{
    /**
     * Create a blocked response
     *
     * @return PromiseInterface
     */
    public function create(): PromiseInterface
    {
        return new FulfilledPromise(
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                (string) json_encode(['status' => 'blocked', 'reason' => 'read-only mode'])
            )
        );
    }
}
