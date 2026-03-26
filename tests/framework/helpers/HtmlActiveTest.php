<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\helpers;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use Yii;
use yii\base\DynamicModel;
use yii\base\InvalidArgumentException;
use yii\db\ActiveRecordInterface;
use yii\helpers\Html;
use yii\web\Request;
use yii\web\Response;
use yiiunit\data\helpers\HtmlTestModel;
use yiiunit\data\helpers\MyHtml;
use yiiunit\framework\helpers\providers\HtmlActiveProvider;
use yiiunit\TestCase;


/**
 * Unit tests for {@see yii\helpers\Html} active input helper methods.
 *
 * {@see HtmlActiveProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('helpers')]
#[Group('html')]
#[Group('html-active')]
final class HtmlActiveTest extends TestCase
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

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlActiveProvider::class, 'activeTextInput')]
    public function testActiveTextInput(string $value, array $options, string $expectedHtml): void
    {
        $model = new HtmlTestModel();

        $model->name = $value;

        self::assertSame(
            $expectedHtml,
            Html::activeTextInput($model, 'name', $options),
            'Generated HTML does not match.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlActiveProvider::class, 'activeTextInputMaxLength')]
    public function testActiveTextInputMaxLength(
        string $value,
        array $options,
        string $expectedHtmlForTitle,
        string $expectedHtmlForAlias,
    ): void {
        $model = new HtmlTestModel();

        $model->title = $value;
        $model->alias = $value;

        self::assertSame(
            $expectedHtmlForTitle,
            Html::activeInput('text', $model, 'title', $options),
            'Generated HTML for title does not match.',
        );
        self::assertSame(
            $expectedHtmlForAlias,
            Html::activeInput('text', $model, 'alias', $options),
            'Generated HTML for alias does not match.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlActiveProvider::class, 'activePasswordInput')]
    public function testActivePasswordInput(string $value, array $options, string $expectedHtml): void
    {
        $model = new HtmlTestModel();

        $model->name = $value;

        self::assertSame(
            $expectedHtml,
            Html::activePasswordInput($model, 'name', $options),
            'Generated HTML does not match.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlActiveProvider::class, 'activeInputTypeText')]
    public function testActiveInputTypeText(string $value, array $options, string $expectedHtml): void
    {
        $model = new HtmlTestModel();

        $model->name = $value;

        self::assertSame(
            $expectedHtml,
            Html::activeInput('text', $model, 'name', $options),
            'Generated HTML does not match.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     * @phpstan-param Closure(HtmlTestModel): void $beforeValidate
     */
    #[DataProviderExternal(HtmlActiveProvider::class, 'errorSummary')]
    public function testErrorSummary(
        string $value,
        array $options,
        string $expectedHtml,
        Closure|null $beforeValidate = null,
    ): void {
        $model = new HtmlTestModel();

        $model->name = $value;

        if ($beforeValidate !== null) {
            $beforeValidate($model);
        }

        $model->validate(null, false);

        self::assertEqualsWithoutLE(
            $expectedHtml,
            Html::errorSummary($model, $options),
            'Generated HTML does not match.',
        );
    }

    public function testError(): void
    {
        $model = new HtmlTestModel();

        $model->validate();

        self::assertSame(
            <<<HTML
            <div>Name cannot be blank.</div>
            HTML,
            Html::error($model, 'name'),
            'Default error message does not match.',
        );
        self::assertSame(
            <<<HTML
            <div>this is custom error message</div>
            HTML,
            Html::error($model, 'name', ['errorSource' => [$model, 'customError']]),
            'Custom error message by callback does not match.',
        );
        self::assertSame(
            <<<HTML
            <div>Error in yiiunit\data\helpers\HtmlTestModel - name</div>
            HTML,
            Html::error(
                $model,
                'name',
                [
                    'errorSource' => fn($model, $attribute): string => 'Error in '
                        . $model::class
                        . ' - ' . $attribute,
                ],
            ),
            'Custom error message by closure does not match.',
        );
    }

    /**
     * Test that attributes that output same errors, return unique message error.
     *
     * @see https://github.com/yiisoft/yii2/pull/15859
     */
    public function testCollectError(): void
    {
        $model = new DynamicModel(['attr1', 'attr2']);

        $model->addError('attr1', 'error1');
        $model->addError('attr1', 'error2');
        $model->addError('attr2', 'error1');

        self::assertSame(
            <<<HTML
            <div><p>Please fix the following errors:</p><ul><li>error1</li>
            <li>error2</li></ul></div>
            HTML,
            Html::errorSummary($model, ['showAllErrors' => true]),
            'Error summary should collapse duplicate messages across attributes.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlActiveProvider::class, 'activeTextArea')]
    public function testActiveTextArea(string $value, array $options, string $expectedHtml): void
    {
        $model = new HtmlTestModel();

        $model->description = $value;

        self::assertSame(
            $expectedHtml,
            Html::activeTextarea($model, 'description', $options),
            'Generated HTML does not match.',
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/10078
     */
    public function testCsrfDisable(): void
    {
        $this->mockApplication(
            [
                'components' => [
                    'request' => [
                        'class' => Request::class,
                        'url' => '/test',
                        'scriptUrl' => '/index.php',
                        'hostInfo' => 'http://www.example.com',
                        'enableCsrfValidation' => true,
                        'cookieValidationKey' => 'foobar',
                    ],
                    'response' => ['class' => Response::class],
                ],
            ],
        );

        $token = Yii::$app->request->getCsrfToken();

        self::assertSame(
            <<<HTML
            <form id="mycsrfform" action="/index.php" method="post">
            <input type="hidden" name="_csrf" value="{$token}">
            HTML,
            Html::beginForm('/index.php', 'post', ['id' => 'mycsrfform']),
            'CSRF form should include hidden CSRF token when CSRF is enabled.',
        );
        self::assertSame(
            <<<HTML
            <form id="myform" action="/index.php" method="post">
            HTML,
            Html::beginForm(
                '/index.php',
                'post',
                ['csrf' => false, 'id' => 'myform'],
            ),
            'Form should not include CSRF hidden input when csrf option is disabled.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlActiveProvider::class, 'activeRadio')]
    public function testActiveRadio(bool $value, array $options, string $expectedHtml): void
    {
        $model = new HtmlTestModel();

        $model->radio = $value;

        self::assertSame(
            $expectedHtml,
            Html::activeRadio($model, 'radio', $options),
            'Generated HTML does not match.',
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    #[DataProviderExternal(HtmlActiveProvider::class, 'activeCheckbox')]
    public function testActiveCheckbox(bool $value, array $options, string $expectedHtml): void
    {
        $model = new HtmlTestModel();

        $model->checkbox = $value;

        self::assertSame(
            $expectedHtml,
            Html::activeCheckbox($model, 'checkbox', $options),
            'Generated HTML does not match.',
        );
    }

    #[DataProviderExternal(HtmlActiveProvider::class, 'activeFileInput')]
    public function testActiveFileInput(string $expected, string $attribute, array $options, string $message): void
    {
        self::assertEqualsWithoutLE(
            $expected,
            Html::activeFileInput(new HtmlTestModel(), $attribute, $options),
            $message,
        );
    }

    public function testGetAttributeValue(): void
    {
        $model = new HtmlTestModel();

        self::assertNull(
            Html::getAttributeValue($model, 'types'),
            'Attribute value for null model attribute does not match.',
        );

        $activeRecord = $this->getMockBuilder(ActiveRecordInterface::class)->getMock();

        $activeRecord->method('getPrimaryKey')->willReturn(1);

        $model->types = $activeRecord;

        self::assertSame(
            1,
            Html::getAttributeValue($model, 'types'),
            'Attribute value for ActiveRecord primary key value does not match.',
        );

        $model->types = [$activeRecord];

        self::assertSame(
            [1],
            Html::getAttributeValue($model, 'types'),
            'Attribute value for ActiveRecord array values does not match.',
        );
    }

    public function testActiveDropDownList(): void
    {
        $model = new HtmlTestModel();

        self::assertEqualsWithoutLE(
            <<<HTML
            <input type="hidden" name="HtmlTestModel[types]" value=""><select id="htmltestmodel-types" name="HtmlTestModel[types][]" multiple="true" size="4">

            </select>
            HTML,
            Html::activeDropDownList($model, 'types', [], ['multiple' => 'true']),
            'Generated HTML with multiple selection does not match.',
        );
    }

    public function testActiveRadioList(): void
    {
        $model = new HtmlTestModel();

        self::assertEqualsWithoutLE(
            <<<HTML
            <input type="hidden" name="HtmlTestModel[types]" value=""><div id="htmltestmodel-types"><label><input type="radio" name="HtmlTestModel[types]" value="0"> foo</label></div>
            HTML,
            Html::activeRadioList($model, 'types', ['foo']),
            'Generated HTML with default options does not match.',
        );
    }

    public function testActiveCheckboxList(): void
    {
        $model = new HtmlTestModel();

        self::assertEqualsWithoutLE(
            <<<HTML
            <input type="hidden" name="HtmlTestModel[types]" value=""><div id="htmltestmodel-types"><label><input type="checkbox" name="HtmlTestModel[types][]" value="0"> foo</label></div>
            HTML,
            Html::activeCheckboxList($model, 'types', ['foo']),
            'Generated HTML with default options does not match.',
        );
    }

    public function testActiveCheckboxListOptions(): void
    {
        $model = new HtmlTestModel();

        self::assertEqualsWithoutLE(
            <<<HTML
            <input type="hidden" name="foo" value=""><div id="htmltestmodel-types"><label><input type="checkbox" name="foo[]" value="0" checked> foo</label></div>
            HTML,
            Html::activeCheckboxList(
                $model,
                'types',
                ['foo'],
                ['name' => 'foo', 'value' => 0],
            ),
            'Generated HTML with custom name and value options does not match.',
        );
    }

    public function testActiveTextInputPlaceholderFillFromModel(): void
    {
        $model = new HtmlTestModel();

        self::assertSame(
            <<<HTML
            <input type="text" id="htmltestmodel-name" name="HtmlTestModel[name]" placeholder="Name">
            HTML,
            Html::activeTextInput($model, 'name', ['placeholder' => true]),
            "Placeholder filled from model label does not match.",
        );
    }

    public function testActiveTextInputCustomPlaceholder(): void
    {
        $model = new HtmlTestModel();

        self::assertSame(
            <<<HTML
            <input type="text" id="htmltestmodel-name" name="HtmlTestModel[name]" placeholder="Custom placeholder">
            HTML,
            Html::activeTextInput($model, 'name', ['placeholder' => 'Custom placeholder']),
            'Custom placeholder value does not match.',
        );
    }

    public function testActiveTextInputPlaceholderFillFromModelTabular(): void
    {
        $model = new HtmlTestModel();

        self::assertSame(
            <<<HTML
            <input type="text" id="htmltestmodel-0-name" name="HtmlTestModel[0][name]" placeholder="Name">
            HTML,
            Html::activeTextInput($model, '[0]name', ['placeholder' => true]),
            'Placeholder for tabular input does not match.',
        );
    }

    public function testOverrideSetActivePlaceholder(): void
    {
        $model = new HtmlTestModel();

        self::assertSame(
            <<<HTML
            <input type="text" id="htmltestmodel-name" name="HtmlTestModel[name]" placeholder="My placeholder: Name">
            HTML,
            MyHtml::activeTextInput($model, 'name', ['placeholder' => true]),
            'Overridden placeholder does not match.',
        );
    }

    public function testThrowInvalidArgumentExceptionForGetAttributeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attribute name must contain word characters only.');

        $model = new HtmlTestModel();

        Html::getAttributeValue($model, '-');
    }
}
