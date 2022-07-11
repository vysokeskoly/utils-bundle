<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class DebugLevelTest extends TestCase
{
    private DebugLevel $debugLevel;

    protected function setUp(): void
    {
        $this->debugLevel = new DebugLevel();
    }

    public function testShouldCheckDefaultDebugMode(): void
    {
        $this->assertSame(0, $this->debugLevel->getDebugLevel());
        $this->assertFalse($this->debugLevel->isDebug());
        $this->assertTrue($this->debugLevel->isCacheEnabled());
    }

    /**
     * @dataProvider provideLevel
     */
    public function testShouldCheckAllDebugModes(
        int $level,
        int $expectedLevel,
        bool $isDebug,
        bool $isCacheEnabled
    ): void {
        $this->debugLevel->setLevel($level);

        $this->assertSame($expectedLevel, $this->debugLevel->getDebugLevel());
        $this->assertSame($isDebug, $this->debugLevel->isDebug());
        $this->assertSame($isCacheEnabled, $this->debugLevel->isCacheEnabled());
    }

    public function provideLevel(): array
    {
        return [
            // debug level, expected debug level, is debug?, is cache enabled?
            'debug off' => [0, 0, false, true],
            'debug on, cache on' => [1, 1, true, true],
            'debug on, cache off' => [2, 2, true, false],
            'invalid value - unsupported int' => [666, 0, false, true],
        ];
    }
}
