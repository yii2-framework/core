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
use yii\base\DynamicModel;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\helpers\Html;
use yii\web\Request;
use yii\web\Response;
use yiiunit\data\helpers\HtmlTestModel;
use yiiunit\framework\helpers\providers\HtmlAttributeProvider;
use yiiunit\TestCase;
use function array_key_exists;

/**
 * Unit tests for {@see Html} helper covering HTML attribute handling.
 *
 * {@see HtmlAttributeProvider} for test case data providers.
 *
 * @copyright Copyright (c) 2008 Yii Software LLC.
 * @license https://www.yiiframework.com/license/
 */
#[Group('helpers')]
#[Group('html')]
#[Group('html-attribute')]
final class HtmlAttributeTest extends TestCase
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

    #[DataProviderExternal(HtmlAttributeProvider::class, 'renderTagAttributes')]
    public function testRenderTagAttributes(string $expected, array $attributes): void
    {
        self::assertSame(
            $expected,
            Html::renderTagAttributes($attributes),
            'Tag attributes do not match.',
        );
    }

    public function testRenderTagAttributesNormalizeClass(): void
    {
        Html::$normalizeClassAttribute = true;

        self::assertSame(
            ' class="first second"',
            Html::renderTagAttributes(['class' => ['first second', 'first']]),
            'Normalized class attribute does not match.',
        );

        Html::$normalizeClassAttribute = false;
    }

    /**
     * @phpstan-param array<string, mixed> $options
     * @phpstan-param array<string, mixed> $expected
     */
    #[DataProviderExternal(HtmlAttributeProvider::class, 'addCssClass')]
    public function testAddCssClass(array $options, string|array $class, array $expected): void
    {
        Html::addCssClass($options, $class);

        self::assertSame(
            $expected,
            $options,
            'CSS class options do not match.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     * @phpstan-param array<string, mixed> $expected
     */
    #[DataProviderExternal(HtmlAttributeProvider::class, 'mergeCssClass')]
    public function testMergeCssClass(array $options, array $class, array $expectedClass): void
    {
        Html::addCssClass($options, $class);

        self::assertSame(
            $expectedClass,
            $options['class'],
            'Merged CSS class map does not match.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     * @phpstan-param array<string, mixed> $expected
     */
    #[DataProviderExternal(HtmlAttributeProvider::class, 'removeCssClass')]
    public function testRemoveCssClass(array $options, string|array $class, array $expected): void
    {
        Html::removeCssClass($options, $class);

        self::assertSame(
            $expected,
            $options,
            'CSS class options after removal do not match.',
        );
    }

    public function testCssStyleFromArray(): void
    {
        self::assertSame(
            'width: 100px; height: 200px;',
            Html::cssStyleFromArray(['width' => '100px', 'height' => '200px']),
            'Converting CSS style array to string produced unexpected output.',
        );
        self::assertNull(
            Html::cssStyleFromArray([]),
            "Empty array should return 'null'.",
        );
    }

    public function testCssStyleToArray(): void
    {
        self::assertSame(
            [
                'width' => '100px',
                'height' => '200px',
            ],
            Html::cssStyleToArray('width: 100px; height: 200px;'),
            'Converting CSS style string to array produced unexpected output.',
        );
        self::assertEmpty(
            Html::cssStyleToArray('  '),
            'Converting empty CSS style string should return an empty array.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlAttributeProvider::class, 'addCssStyle')]
    public function testAddCssStyle(array $options, string|array $style, bool $overwrite, string $expected): void
    {
        Html::addCssStyle($options, $style, $overwrite);

        self::assertSame(
            $expected,
            $options['style'],
            'CSS style does not match.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlAttributeProvider::class, 'removeCssStyle')]
    public function testRemoveCssStyle(array $options, string|array $properties, string $expected): void
    {
        Html::removeCssStyle($options, $properties);

        self::assertSame(
            $expected,
            $options['style'],
            'CSS style after removal does not match.',
        );
    }

    public function testRemoveCssStyleAllProperties(): void
    {
        $options = ['style' => 'color: red;'];

        Html::removeCssStyle($options, ['color', 'background']);

        self::assertNull(
            $options['style'],
            "Style should be 'null' after removing all properties.",
        );
    }

    public function testRemoveCssStyleFromEmptyOptions(): void
    {
        $options = [];

        Html::removeCssStyle($options, ['color', 'background']);

        self::assertNotTrue(
            array_key_exists('style', $options),
            "Style key should not exist after removing from empty options.",
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlAttributeProvider::class, 'booleanAttributes')]
    public function testBooleanAttributes(string $expected, array $options): void
    {
        self::assertSame(
            $expected,
            Html::input('email', 'mail', null, $options),
            'Boolean attribute does not match.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlAttributeProvider::class, 'dataAttributes')]
    public function testDataAttributes(string $expected, array $options): void
    {
        self::assertSame(
            $expected,
            Html::tag('link', '', $options),
            'Data attributes do not match.',
        );
    }

    #[DataProviderExternal(HtmlAttributeProvider::class, 'validAttributeNames')]
    public function testAttributeNameValidation(string $name, string $expected): void
    {
        self::assertSame(
            $expected,
            Html::getAttributeName($name),
            "Attribute name '$name' does not match.",
        );
    }

    public function testGetInputName(): void
    {
        $model = $this->getMockBuilder(Model::class)->getMock();

        $model->method('formName')->willReturn('');

        self::assertSame(
            'types',
            Html::getInputName($model, 'types'),
            'Input name for model without form name does not match.',
        );
    }

    #[DataProviderExternal(HtmlAttributeProvider::class, 'getInputId')]
    public function testGetInputId(string $attributeName, string $inputIdExpected): void
    {
        $model = new DynamicModel();

        $model->defineAttribute($attributeName);

        self::assertSame(
            $inputIdExpected,
            Html::getInputId($model, $attributeName),
            "Input ID for attribute '$attributeName' does not match.",
        );
    }

    #[DataProviderExternal(HtmlAttributeProvider::class, 'getInputIdByName')]
    public function testGetInputIdByName(string $attributeName, string $inputIdExpected): void
    {
        $model = new DynamicModel();

        $model->defineAttribute($attributeName);

        $inputNameActual = Html::getInputName($model, $attributeName);

        self::assertSame(
            $inputIdExpected,
            Html::getInputIdByName($inputNameActual),
            "Input ID by name for attribute '$attributeName' does not match.",
        );
    }

    #[DataProviderExternal(HtmlAttributeProvider::class, 'getInputNameWithoutBrackets')]
    public function testGetInputNameWithoutBrackets(string $name, string $expectedName): void
    {
        self::assertSame(
            $expectedName,
            Html::getInputNameWithoutBrackets($name),
            "Input name '$name' does not match.",
        );
    }

    public function testEscapeJsRegularExpression(): void
    {
        self::assertSame(
            '/[a-z0-9-]+/',
            Html::escapeJsRegularExpression('([a-z0-9-]+)'),
            'JS regular expression for plain pattern string does not match.',
        );
        self::assertSame(
            '/([a-z0-9-]+)/gim',
            Html::escapeJsRegularExpression('/([a-z0-9-]+)/Ugimex'),
            'JS regular expression for pattern with unsupported modifiers does not match.',
        );

        // Make sure that just allowed REGEX modifiers remain after the escaping
        self::assertSame(
            '/([a-z0-9-]+)/ugim',
            Html::escapeJsRegularExpression('/([a-z0-9-]+)/dugimex'),
            'JS regular expression should keep only allowed modifiers.',
        );
    }

    #[DataProviderExternal(HtmlAttributeProvider::class, 'invalidAttributeNames')]
    public function testThrowInvalidArgumentExceptionForAttributeName(string $name): void
    {
        $this->expectException('yii\base\InvalidArgumentException');

        Html::getAttributeName($name);
    }

    public function testThrowInvalidArgumentExceptionForInputNameAttribute(): void
    {
        $model = new HtmlTestModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attribute name must contain word characters only.');

        Html::getInputName($model, '-');
    }

    public function testThrowInvalidArgumentExceptionForInputNameFormName(): void
    {
        $model = $this->getMockBuilder(Model::class)->getMock();

        $model->method('formName')->willReturn('');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty for tabular inputs.');

        Html::getInputName($model, '[foo]bar');
    }
}
