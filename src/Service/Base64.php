<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use Assert\Assertion;

class Base64
{
    public static function encode(string $data): string
    {
        return base64_encode($data);
    }

    public static function decode(string $data): string
    {
        $decoded = base64_decode($data, true);
        Assertion::string($decoded, sprintf('Failed to base64-decode string "%s".', $data));

        return $decoded;
    }

    public static function urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function urlDecode(string $data): string
    {
        $length = strlen($data);
        $padded = str_pad(strtr($data, '-_', '+/'), $length + (4 - $length % 4) % 4, '=');

        $decoded = base64_decode($padded, true);
        Assertion::string($decoded, sprintf('Failed to base64url-decode string "%s".', $data));

        return $decoded;
    }
}
