<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function testBuildQuery(): void
    {
        $this->assertEquals('foo=bar&baz=foo', Url::buildQuery(['foo' => 'bar', 'baz' => 'foo']));
        $this->assertEquals('', Url::buildQuery([]));
        $this->assertEquals('foo%5B0%5D=bar&foo%5B1%5D=baz', Url::buildQuery(['foo' => ['bar', 'baz']]));
        $this->assertEquals(
            'foo=http%3A%2F%2Fwww.vysokeskoly.cz%2F%3Fquery%3Dparam',
            Url::buildQuery(['foo' => 'http://www.vysokeskoly.cz/?query=param']),
        );
    }

    /**
     * @dataProvider provideUrlForBuild
     */
    public function testBuildUrl(string $expectedUrl, string $url, array $params): void
    {
        $this->assertEquals($expectedUrl, Url::buildUrlWithParams($url, $params));
    }

    public function provideUrlForBuild(): array
    {
        return [
            // url without params in it and without additional params
            ['http://www.vysokeskoly.cz/', 'http://www.vysokeskoly.cz/', []],
            // url without params, given additional params that have empty and non-empty values
            [
                'http://www.vysokeskoly.cz/?param1=&param2=val2&param3=0',
                'http://www.vysokeskoly.cz/',
                ['param1' => '', 'param2' => 'val2', 'param3' => '0'],
            ],
            // url with params, given additional param that has non-empty value
            [
                'http://www.vysokeskoly.cz/search/?param=value&param2=value2&param3=value3',
                'http://www.vysokeskoly.cz/search/?param=value&param2=value2',
                ['param3' => 'value3'],
            ],
        ];
    }

    /**
     * @dataProvider provideParameterForRemove
     */
    public function testShouldRemoveParameterIfItIsPresent(string $originalUrl, string $parameterToRemove, string $expectedUrl): void
    {
        $this->assertSame($expectedUrl, Url::removeParam($originalUrl, $parameterToRemove));
    }

    public function provideParameterForRemove(): array
    {
        return [
            // Standard URL with primitive param to be removed from multiple parameters
            [
                'http://www.vysokeskoly.cz/search/?keep=me&remove=me',
                'remove',
                'http://www.vysokeskoly.cz/search/?keep=me',
            ],
            // URL with only one primitive param that should be removed
            [
                'http://www.vysokeskoly.cz/search/?remove=me',
                'remove',
                'http://www.vysokeskoly.cz/search/',
            ],
            // URL without the parameter that should be removed should be kept the same
            [
                'http://www.vysokeskoly.cz/search/?foo=bar&ban=baz',
                'notpresent',
                'http://www.vysokeskoly.cz/search/?foo=bar&ban=baz',
            ],
            // URL without query part should also be kept the same
            [
                'http://www.vysokeskoly.cz/search/',
                'notpresent',
                'http://www.vysokeskoly.cz/search/',
            ],
            // URL with array parameter to be removed
            [
                'http://www.vysokeskoly.cz/search/?keep=me&remove[]=me&remove[]=metoo&keepme=to',
                'remove',
                'http://www.vysokeskoly.cz/search/?keep=me&keepme=to',
            ],
        ];
    }

    /**
     * @dataProvider provideUrlParameter
     */
    public function testShouldRetrievesParametersFromUrlToArray(string $url, array $expectedValue): void
    {
        $this->assertSame($expectedValue, Url::getParams($url));
    }

    public function provideUrlParameter(): array
    {
        return [
            ['http://www.vysokeskoly.cz/search/?foo=bar', ['foo' => 'bar']],
            ['http://www.vysokeskoly.cz/search/?foo=bar&bar=foo', ['foo' => 'bar', 'bar' => 'foo']],
            ['http://www.vysokeskoly.cz/search/?foo=&bar=', ['foo' => '', 'bar' => '']], // empty params
            ['http://www.vysokeskoly.cz/search/', []], // no params
            [
                'http://www.vysokeskoly.cz/search/?foo[]=val1&foo[]=val2&baz=ban',
                ['foo' => ['val1', 'val2'], 'baz' => 'ban'],
            ], // array param
        ];
    }
}
