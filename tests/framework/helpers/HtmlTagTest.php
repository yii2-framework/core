<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\helpers;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\web\Request;
use yii\web\Response;
use yii\web\View;
use yiiunit\framework\helpers\providers\HtmlTagProvider;
use yiiunit\TestCase;

/**
 * Unit tests for {@see \yii\helpers\Html} helper HTML tag generation methods.
 *
 * {@see HtmlTagProvider} for test case data providers.
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */
#[Group('helpers')]
#[Group('html')]
final class HtmlTagTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApplication(
            [
                'components' => [
                    'request' => [
                        'class' => Request::class,
                        'url' => '/test',
                        'scriptUrl' => '/index.php',
                        'hostInfo' => 'http://www.example.com',
                        'enableCsrfValidation' => false,
                    ],
                    'response' => ['class' => Response::class],
                ],
            ],
        );
    }

    public function testEncode(): void
    {
        self::assertSame(
            'a&lt;&gt;&amp;&quot;&#039;�',
            Html::encode("a<>&\"'\x80"),
            'Special characters do not match.',
        );
        self::assertSame(
            'Sam &amp; Dark',
            Html::encode('Sam & Dark'),
            'Ampersand character does not match.',
        );
    }

    public function testDecode(): void
    {
        self::assertSame(
            "a<>&\"'",
            Html::decode('a&lt;&gt;&amp;&quot;&#039;'),
            'Decoded special characters do not match.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlTagProvider::class, 'tag')]
    public function testTag(string $expected, string|bool|null $name, string $content, array $options): void
    {
        self::assertSame(
            $expected,
            Html::tag($name, $content, $options),
            "Generated HTML for tag '$name' does not match.",
        );
    }

    public function testBeginTag(): void
    {
        self::assertEmpty(
            Html::beginTag(false),
            "Should return empty for 'false' tag name.",
        );
        self::assertEmpty(
            Html::beginTag(null),
            "Should return empty for 'null' tag name.",
        );
        self::assertSame(
            <<<HTML
            <br>
            HTML,
            Html::beginTag('br'),
            "Generated HTML for 'br' does not match.",
        );
        self::assertSame(
            <<<HTML
            <span id="test" class="title">
            HTML,
            Html::beginTag('span', ['id' => 'test', 'class' => 'title']),
            "Generated HTML for 'span' with attributes does not match.",
        );
    }

    public function testEndTag(): void
    {
        self::assertSame(
            '',
            Html::endTag(false),
            "Should return empty for 'false' tag name.",
        );
        self::assertSame(
            '',
            Html::endTag(null),
            "Should return empty for 'null' tag name.",
        );
        self::assertSame(
            <<<HTML
            </br>
            HTML,
            Html::endTag('br'),
            "Generated HTML for 'br' does not match.",
        );
        self::assertSame(
            <<<HTML
            </span>
            HTML,
            Html::endTag('span'),
            "Generated HTML for 'span' does not match.",
        );
    }

    public function testStyle(): void
    {
        $content = 'a <>';

        self::assertSame(
            <<<HTML
            <style>{$content}</style>
            HTML,
            Html::style($content),
            'Generated HTML with default attributes does not match.',
        );
        self::assertSame(
            <<<HTML
            <style type="text/less">{$content}</style>
            HTML,
            Html::style($content, ['type' => 'text/less']),
            'Generated HTML with custom attributes does not match.',
        );
    }

    public function testStyleCustomAttribute(): void
    {
        $nonce = Yii::$app->security->generateRandomString();

        $this->mockApplication(
            [
                'components' => [
                    'view' => [
                        'class' => View::class,
                        'styleOptions' => ['nonce' => $nonce],
                    ],
                ],
            ],
        );

        $content = 'a <>';

        self::assertSame(
            <<<HTML
            <style nonce="{$nonce}">{$content}</style>
            HTML,
            Html::style($content),
            "Generated HTML with custom 'nonce' attribute does not match.",
        );
    }

    public function testScript(): void
    {
        $content = 'a <>';

        self::assertSame(
            <<<HTML
            <script>{$content}</script>
            HTML,
            Html::script($content),
            'Generated HTML with default attributes does not match.',
        );
        self::assertSame(
            <<<HTML
            <script type="text/js">{$content}</script>
            HTML,
            Html::script($content, ['type' => 'text/js']),
            'Generated HTML with custom attributes does not match.',
        );
    }

    public function testScriptCustomAttribute(): void
    {
        $nonce = Yii::$app->security->generateRandomString();

        $this->mockApplication(
            [
                'components' => [
                    'view' => [
                        'class' => View::class,
                        'scriptOptions' => ['nonce' => $nonce],
                    ],
                ],
            ],
        );

        $content = 'a <>';

        self::assertSame(
            <<<HTML
            <script nonce="{$nonce}">{$content}</script>
            HTML,
            Html::script($content),
            "Generated HTML with custom 'nonce' attribute does not match.",
        );
    }

    public function testCssFile(): void
    {
        self::assertSame(
            <<<HTML
            <link href="http://example.com" rel="stylesheet">
            HTML,
            Html::cssFile('http://example.com'),
            'Generated HTML for external URL does not match.',
        );
        self::assertSame(
            <<<HTML
            <link href="/test" rel="stylesheet">
            HTML,
            Html::cssFile(''),
            'Generated HTML for empty URL does not match.',
        );
        self::assertSame(
            <<<HTML
            <!--[if IE 9]>
            <link href="http://example.com" rel="stylesheet">
            <![endif]-->
            HTML,
            Html::cssFile('http://example.com', ['condition' => 'IE 9']),
            'Generated HTML for legacy IE condition does not match.',
        );
        self::assertSame(
            <<<HTML
            <!--[if (gte IE 9)|(!IE)]><!-->
            <link href="http://example.com" rel="stylesheet">
            <!--<![endif]-->
            HTML,
            Html::cssFile('http://example.com', ['condition' => '(gte IE 9)|(!IE)']),
            'Generated HTML for modern IE fallback condition does not match.',
        );
        self::assertSame(
            <<<HTML
            <noscript><link href="http://example.com" rel="stylesheet"></noscript>
            HTML,
            Html::cssFile('http://example.com', ['noscript' => true]),
            'Generated HTML with noscript wrapper does not match.',
        );
    }

    public function testJsFile(): void
    {
        self::assertSame(
            <<<HTML
            <script src="http://example.com"></script>
            HTML,
            Html::jsFile('http://example.com'),
            'Generated HTML for external URL does not match.',
        );
        self::assertSame(
            <<<HTML
            <script src="/test"></script>
            HTML,
            Html::jsFile(''),
            'Generated HTML for empty URL does not match.',
        );
        self::assertSame(
            <<<HTML
            <!--[if IE 9]>
            <script src="http://example.com"></script>
            <![endif]-->
            HTML,
            Html::jsFile('http://example.com', ['condition' => 'IE 9']),
            'Generated HTML for legacy IE condition does not match.',
        );
        self::assertSame(
            <<<HTML
            <!--[if (gte IE 9)|(!IE)]><!-->
            <script src="http://example.com"></script>
            <!--<![endif]-->
            HTML,
            Html::jsFile('http://example.com', ['condition' => '(gte IE 9)|(!IE)']),
            'Generated HTML for modern IE fallback condition does not match.',
        );
    }

    public function testCsrfMetaTagsDisableCsrfValidation(): void
    {
        self::assertSame(
            '',
            Html::csrfMetaTags(),
            'Should return empty when CSRF validation is disabled.',
        );
    }

    public function testCsrfMetaTagsEnableCsrfValidation(): void
    {
        $this->mockApplication(
            [
                'components' => [
                    'request' => [
                        'class' => Request::class,
                        'enableCsrfValidation' => true,
                        'cookieValidationKey' => 'key',
                    ],
                    'response' => ['class' => Response::class],
                ],
            ],
        );

        self::assertStringMatchesFormat(
            <<<HTML
            <meta name="csrf-param" content="_csrf">%A<meta name="csrf-token" content="%s">
            HTML,
            Html::csrfMetaTags(),
            'Generated meta tags format does not match.',
        );
    }

    public function testThrowInvalidConfigExceptionForCsrfMetaTagsEnableCsrfValidationWithoutCookieValidationKey(): void
    {
        $this->mockApplication(
            [
                'components' => [
                    'request' => [
                        'class' => Request::class,
                        'enableCsrfValidation' => true,
                    ],
                ],
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('yii\web\Request::cookieValidationKey must be configured with a secret key.');

        Html::csrfMetaTags();
    }

}
