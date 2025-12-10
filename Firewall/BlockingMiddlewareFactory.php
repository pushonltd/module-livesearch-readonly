<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Firewall;

use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Creates middleware that blocks non-search requests
 */
class BlockingMiddlewareFactory
{
    /**
     * @param RequestValidator $requestValidator
     * @param BlockedResponseFactory $blockedResponseFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestValidator $requestValidator,
        private readonly BlockedResponseFactory $blockedResponseFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Create blocking middleware
     *
     * @return callable
     */
    public function create(): callable
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $path = $request->getUri()->getPath();

                if ($this->requestValidator->isAllowed($path)) {
                    return $handler($request, $options);
                }

                $this->logger->warning('[LiveSearchReadOnly] Blocked non-search request', [
                    'method' => $request->getMethod(),
                    'uri' => (string) $request->getUri(),
                ]);

                return $this->blockedResponseFactory->create();
            };
        };
    }
}
