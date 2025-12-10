<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Test\Unit\Client;

use GuzzleHttp\ClientInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\ServicesConnector\Api\ClientResolverInterface;
use Magento\ServicesConnector\Api\JwtTokenInterface;
use Magento\ServicesConnector\Model\GuzzleClientFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PushON\LiveSearchReadOnly\Client\Resolver;
use PushON\LiveSearchReadOnly\Config\Credentials;

class ResolverTest extends TestCase
{
    private MockObject&Credentials $credentials;
    private MockObject&ClientResolverInterface $originalResolver;
    private MockObject&JwtTokenInterface $jwtToken;
    private MockObject&GuzzleClientFactory $clientFactory;
    private MockObject&ProductMetadataInterface $productMetadata;
    private MockObject&ScopeConfigInterface $scopeConfig;
    private Resolver $resolver;

    protected function setUp(): void
    {
        $this->credentials = $this->createMock(Credentials::class);
        $this->originalResolver = $this->createMock(ClientResolverInterface::class);
        $this->jwtToken = $this->createMock(JwtTokenInterface::class);
        $this->clientFactory = $this->createMock(GuzzleClientFactory::class);
        $this->productMetadata = $this->createMock(ProductMetadataInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);

        $this->resolver = new Resolver(
            $this->credentials,
            $this->originalResolver,
            $this->jwtToken,
            $this->clientFactory,
            $this->productMetadata,
            $this->scopeConfig
        );
    }

    public function testDelegatesToOriginalWhenDisabled(): void
    {
        $this->credentials->method('isEnabled')->willReturn(false);

        $expectedClient = $this->createMock(ClientInterface::class);
        $this->originalResolver->expects($this->once())
            ->method('createHttpClient')
            ->with('Magento_LiveSearch', 'production', '', [], [])
            ->willReturn($expectedClient);

        $result = $this->resolver->createHttpClient('Magento_LiveSearch', 'production');

        $this->assertSame($expectedClient, $result);
    }

    public function testDelegatesToOriginalWhenFallbackEnabledAndNoCustomCredentials(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn(null);
        $this->credentials->method('getPrivateKey')->willReturn(null);
        $this->credentials->method('isFallbackEnabled')->willReturn(true);

        $expectedClient = $this->createMock(ClientInterface::class);
        $this->originalResolver->expects($this->once())
            ->method('createHttpClient')
            ->willReturn($expectedClient);

        $result = $this->resolver->createHttpClient('Magento_LiveSearch', 'production');

        $this->assertSame($expectedClient, $result);
    }

    public function testCreatesCustomClientWhenEnabled(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn('test-api-key');
        $this->credentials->method('getPrivateKey')->willReturn('test-private-key');

        $this->jwtToken->method('getSignature')
            ->with('test-private-key')
            ->willReturn('test-signature');

        $this->productMetadata->method('getEdition')->willReturn('Commerce');
        $this->productMetadata->method('getVersion')->willReturn('2.4.6');

        $this->scopeConfig->method('getValue')
            ->with('services_connector/production_gateway_url')
            ->willReturn('https://commerce.adobe.io/');

        $expectedClient = $this->createMock(ClientInterface::class);
        $this->clientFactory->expects($this->once())
            ->method('create')
            ->willReturn($expectedClient);

        $this->originalResolver->expects($this->never())->method('createHttpClient');

        $result = $this->resolver->createHttpClient('Magento_LiveSearch', 'production');

        $this->assertSame($expectedClient, $result);
    }

    public function testUsesDefaultGatewayUrlWhenNotConfigured(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn('test-api-key');
        $this->credentials->method('getPrivateKey')->willReturn('test-private-key');

        $this->jwtToken->method('getSignature')->willReturn('test-signature');
        $this->productMetadata->method('getEdition')->willReturn('Commerce');
        $this->productMetadata->method('getVersion')->willReturn('2.4.6');

        $this->scopeConfig->method('getValue')
            ->with('services_connector/production_gateway_url')
            ->willReturn(null);

        $this->clientFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($config) {
                return $config['base_uri'] === 'https://commerce.adobe.io/';
            }))
            ->willReturn($this->createMock(ClientInterface::class));

        $this->resolver->createHttpClient('Magento_LiveSearch', 'production');
    }

    public function testDefaultsToProductionEnvironmentWhenEmpty(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn('test-api-key');
        $this->credentials->method('getPrivateKey')->willReturn('test-private-key');

        $this->jwtToken->method('getSignature')->willReturn('test-signature');
        $this->productMetadata->method('getEdition')->willReturn('Commerce');
        $this->productMetadata->method('getVersion')->willReturn('2.4.6');

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('services_connector/production_gateway_url')
            ->willReturn('https://commerce.adobe.io/');

        $this->clientFactory->method('create')
            ->willReturn($this->createMock(ClientInterface::class));

        // Pass empty environment
        $this->resolver->createHttpClient('Magento_LiveSearch', '');
    }

    public function testUsesProvidedHostname(): void
    {
        $this->credentials->method('isEnabled')->willReturn(true);
        $this->credentials->method('getApiKey')->willReturn('test-api-key');
        $this->credentials->method('getPrivateKey')->willReturn('test-private-key');

        $this->jwtToken->method('getSignature')->willReturn('test-signature');
        $this->productMetadata->method('getEdition')->willReturn('Commerce');
        $this->productMetadata->method('getVersion')->willReturn('2.4.6');

        $customHostname = 'https://custom.gateway.io/';

        $this->clientFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($config) use ($customHostname) {
                return $config['base_uri'] === $customHostname;
            }))
            ->willReturn($this->createMock(ClientInterface::class));

        $this->resolver->createHttpClient('Magento_LiveSearch', 'production', $customHostname);
    }
}
