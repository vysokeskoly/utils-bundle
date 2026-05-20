<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Lmc\Cqrs\Types\QueryFetcherInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use VysokeSkoly\UtilsBundle\Exception\DecodingException;
use VysokeSkoly\UtilsBundle\Query\GetCFJwkQuery;

class CloudflareHelper
{
    public const COOKIE_CF_AUTHORIZATION = 'CF_Authorization';

    /**
     * @param QueryFetcherInterface<mixed, mixed> $queryFetcher
     */
    public function __construct(
        private LoggerInterface $logger,
        private QueryFetcherInterface $queryFetcher,
        private RequestFactoryInterface $requestFactory,
    ) {}

    public function isValidIssuerFromRequest(Request $request, string $expectedIssuer, bool $strict = false): bool
    {
        $jwt = $this->getAuthorizationJwtFromCookie($request);

        return $jwt
            ? $this->isValidIssuer($jwt, $expectedIssuer, $strict)
            : false;
    }

    private function getAuthorizationJwtFromCookie(Request $request): ?string
    {
        return $request->cookies->has(self::COOKIE_CF_AUTHORIZATION)
            ? $request->cookies->getString(self::COOKIE_CF_AUTHORIZATION)
            : null;
    }

    public function isValidIssuer(string $cfAuthorizationJwt, string $expectedIssuer, bool $strict = false): bool
    {
        try {
            $parts = explode('.', $cfAuthorizationJwt);

            if (count($parts) !== 3) {
                throw new DecodingException(
                    sprintf('Invalid JWT format: expected 3 parts, got %d.', count($parts)),
                );
            }

            $rawPayload = Base64::urlDecode($parts[1]);
            $payload = json_decode($rawPayload, true);

            if (!is_array($payload)) {
                throw new DecodingException('Failed to JSON-decode JWT payload.');
            }

            $iss = $payload['iss'] ?? '';
            if ($iss !== $expectedIssuer) {
                return false;
            }

            if ($strict) {
                return $this->verifySignature($parts, $iss);
            }

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to base64-decode JWT payload.', ['exception' => $e]);

            return false;
        }
    }

    /**
     * @param string[] $parts JWT parts [header, payload, signature]
     */
    private function verifySignature(array $parts, string $iss): bool
    {
        $jwks = $this->fetchJwks($iss);

        if (empty($jwks)) {
            $this->logger->error('Could not fetch JWKS for signature verification.', ['iss' => $iss]);

            return false;
        }

        $keySet = JWK::parseKeySet($jwks);
        $decoded = JWT::decode(implode('.', $parts), $keySet);

        return $decoded->iss === $iss;
    }

    private function fetchJwks(string $iss): array
    {
        try {
            $response = $this->queryFetcher->fetchAndReturn(
                new GetCFJwkQuery($this->requestFactory, $iss),
            );

            return is_array($response) ? $response : [];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch JWKS.', ['iss' => $iss, 'exception' => $e]);

            return [];
        }
    }
}
