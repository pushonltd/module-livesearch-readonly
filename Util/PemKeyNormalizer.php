<?php

declare(strict_types=1);

namespace PushON\LiveSearchReadOnly\Util;

/**
 * Normalizes PEM keys by ensuring proper line breaks
 *
 * When PEM keys are stored in config, newlines may be stripped.
 * This utility restores proper PEM formatting.
 */
class PemKeyNormalizer
{
    private const PEM_LINE_LENGTH = 64;
    private const PEM_PATTERN = '/^(-----BEGIN [^-]+-----)(.+)(-----END [^-]+-----)$/s';

    /**
     * Normalize PEM key format
     *
     * @param string $key
     * @return string
     */
    public function normalize(string $key): string
    {
        if ($this->hasNewlines($key)) {
            return $key;
        }

        if (!preg_match(self::PEM_PATTERN, $key, $matches)) {
            return $key;
        }

        return $this->formatPemKey($matches[1], $matches[2], $matches[3]);
    }

    /**
     * Check if key already has newlines
     *
     * @param string $key
     * @return bool
     */
    private function hasNewlines(string $key): bool
    {
        return str_contains($key, "\n");
    }

    /**
     * Format PEM key with proper line breaks
     *
     * @param string $header
     * @param string $content
     * @param string $footer
     * @return string
     */
    private function formatPemKey(string $header, string $content, string $footer): string
    {
        $wrappedContent = wordwrap($content, self::PEM_LINE_LENGTH, "\n", true);

        return $header . "\n" . $wrappedContent . "\n" . $footer;
    }
}
