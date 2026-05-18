<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    #[Test]
    #[DataProvider('provideEnvironments')]
    public function shouldParseEnvironmentFromAlias(string $alias, Tier $expectedTier, ?int $expectedNumber, string $expectedSpace): void
    {
        $environment = Environment::parse($alias);

        $this->assertSame($expectedTier, $environment->getTier());
        $this->assertSame($expectedNumber, $environment->getNumber());
        $this->assertSame($expectedSpace, $environment->getSpace());
        $this->assertSame($alias, $environment->toAlias());
    }

    public static function provideEnvironments(): array
    {
        return [
            'dev with number and space' => ['dev1-eduroute', Tier::Dev, 1, 'eduroute'],
            'prod with number and space' => ['prod1-eduroute', Tier::Prod, 1, 'eduroute'],
            'dev with number, no space' => ['dev21', Tier::Dev, 21, ''],
            'prod, no number, no space' => ['prod', Tier::Prod, null, ''],
            'deploy with space' => ['deploy-services', Tier::Deploy, null, 'services'],
            'dev with number and services' => ['dev1-services', Tier::Dev, 1, 'services'],
            'devel with number' => ['devel3', Tier::Devel, 3, ''],
            'int no number' => ['int', Tier::Integration, null, ''],
            'int with space' => ['int-rad', Tier::Integration, null, 'rad'],
            'prod with space' => ['prod-rad', Tier::Prod, null, 'rad'],
        ];
    }

    #[Test]
    public function shouldCreateEnvironment(): void
    {
        $environment = Environment::create(Tier::Dev, 1, 'eduroute');

        $this->assertSame('dev1-eduroute', $environment->toAlias());
        $this->assertSame('dev1-eduroute', (string) $environment);
    }

    #[Test]
    public function shouldCheckTierConvenienceMethods(): void
    {
        $dev = Environment::parse('dev1-eduroute');
        $prod = Environment::parse('prod');

        $this->assertTrue($dev->isDev());
        $this->assertFalse($dev->isProd());
        $this->assertFalse($dev->isDeploy());
        $this->assertFalse($dev->isIntegration());
        $this->assertFalse($dev->isDevel());

        $this->assertTrue($prod->isProd());
        $this->assertFalse($prod->isDev());
    }

    #[Test]
    public function shouldCompareEquality(): void
    {
        $env1 = Environment::parse('dev1-eduroute');
        $env2 = Environment::create(Tier::Dev, 1, 'eduroute');

        $this->assertTrue($env1->equals($env2));
    }

    #[Test]
    public function shouldThrowOnInvalidAlias(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse environment alias "invalid!".');

        Environment::parse('invalid!');
    }

    #[Test]
    #[DataProvider('provideInvalidAliases')]
    public function shouldThrowOnUnknownTier(string $alias): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Environment::parse($alias);
    }

    public static function provideInvalidAliases(): array
    {
        return [
            'unknown tier' => ['staging1'],
            'empty string' => [''],
            'special chars' => ['dev1@space'],
        ];
    }
}
