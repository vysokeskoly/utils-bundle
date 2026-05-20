<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use Lmc\Cqrs\Types\QueryFetcherInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class CloudflareHelperTest extends TestCase
{
    private const CLOUDFLARE_ISSUER = 'https://test.cloudflareaccess.com';
    private const OTHER_ISSUER = 'https://other-tenant.cloudflareaccess.com';

    private CloudflareHelper $cloudflareHelper;

    protected function setUp(): void
    {
        $this->cloudflareHelper = new CloudflareHelper(
            $this->createMock(LoggerInterface::class),
            $this->createMock(QueryFetcherInterface::class),
            $this->createMock(RequestFactoryInterface::class),
        );
    }

    private function prepareJwt(string $iss = self::CLOUDFLARE_ISSUER): string
    {
        $jsonEncode = fn(array $data) => json_encode($data, JSON_THROW_ON_ERROR);

        $header = Base64::urlEncode($jsonEncode(['alg' => 'RS256', 'kid' => 'test']));

        $payload = Base64::urlEncode($jsonEncode([
            'aud' => ['123456'],
            'email' => 'email@gmail.com',
            'exp' => 1779344759,
            'iat' => 1779258359,
            'nbf' => 1779258359,
            'iss' => $iss,
            'type' => 'app',
            'identity_nonce' => 'abc123',
            'sub' => '6cf0d3df-011e-41c1-9fe0-f2baaccabb24',
            'country' => 'CZ',
            'policy_id' => '03a36644-888f-4c28-b96f-f9f18f88dc08',
        ]));

        return $header . '.' . $payload . '.fake-signature';
    }

    // --- isValidIssuer ---

    public function testShouldReturnTrueForValidCloudflareIssuer(): void
    {
        $result = $this->cloudflareHelper->isValidIssuer($this->prepareJwt(), self::CLOUDFLARE_ISSUER);

        $this->assertTrue($result);
    }

    public function testShouldReturnFalseForDifferentIssuer(): void
    {
        $result = $this->cloudflareHelper->isValidIssuer($this->prepareJwt(), self::OTHER_ISSUER);

        $this->assertFalse($result);
    }

    public function testShouldNotAcceptForMalformedToken(): void
    {
        $result = $this->cloudflareHelper->isValidIssuer('not.a.valid.jwt.token', self::CLOUDFLARE_ISSUER);

        $this->assertFalse($result);
    }

    // --- isValidIssuerFromRequest ---

    public function testShouldReturnTrueForRequestWithValidCfAuthorizationCookie(): void
    {
        $request = new Request(cookies: [CloudflareHelper::COOKIE_CF_AUTHORIZATION => $this->prepareJwt()]);

        $result = $this->cloudflareHelper->isValidIssuerFromRequest($request, self::CLOUDFLARE_ISSUER);

        $this->assertTrue($result);
    }

    public function testShouldReturnFalseForRequestWithMissingCfAuthorizationCookie(): void
    {
        $request = new Request();

        $result = $this->cloudflareHelper->isValidIssuerFromRequest($request, self::CLOUDFLARE_ISSUER);

        $this->assertFalse($result);
    }

    public function testShouldReturnFalseForRequestWithWrongIssuerInCookie(): void
    {
        $request = new Request(cookies: [CloudflareHelper::COOKIE_CF_AUTHORIZATION => $this->prepareJwt()]);

        $result = $this->cloudflareHelper->isValidIssuerFromRequest($request, self::OTHER_ISSUER);

        $this->assertFalse($result);
    }

    public function testShouldReturnFalseForRequestWithMalformedCfAuthorizationCookie(): void
    {
        $request = new Request(cookies: [CloudflareHelper::COOKIE_CF_AUTHORIZATION => 'not.a.valid.jwt.token']);

        $result = $this->cloudflareHelper->isValidIssuerFromRequest($request, self::CLOUDFLARE_ISSUER);

        $this->assertFalse($result);
    }
}
