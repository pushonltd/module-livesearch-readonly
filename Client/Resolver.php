<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Client;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\ServicesConnector\Api\ClientResolverInterface;
use Magento\ServicesConnector\Api\JwtTokenInterface;
use Magento\ServicesConnector\Model\GuzzleClientFactory;
use Psr\Http\Message\RequestInterface;
use PushON\LiveSearchReadOnly\Config\Credentials;
use PushON\LiveSearchReadOnly\Firewall\BlockingMiddlewareFactory;

/**
 * Custom client resolver for LiveSearch with separate credentials
 */
class Resolver implements ClientResolverInterface
{
    private const GATEWAY_URL_PATH = 'services_connector/{env}_gateway_url';
    private const DEFAULT_GATEWAY_URL = 'https://commerce.adobe.io/';

    /**
     * @param Credentials $credentials
     * @param ClientResolverInterface $originalResolver
     * @param JwtTokenInterface $jwtToken
     * @param GuzzleClientFactory $clientFactory
     * @param ProductMetadataInterface $productMetadata
     * @param ScopeConfigInterface $scopeConfig
     * @param BlockingMiddlewareFactory $blockingMiddlewareFactory
     */
    public function __construct(
        private readonly Credentials $credentials,
        private readonly ClientResolverInterface $originalResolver,
        private readonly JwtTokenInterface $jwtToken,
        private readonly GuzzleClientFactory $clientFactory,
        private readonly ProductMetadataInterface $productMetadata,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly BlockingMiddlewareFactory $blockingMiddlewareFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createHttpClient(
        $extension,
        $environment = 'production',
        $hostname = '',
        $scopes = [],
        $middlewares = []
    ) {
        if (!$this->credentials->isEnabled()) {
            return $this->originalResolver->createHttpClient(
                $extension,
                $environment,
                $hostname,
                $scopes,
                $middlewares
            );
        }

        if (!$this->hasCustomCredentials() && $this->credentials->isFallbackEnabled()) {
            return $this->originalResolver->createHttpClient(
                $extension,
                $environment,
                $hostname,
                $scopes,
                $middlewares
            );
        }

        $stack = HandlerStack::create();
        $stack->push($this->blockingMiddlewareFactory->create(), 'block_non_search');
        $stack->push($this->createAuthMiddleware(), 'auth');

        foreach ($middlewares as $middleware) {
            // @phpstan-ignore argument.type
            $stack->push($middleware);
        }

        return $this->clientFactory->create([
            RequestOptions::HTTP_ERRORS => false,
            'base_uri' => $hostname ?: $this->getGatewayUrl($environment),
            'handler' => $stack,
            'headers' => ['User-Agent' => $this->buildUserAgent()],
        ]);
    }

    /**
     * Check if custom credentials are configured
     *
     * @return bool
     */
    private function hasCustomCredentials(): bool
    {
        return $this->credentials->getApiKey() && $this->credentials->getPrivateKey();
    }

    /**
     * Get gateway URL for environment
     *
     * @param string $environment
     * @return string
     */
    private function getGatewayUrl(string $environment): string
    {
        $environment = $environment ?: 'production';
        $path = str_replace('{env}', $environment, self::GATEWAY_URL_PATH);

        return $this->scopeConfig->getValue($path) ?: self::DEFAULT_GATEWAY_URL;
    }

    /**
     * Create authentication middleware with custom credentials
     *
     * @return callable
     */
    private function createAuthMiddleware(): callable
    {
        $apiKey = (string) $this->credentials->getApiKey();
        $privateKey = (string) $this->credentials->getPrivateKey();
        $signature = $this->jwtToken->getSignature($privateKey);

        return Middleware::mapRequest(
            fn(RequestInterface $request) => $request
                ->withHeader('magento-api-key', $apiKey)
                ->withHeader('x-api-key', $apiKey)
                ->withHeader('x-gw-signature', $signature)
        );
    }

    /**
     * Build user agent string matching Magento's format
     *
     * @return string
     */
    private function buildUserAgent(): string
    {
        return sprintf(
            'Magento Services Connector (Magento: %s)',
            $this->productMetadata->getEdition() . ' ' . $this->productMetadata->getVersion()
        );
    }
}
