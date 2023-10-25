<?php declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\Service;

use PHPUnit\Framework\TestCase;
use VysokeSkoly\UtilsBundle\Entity\Html\Image;
use VysokeSkoly\UtilsBundle\Entity\Html\Link;

/**
 * @group unit
 */
class HtmlHelperTest extends TestCase
{
    private HtmlHelper $htmlHelper;

    protected function setUp(): void
    {
        $this->htmlHelper = new HtmlHelper();
    }

    /**
     * @dataProvider imagesProvider
     */
    public function testShouldGetImages(string $content, array $expectedImages): void
    {
        $images = $this->htmlHelper->findAllImages($content);

        $this->assertEquals($expectedImages, $images);

        foreach (array_keys($images) as $needle) {
            $this->assertStringContainsString($needle, $content);
        }
    }

    public function imagesProvider(): array
    {
        return [
            // content, imageSources
            'without image' => ['content', []],
            '1 image' => [
                '<div>content<img src="image.jpg"></div>',
                [
                    ' src="image.jpg"' => new Image(['src' => 'image.jpg']),
                ],
            ],
            'image with space in src' => [
                '<div>content<img src="nice image.jpg"></div>',
                [
                    ' src="nice image.jpg"' => new Image(['src' => 'nice image.jpg']),
                ],
            ],
            'image with space in src and alt' => [
                '<div>content<img alt="alt" src="nice image.jpg"></div>',
                [
                    ' alt="alt" src="nice image.jpg"' => new Image(['src' => 'nice image.jpg', 'alt' => 'alt']),
                ],
            ],
            'image with space in src, alt and style' => [
                '<div>content<img alt="alt" src="nice image.jpg" style="width:400px; height:200px;"></div>',
                [
                    ' alt="alt" src="nice image.jpg" style="width:400px; height:200px;"' => new Image([
                        'src' => 'nice image.jpg',
                        'alt' => 'alt',
                        'style' => 'width:400px; height:200px;',
                    ]),
                ],
            ],
            'many images' => [
                '<div>content<img src="image.jpg" alt="1"></div><p><img height="200" src="image2.jpg" /></p>',
                [
                    ' src="image.jpg" alt="1"' => new Image(['src' => 'image.jpg', 'alt' => '1']),
                    ' height="200" src="image2.jpg" /' => new Image(['height' => '200', 'src' => 'image2.jpg']),
                ],
            ],
            'many images lined' => [
                '<figure class="wp-block-image size-large is-resized">
                    content
                    <img decoding="async"
                         loading="lazy"
                         src="https://cdn.vysokeskoly.cz/vysokeskolyuk/new/uploads/2023/03/diablo-4-1920-1080-618x348.jpeg"
                         alt=""
                         class="wp-image-19"
                         width="650"
                         height="366"
                         srcset="https://cdn.vysokeskoly.cz/vysokeskolyuk/new/uploads/2023/03/diablo-4-1920-1080-618x348.jpeg 618w, https://cdn.vysokeskoly.cz/vysokeskolyuk/new/uploads/2023/03/diablo-4-1920-1080-596x335.jpeg 596w"
                         sizes="(max-width: 650px) 100vw, 650px" />
                </figure>
                <p>
                    <img 
                        height="200" src="image2.jpg"
                        alt="image 2" 
                    />
                </p>',
                [
                    ' decoding="async"
                         loading="lazy"
                         src="https://cdn.vysokeskoly.cz/vysokeskolyuk/new/uploads/2023/03/diablo-4-1920-1080-618x348.jpeg"
                         alt=""
                         class="wp-image-19"
                         width="650"
                         height="366"
                         srcset="https://cdn.vysokeskoly.cz/vysokeskolyuk/new/uploads/2023/03/diablo-4-1920-1080-618x348.jpeg 618w, https://cdn.vysokeskoly.cz/vysokeskolyuk/new/uploads/2023/03/diablo-4-1920-1080-596x335.jpeg 596w"
                         sizes="(max-width: 650px) 100vw, 650px" /' => new Image(
                        [
                            'decoding' => 'async',
                            'loading' => 'lazy',
                            'src' => 'https://cdn.vysokeskoly.cz/vysokeskolyuk/new/uploads/2023/03/diablo-4-1920-1080-618x348.jpeg',
                            'alt' => '',
                            'class' => 'wp-image-19',
                            'width' => '650',
                            'height' => '366',
                            'srcset' => 'https://cdn.vysokeskoly.cz/vysokeskolyuk/new/uploads/2023/03/diablo-4-1920-1080-618x348.jpeg 618w, https://cdn.vysokeskoly.cz/vysokeskolyuk/new/uploads/2023/03/diablo-4-1920-1080-596x335.jpeg 596w',
                            'sizes' => '(max-width: 650px) 100vw, 650px',
                        ],
                    ),
                    ' 
                        height="200" src="image2.jpg"
                        alt="image 2" 
                    /' => new Image(
                        [
                            'height' => '200',
                            'src' => 'image2.jpg',
                            'alt' => 'image 2',
                        ],
                    ),
                ],
            ],
        ];
    }

    /**
     * @dataProvider tagProvider
     */
    public function testShouldTransformToHtmlTag(
        string $tag,
        array $parameters,
        bool $isSingleTag,
        string $expected,
    ): void {
        $this->assertSame($expected, $this->htmlHelper->transformToTag($tag, $parameters, $isSingleTag));
    }

    public function tagProvider(): array
    {
        return [
            // tag, parameters, isSingleTag, expected
            'img' => [
                'img',
                (new Image(['src' => 'image.jpg']))->getParameters(),
                true,
                '<img alt="" src="image.jpg" />',
            ],
            'img with alt' => [
                'img',
                (new Image(['src' => 'image.jpg', 'alt' => 'Alternative']))->getParameters(),
                true,
                '<img alt="Alternative" src="image.jpg" />',
            ],
            'link' => [
                'a',
                (new Link(['href' => 'index.html', 'title' => 'Title']))->getParameters(),
                false,
                '<a href="index.html" title="Title">',
            ],
        ];
    }

    public function testShouldReplaceAllImageSourcesInContent(): void
    {
        $content = '<div>content<img src="image.jpg" alt="1"></div><p><img height="200" src="image2.jpg" /></p>';

        $srcsReplacements = [
            ' src="image.jpg" alt="1"' => ' alt="1" src="img.jpg" /',
            ' height="200" src="image2.jpg" /' => ' alt="" height="200" src="img2.jpg" /',
        ];

        $expected = '<div>content<img alt="1" src="img.jpg" /></div><p><img alt="" height="200" src="img2.jpg" /></p>';

        $this->assertEquals($expected, $this->htmlHelper->replaceAllInContent($content, $srcsReplacements));
    }

    /**
     * @dataProvider linksProvider
     */
    public function testShouldGetAllLinksFromContent(string $content, array $expectedLinks): void
    {
        $links = $this->htmlHelper->findAllLinks($content);

        $this->assertEquals($expectedLinks, $links);

        foreach (array_keys($links) as $needle) {
            $this->assertStringContainsString($needle, $content);
        }
    }

    public function linksProvider(): array
    {
        return [
            // content, expectedLinks
            'without link' => [
                '<div>content</div>',
                [],
            ],
            'with one link' => [
                '<div>content<a href="$$slovnik:prezencni-studium$$"></a></div>',
                [
                    ' href="$$slovnik:prezencni-studium$$"' => new Link(['href' => '$$slovnik:prezencni-studium$$']),
                ],
            ],
            'with one link without a quotes' => [
                '<div>content<a href=https://www.vysokeskoly.cz></div>',
                [
                    ' href=https://www.vysokeskoly.cz' => new Link(['href' => 'https://www.vysokeskoly.cz']),
                ],
            ],
            'with one link without a quotes but with class before' => [
                '<div>content<a class="active" href=https://www.vysokeskoly.cz></div>',
                [
                    ' class="active" href=https://www.vysokeskoly.cz' => new Link([
                        'class' => 'active',
                        'href' => 'https://www.vysokeskoly.cz',
                    ]),
                ],
            ],
            'with link with multiclasses' => [
                "<a class='pager__item pager__item--active' href='?page=1'>1</a>",
                [
                    " class='pager__item pager__item--active' href='?page=1'" => new Link([
                        'class' => 'pager__item pager__item--active',
                        'href' => '?page=1',
                    ]),
                ],
            ],
            'with more links' => [
                '<div>content<a href="$$slovnik:prezencni-studium$$"></a> ' .
                '<a href="$$slovnik:dalkove-studium$$" title="Dálkové studium"></a></div>',
                [
                    ' href="$$slovnik:prezencni-studium$$"' => new Link(['href' => '$$slovnik:prezencni-studium$$']),
                    ' href="$$slovnik:dalkove-studium$$" title="Dálkové studium"' => new Link([
                        'href' => '$$slovnik:dalkove-studium$$',
                        'title' => 'Dálkové studium',
                    ]),
                ],
            ],
            'with different links' => [
                '<div>content<a href="/clanek/123"></a> ' .
                '<a href="$$slovnik:dalkove-studium$$" title="Dálkové studium"></a></div>',
                [
                    ' href="/clanek/123"' => new Link(['href' => '/clanek/123']),
                    ' href="$$slovnik:dalkove-studium$$" title="Dálkové studium"' => new Link([
                        'href' => '$$slovnik:dalkove-studium$$',
                        'title' => 'Dálkové studium',
                    ]),
                ],
            ],
            'empty link' => ['<div>content<a>Foo</a></div>', []],
            'with one link without a quotes but with class' => [
                '<div>content<a href=https://www.vysokeskoly.cz class="active"></div>',
                [
                    ' href=https://www.vysokeskoly.cz class="active"' => new Link([
                        'href' => 'https://www.vysokeskoly.cz',
                        'class' => 'active',
                    ]),
                ],
            ],
            'with one link containing not encoded entities' => [
                '<div>content<a href="https://www.vysokeskoly.cz?param1=1&param2=2" class="active"></div>',
                [
                    ' href="https://www.vysokeskoly.cz?param1=1&param2=2" class="active"' => new Link([
                        'href' => 'https://www.vysokeskoly.cz?param1=1&param2=2',
                        'class' => 'active',
                    ]),
                ],
            ],
            'with one link containing both encoded and not encoded entities' => [
                '<div>content<a href="https://www.vysokeskoly.cz?param1=1&param2=2&amp;param3=3" class="active"></div>',
                [
                    ' href="https://www.vysokeskoly.cz?param1=1&param2=2&amp;param3=3" class="active"' => new Link([
                        'href' => 'https://www.vysokeskoly.cz?param1=1&param2=2&param3=3',
                        'class' => 'active',
                    ]),
                ],
            ],
            'with multi line link' => [
                '<figure class="wp-block-image size-large is-resized">
                    content
                    <a 
                        href=https://www.vysokeskoly.cz 
                        class="active"
                        title="Homepage"
                    >
                        Homepage
                    </a>
                </figure>',
                [
                    ' 
                        href=https://www.vysokeskoly.cz 
                        class="active"
                        title="Homepage"
                    ' => new Link([
                        'href' => 'https://www.vysokeskoly.cz',
                        'class' => 'active',
                        'title' => 'Homepage',
                    ]),
                ],
            ],
        ];
    }

    public function testShouldGetAllParagraphsFromHtml(): void
    {
        $html = '<p>john snow</p> is:' .
            '<ul><li>brave</li><li>hot</li><li>strong</li></ul>' .
            '<p class="temperature:cold">winter is</p>coming' .
            '<p>howg</p>';

        $paragraphs = [
            '<p>john snow</p>',
            '<p class="temperature:cold">winter is</p>',
            '<p>howg</p>',
        ];

        $this->assertSame($paragraphs, $this->htmlHelper->findAllParagraphs($html));
    }

    /**
     * @dataProvider firstReplacementProvider
     */
    public function testShouldInsertTextAfterFirstOccurrence(
        string $content,
        string $replacement,
        ?string $search,
        string $expectedContent,
    ): void {
        $this->assertSame($expectedContent, $this->htmlHelper->insertAfterFirst($content, $replacement, $search));
    }

    public function firstReplacementProvider(): array
    {
        return [
            // content, replacement, search for, expected replaced content
            'no occurrence' => ['there is nothing to replace', ':(', null, 'there is nothing to replace:('],
            'one' => ['john snow is not dead', 'board', 'snow', 'john snowboard is not dead'],
            'two' => ['john snow eats snow', 'board', 'snow', 'john snowboard eats snow'],
            'html with mb characters' => [
                'content' => '<p>Žluťoučký kůň se pase 🐴</p>' .
                    '<p> </p>' .
                    '<p>Second paragraph</p>' .
                    '<p> </p>' .
                    '<p>Last paragraph</p>',
                'replacement' => '{-top-}',
                'search for' => '<p> </p>',
                'expected' => '<p>Žluťoučký kůň se pase 🐴</p>' .
                    '<p> </p>{-top-}' .
                    '<p>Second paragraph</p>' .
                    '<p> </p>' .
                    '<p>Last paragraph</p>',
            ],
        ];
    }

    /**
     * @dataProvider lastReplacementProvider
     */
    public function testShouldInsertTextAfterLastOccurrence(
        string $content,
        string $replacement,
        string $search,
        string $expectedContent,
    ): void {
        $this->assertSame($expectedContent, $this->htmlHelper->insertAfterLast($content, $replacement, $search));
    }

    public function lastReplacementProvider(): array
    {
        return [
            // content, replacement, search for, expected replaced content
            'no occurrence - empty string' => ['there is nothing to replace', ':(', '', 'there is nothing to replace'],
            'no occurrence' => ['there is nothing to replace', ':(', 'X', 'there is nothing to replace'],
            'one' => ['john snow is not dead', 'board', 'snow', 'john snowboard is not dead'],
            'two' => ['john snow eats snow', 'board', 'snow', 'john snow eats snowboard'],
            'html with mb characters' => [
                'content' => '<p>Žluťoučký kůň se pase 🐴</p>' .
                    '<p> </p>' .
                    '<p>Second paragraph</p>' .
                    '<p> </p>' .
                    '<p>Last paragraph</p>',
                'replacement' => '{-bottom-}',
                'search for' => '<p> </p>',
                'expected' => '<p>Žluťoučký kůň se pase 🐴</p>' .
                    '<p> </p>' .
                    '<p>Second paragraph</p>' .
                    '<p> </p>{-bottom-}' .
                    '<p>Last paragraph</p>',
            ],
        ];
    }
}
