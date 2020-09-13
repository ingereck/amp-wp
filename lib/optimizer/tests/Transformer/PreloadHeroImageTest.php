<?php

namespace AmpProject\Optimizer\Transformer;

use AmpProject\Dom\Document;
use AmpProject\Optimizer\Configuration\PreloadHeroImageConfiguration;
use AmpProject\Optimizer\Error;
use AmpProject\Optimizer\ErrorCollection;
use AmpProject\Optimizer\Tests\ErrorComparison;
use AmpProject\Optimizer\Tests\MarkupComparison;
use AmpProject\Optimizer\Tests\TestMarkup;
use PHPUnit\Framework\TestCase;

/**
 * Test the PreloadHeroImage transformer.
 *
 * @package ampproject/optimizer
 */
final class PreloadHeroImageTest extends TestCase
{
    use ErrorComparison;
    use MarkupComparison;

    /**
     * Provide the data to test the transform() method.
     *
     * @return array[] Associative array of data arrays.
     */
    public function dataTransform()
    {
        $input = static function ($body, $preloads = '') {
            return TestMarkup::DOCTYPE . '<html ⚡><head>'
                   . TestMarkup::META_CHARSET . TestMarkup::META_VIEWPORT . $preloads . TestMarkup::SCRIPT_AMPRUNTIME
                   . TestMarkup::LINK_FAVICON . TestMarkup::LINK_CANONICAL . TestMarkup::STYLE_AMPBOILERPLATE . TestMarkup::NOSCRIPT_AMPBOILERPLATE
                   . '</head><body>'
                   . $body
                   . '</body></html>';
        };

        $output = static function ($body, $preloads = '') {
            return TestMarkup::DOCTYPE . '<html ⚡ i-amphtml-layout=""><head>'
                   . TestMarkup::META_CHARSET . TestMarkup::META_VIEWPORT . $preloads . TestMarkup::SCRIPT_AMPRUNTIME
                   . TestMarkup::LINK_FAVICON . TestMarkup::LINK_CANONICAL . TestMarkup::STYLE_AMPBOILERPLATE . TestMarkup::NOSCRIPT_AMPBOILERPLATE
                   . '</head><body>'
                   . $body
                   . '</body></html>';
        };

        return [
            'throws error when past data-hero maximum' => [
                $input(
                    '<amp-img width="500" height="400" src="/foo.png"></amp-img>'
                    . '<amp-img data-hero width="500" height="400" src="/hero1.png"></amp-img>'
                    . '<amp-img data-hero width="500" height="400" src="/hero2.png"></amp-img>'
                    . '<amp-img data-hero width="500" height="400" src="/hero3.png"></amp-img>'
                    . '<amp-img data-hero width="500" height="400" src="/hero4.png"></amp-img>'
                    . '<amp-img data-hero width="500" height="400" src="/hero5.png"></amp-img>'
                ),
                $output(
                    '<amp-img width="500" height="400" src="/foo.png"></amp-img>'
                    . '<amp-img data-hero width="500" height="400" src="/hero1.png" i-amphtml-ssr><img class="i-amphtml-fill-content i-amphtml-replaced-content" decoding="async" src="/hero1.png"></amp-img>'
                    . '<amp-img data-hero width="500" height="400" src="/hero2.png" i-amphtml-ssr><img class="i-amphtml-fill-content i-amphtml-replaced-content" decoding="async" src="/hero2.png"></amp-img>'
                    . '<amp-img data-hero width="500" height="400" src="/hero3.png"></amp-img>'
                    . '<amp-img data-hero width="500" height="400" src="/hero4.png"></amp-img>'
                    . '<amp-img data-hero width="500" height="400" src="/hero5.png"></amp-img>',
                    '<link rel=preload href="/hero1.png" as="image" data-hero>'
                    . '<link rel=preload href="/hero2.png" as="image" data-hero>'
                ),
                [
                    Error\TooManyHeroImages::whenPastMaximum(),
                ],
            ],

            'throws error when scrset detected on image to be preloaded' => [
                $input(
                    '<amp-img width="500" height="400" src="https://example-com.cdn.ampproject.org/foo.png" srcset="test 100w test2 3dpr"></amp-img>'
                    . '<amp-img width="500" height="400" src="https://example-com.cdn.ampproject.org/foo.png"></amp-img>'
                ),
                $output(
                    '<amp-img width="500" height="400" src="https://example-com.cdn.ampproject.org/foo.png" srcset="test 100w test2 3dpr" i-amphtml-ssr data-hero>'
                    . '<img class="i-amphtml-fill-content i-amphtml-replaced-content" decoding="async" src="https://example-com.cdn.ampproject.org/foo.png" srcset="test 100w test2 3dpr">'
                    . '</amp-img>'
                    . '<amp-img width="500" height="400" src="https://example-com.cdn.ampproject.org/foo.png" srcset="test 100w test2 3dpr" i-amphtml-ssr data-hero></amp-img>'
                ),
                [
                    Error\CannotPreloadImage::fromImageWithSrcsetAttribute(
                        Document::fromHtmlFragment(
                            '<amp-img width="500" height="400" src="https://example-com.cdn.ampproject.org/foo.png" srcset="test 100w test2 3dpr">'
                        )->body->firstChild
                    ),
                ],
            ],
        ];
    }

    /**
     * Test the transform() method.
     *
     * @covers       \AmpProject\Optimizer\Transformer\PreloadHeroImage::transform()
     * @dataProvider dataTransform()
     *
     * @param string                  $source         String of source HTML.
     * @param string                  $expectedHtml   String of expected HTML output.
     * @param ErrorCollection|Error[] $expectedErrors Set of expected errors.
     * @param array                   $config         Configuration data to use.
     */
    public function testTransform($source, $expectedHtml, $expectedErrors = [], $config = [])
    {
        $document    = Document::fromHtml($source);
        $transformer = new PreloadHeroImage(new PreloadHeroImageConfiguration($config));
        $errors      = new ErrorCollection();

        $transformer->transform($document, $errors);

        $this->assertSimilarMarkup($expectedHtml, $document->saveHTML());
        $this->assertSameErrors($expectedErrors, $errors);
    }
}