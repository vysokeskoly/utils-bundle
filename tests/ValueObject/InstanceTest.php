<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\ValueObject;

use Assert\AssertionFailedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InstanceTest extends TestCase
{
    #[Test]
    public function shouldCreateInstance(): void
    {
        $instance = new Instance('domain', 'context', 'purpose', 'v1');

        $this->assertSame('domain', $instance->getDomain());
        $this->assertSame('context', $instance->getContext());
        $this->assertSame('purpose', $instance->getPurpose());
        $this->assertSame('v1', $instance->getVersion());
    }

    #[Test]
    #[DataProvider('provideValidValues')]
    public function shouldParseInstance(string $value, string $domain, string $context, string $purpose, string $version): void
    {
        $instance = Instance::parse($value);

        $this->assertSame($domain, $instance->getDomain());
        $this->assertSame($context, $instance->getContext());
        $this->assertSame($purpose, $instance->getPurpose());
        $this->assertSame($version, $instance->getVersion());
    }

    public static function provideValidValues(): array
    {
        return [
            'basic' => ['lmc-core-api-v1', 'lmc', 'core', 'api', 'v1'],
            'with numbers' => ['acme-billing-payment-2', 'acme', 'billing', 'payment', '2'],
            'purpose with dashes' => ['lmc-core-some-purpose-v2', 'lmc', 'core', 'some', 'purpose-v2'],
        ];
    }

    #[Test]
    public function shouldParseInstanceWithCustomSeparator(): void
    {
        $instance = Instance::parse('lmc_core_api_v1', '_');

        $this->assertSame('lmc', $instance->getDomain());
        $this->assertSame('core', $instance->getContext());
        $this->assertSame('api', $instance->getPurpose());
        $this->assertSame('v1', $instance->getVersion());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function shouldThrowOnInvalidValue(string $value): void
    {
        $this->expectException(AssertionFailedException::class);

        Instance::parse($value);
    }

    public static function provideInvalidValues(): array
    {
        return [
            'only one part' => ['lmc'],
            'only two parts' => ['lmc-core'],
            'only three parts' => ['lmc-core-api'],
            'empty string' => [''],
            'missing part (double dash)' => ['lmc--api-v1'],
        ];
    }

    #[Test]
    public function shouldConcatInstance(): void
    {
        $instance = new Instance('lmc', 'core', 'api', 'v1');

        $this->assertSame('lmc-core-api-v1', $instance->concat());
    }

    #[Test]
    public function shouldConcatWithCustomSeparator(): void
    {
        $instance = new Instance('lmc', 'core', 'api', 'v1');

        $this->assertSame('lmc_core_api_v1', $instance->concat('_'));
    }

    #[Test]
    public function shouldConcatLower(): void
    {
        $instance = new Instance('LMC', 'Core', 'API', 'V1');

        $this->assertSame('lmc-core-api-v1', $instance->concatLower());
    }

    #[Test]
    public function shouldConcatLowerWithCustomSeparator(): void
    {
        $instance = new Instance('LMC', 'Core', 'API', 'V1');

        $this->assertSame('lmc_core_api_v1', $instance->concatLower('_'));
    }

    #[Test]
    public function shouldConvertToString(): void
    {
        $instance = new Instance('lmc', 'core', 'api', 'v1');

        $this->assertSame('lmc-core-api-v1', (string) $instance);
    }

    #[Test]
    public function shouldGenerateK8sUrl(): void
    {
        $instance = new Instance('lmc', 'core', 'api', 'v1');

        $this->assertSame(
            'http://core-api-v1.lmc.svc.cluster.local',
            $instance->toK8sUrl(),
        );
    }

    #[Test]
    public function shouldGenerateK8sUrlWithPort(): void
    {
        $instance = new Instance('lmc', 'core', 'api', 'v1');

        $this->assertSame(
            'http://core-api-v1.lmc.svc.cluster.local:8080',
            $instance->toK8sUrl(8080),
        );
    }

    #[Test]
    public function shouldGenerateK8sUrlLowercase(): void
    {
        $instance = new Instance('LMC', 'Core', 'API', 'V1');

        $this->assertSame(
            'http://core-api-v1.lmc.svc.cluster.local',
            $instance->toK8sUrl(),
        );
    }

    #[Test]
    public function shouldRoundTripParseAndConcat(): void
    {
        $original = 'lmc-core-api-v1';
        $instance = Instance::parse($original);

        $this->assertSame($original, $instance->concat());
        $this->assertSame($original, (string) $instance);
    }
}
