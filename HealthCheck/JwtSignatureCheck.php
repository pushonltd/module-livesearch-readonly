<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\HealthCheck;

use Magento\ServicesConnector\Api\JwtTokenInterface;
use PushON\LiveSearchReadOnly\Config\Credentials;

/**
 * Check JWT signature generation
 */
class JwtSignatureCheck implements CheckInterface
{
    /**
     * @param Credentials $credentials
     * @param JwtTokenInterface $jwtToken
     */
    public function __construct(
        private readonly Credentials $credentials,
        private readonly JwtTokenInterface $jwtToken
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(): CheckResult
    {
        if (!$this->credentials->isEnabled()) {
            return new CheckResult('JWT Signature', [], [], true);
        }

        $privateKey = $this->credentials->getPrivateKey();
        if (!$privateKey) {
            $passed = $this->credentials->isFallbackEnabled();
            return new CheckResult('JWT Signature', [], [], $passed);
        }

        try {
            $signature = $this->jwtToken->getSignature($privateKey);

            if (!$signature) {
                return new CheckResult(
                    'JWT Signature',
                    [new StatusLine('Signature Generation', false, 'EMPTY RESULT')],
                    [],
                    false
                );
            }

            $parts = explode('.', $signature);
            if (count($parts) !== 3) {
                return new CheckResult(
                    'JWT Signature',
                    [new StatusLine('JWT Structure', false, 'INVALID')],
                    [],
                    false
                );
            }

            $statuses = [new StatusLine('Signature Generation', true)];
            $messages = [];

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $payload = json_decode(base64_decode($parts[1]), true);
            if ($payload && isset($payload['exp'])) {
                $expiry = date('Y-m-d H:i:s', $payload['exp']);
                $isExpired = $payload['exp'] < time();
                $statuses[] = new StatusLine(
                    'Token Expiry',
                    !$isExpired,
                    $expiry . ($isExpired ? ' (EXPIRED)' : '')
                );
            }

            if ($payload && isset($payload['iss'])) {
                $messages[] = sprintf('Issuer: %s', $payload['iss']);
            }

            $messages[] = 'Token is auto-regenerated from private key on each request';

            return new CheckResult('JWT Signature', $statuses, $messages, true);
        } catch (\Exception $e) {
            return new CheckResult(
                'JWT Signature',
                [new StatusLine('Signature Generation', false, 'FAILED')],
                [
                    'error:' . $e->getMessage(),
                    'Common causes:',
                    '• Private key format is invalid',
                    '• Private key was corrupted during storage',
                    '• Try re-saving the private key in admin',
                ],
                false
            );
        }
    }
}
