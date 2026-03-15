<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\widgets;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Exception;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Depends;
use Yii;
use yii\base\DynamicModel;
use yii\validators\Validator;
use yii\web\AssetManager;
use yii\web\View;
use yii\widgets\ActiveField;
use yii\widgets\ActiveForm;
use yii\widgets\InputWidget;
use yii\widgets\MaskedInput;
use yiiunit\framework\widgets\provider\ActiveFieldProvider;
use yiiunit\TestCase;

use function array_keys;
use function array_map;
use function sprintf;

/**
 * @author Nelson J Morais <njmorais@gmail.com>
 *
 * @group widgets
 */
class ActiveFieldTest extends TestCase
{
    use ArraySubsetAsserts;

    /**
     * @var ActiveFieldExtend
     */
    private $activeField;
    /**
     * @var DynamicModel
     */
    private $helperModel;
    /**
     * @var ActiveForm
     */
    private $helperForm;
    private $attributeName = 'attributeName';

    protected function setUp(): void
    {
        parent::setUp();
        // dirty way to have Request object not throwing exception when running testHomeLinkNull()
        $_SERVER['SCRIPT_FILENAME'] = 'index.php';
        $_SERVER['SCRIPT_NAME'] = 'index.php';

        $this->mockWebApplication();

        Yii::setAlias('@testWeb', '/');
        Yii::setAlias('@testWebRoot', '@yiiunit/data/web');

        $this->helperModel = new ActiveFieldTestModel(['attributeName']);
        ob_start();
        $this->helperForm = ActiveForm::begin(['action' => '/something', 'enableClientScript' => false]);
        ActiveForm::end();
        ob_end_clean();

        $this->activeField = new ActiveFieldExtend(true);
        $this->activeField->form = $this->helperForm;
        $this->activeField->form->setView($this->getView());
        $this->activeField->model = $this->helperModel;
        $this->activeField->attribute = $this->attributeName;
    }

    public function testRenderNoContent(): void
    {
        $expectedValue = <<<EOD
<div class="form-group field-activefieldtestmodel-attributename">
<label class="control-label" for="activefieldtestmodel-attributename">Attribute Name</label>
<input type="text" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[{$this->attributeName}]">
<div class="hint-block">Hint for attributeName attribute</div>
<div class="help-block"></div>
</div>
EOD;

        $actualValue = $this->activeField->render();
        $this->assertEqualsWithoutLE($expectedValue, $actualValue);
    }

    /**
     * @todo discuss|review Expected HTML shouldn't be wrapped only by the $content?
     */
    public function testRenderWithCallableContent(): void
    {
        // field will be the html of the model's attribute wrapped with the return string below.
        $field = $this->attributeName;
        $content = static fn ($field) => "<div class=\"custom-container\"> $field </div>";

        $expectedValue = <<<EOD
<div class="form-group field-activefieldtestmodel-attributename">
<div class="custom-container"> <div class="form-group field-activefieldtestmodel-attributename">
<label class="control-label" for="activefieldtestmodel-attributename">Attribute Name</label>
<input type="text" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[{$this->attributeName}]">
<div class="hint-block">Hint for attributeName attribute</div>
<div class="help-block"></div>
</div> </div>
</div>
EOD;

        $actualValue = $this->activeField->render($content);
        $this->assertEqualsWithoutLE($expectedValue, $actualValue);
    }

    /**
     * @link https://github.com/yiisoft/yii2/issues/7627
     */
    public function testRenderWithCustomInputId(): void
    {
        $expectedValue = <<<EOD
<div class="form-group field-custom-input-id">
<label class="control-label" for="custom-input-id">Attribute Name</label>
<input type="text" id="custom-input-id" class="form-control" name="ActiveFieldTestModel[{$this->attributeName}]">
<div class="hint-block">Hint for attributeName attribute</div>
<div class="help-block"></div>
</div>
EOD;

        $this->activeField->inputOptions['id'] = 'custom-input-id';

        $actualValue = $this->activeField->render();
        $this->assertEqualsWithoutLE($expectedValue, $actualValue);
    }

    public function testBeginHasErrors(): void
    {
        $this->helperModel->addError($this->attributeName, 'Error Message');

        $expectedValue = '<div class="form-group field-activefieldtestmodel-attributename has-error">';
        $actualValue = $this->activeField->begin();

        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testBeginAttributeIsRequired(): void
    {
        $this->helperModel->addRule($this->attributeName, 'required');

        $expectedValue = '<div class="form-group field-activefieldtestmodel-attributename required">';
        $actualValue = $this->activeField->begin();

        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testBeginHasErrorAndRequired(): void
    {
        $this->helperModel->addError($this->attributeName, 'Error Message');
        $this->helperModel->addRule($this->attributeName, 'required');

        $expectedValue = '<div class="form-group field-activefieldtestmodel-attributename required has-error">';
        $actualValue = $this->activeField->begin();

        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testBegin(): void
    {
        $expectedValue = '<article class="form-group field-activefieldtestmodel-attributename">';
        $this->activeField->options['tag'] = 'article';
        $actualValue = $this->activeField->begin();

        $this->assertEquals($expectedValue, $actualValue);

        $expectedValue = '';
        $this->activeField->options['tag'] = null;
        $actualValue = $this->activeField->begin();

        $this->assertEquals($expectedValue, $actualValue);

        $expectedValue = '';
        $this->activeField->options['tag'] = false;
        $actualValue = $this->activeField->begin();

        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testEnd(): void
    {
        $expectedValue = '</div>';
        $actualValue = $this->activeField->end();

        $this->assertEquals($expectedValue, $actualValue);

        // other tag
        $expectedValue = '</article>';
        $this->activeField->options['tag'] = 'article';
        $actualValue = $this->activeField->end();

        $this->assertEquals($expectedValue, $actualValue);

        $expectedValue = '';
        $this->activeField->options['tag'] = false;
        $actualValue = $this->activeField->end();

        $this->assertEquals($expectedValue, $actualValue);

        $expectedValue = '';
        $this->activeField->options['tag'] = null;
        $actualValue = $this->activeField->end();

        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testLabel(): void
    {
        $expectedValue = <<<HTML
        <label class="control-label" for="activefieldtestmodel-attributename">Attribute Name</label>
        HTML;

        $this->activeField->label();

        self::assertSame(
            $expectedValue,
            $this->activeField->parts['{label}'],
            'Should be the same as the default label generated by the model attribute label',
        );

        // label = false
        $expectedValue = '';

        $this->activeField->label(false);

        self::assertSame(
            $expectedValue,
            $this->activeField->parts['{label}'],
            "Should be empty when set to 'false'",
        );

        // $label = 'Label Name'
        $label = 'Label Name';
        $expectedValue = <<<HTML
        <label class="control-label" for="activefieldtestmodel-attributename">{$label}</label>
        HTML;

        $this->activeField->label($label);

        self::assertSame(
            $expectedValue,
            $this->activeField->parts['{label}'],
            'Should be the same as the provided label',
        );
    }

    public function testLabelInheritsLabelOptions(): void
    {
        $this->activeField->labelOptions = ['class' => 'custom-class'];

        $this->activeField->label();

        self::assertSame(
            <<<HTML
            <label class="custom-class" for="activefieldtestmodel-attributename">Attribute Name</label>
            HTML,
            $this->activeField->parts['{label}'],
            'Should inherit the label options defined in the field',
        );
    }

    public function testLabelPriorityOfContent(): void
    {
        // $label parameter takes priority over $options['label']
        $this->activeField->label('Parameter Label', ['label' => 'Options Label']);

        self::assertSame(
            <<<HTML
            <label class="control-label" for="activefieldtestmodel-attributename">Parameter Label</label>
            HTML,
            $this->activeField->parts['{label}'],
            'Should prioritize the label provided as a parameter over the one in options',
        );

        // $options['label'] takes priority over model attribute label
        $this->activeField->label(null, ['label' => 'Options Label']);

        self::assertSame(
            <<<HTML
            <label class="control-label" for="activefieldtestmodel-attributename">Options Label</label>
            HTML,
            $this->activeField->parts['{label}'],
            'Should prioritize the label provided in options over the model attribute label',
        );
    }

    public function testLabelOptionsLabelFalseWithNullParameter(): void
    {
        $this->activeField->label(null, ['label' => false]);

        self::assertSame(
            '',
            $this->activeField->parts['{label}'],
            "Should be empty when label option is set to 'false', even if the parameter is 'null'",
        );
    }

    public function testLabelOptionsLabelFalseWithNoParameters(): void
    {
        $this->activeField->labelOptions = ['label' => false];

        $this->activeField->label();

        self::assertSame(
            '',
            $this->activeField->parts['{label}'],
            "Should be empty when label option is set to 'false' and no parameters are provided",
        );
    }

    public function testLabelOverridesLabelOptionsFalse(): void
    {
        $this->activeField->labelOptions = [
            'class' => 'control-label',
            'label' => false,
        ];

        $this->activeField->label('Explicit Label');

        self::assertSame(
            <<<HTML
            <label class="control-label" for="activefieldtestmodel-attributename">Explicit Label</label>
            HTML,
            $this->activeField->parts['{label}'],
            "Should render the label when an explicit label is provided, even if label option is set to 'false'",
        );
    }

    public function testLabelWithContent(): void
    {
        $this->activeField->label('Custom Text');

        self::assertSame(
            <<<HTML
            <label class="control-label" for="activefieldtestmodel-attributename">Custom Text</label>
            HTML,
            $this->activeField->parts['{label}'],
            'Should render the label with the provided custom text',
        );
    }

    public function testLabelWithEmptyString(): void
    {
        $this->activeField->label('');

        self::assertSame(
            <<<HTML
            <label class="control-label" for="activefieldtestmodel-attributename"></label>
            HTML,
            $this->activeField->parts['{label}'],
            'Should render an empty label when an empty string is provided as the label content',
        );
    }

    #[DataProviderExternal(ActiveFieldProvider::class, 'labelWithTagCustom')]
    public function testLabelWithLabelOptionsAndTagCustom(string $tag, string|null $label, string $expectedHtml): void
    {
        $this->activeField->label($label, ['tag' => $tag]);

        self::assertSame(
            $expectedHtml,
            $this->activeField->parts['{label}'],
            "Should render the label wrapped in the specified custom tag when the 'tag' option is set",
        );
    }

    #[DataProviderExternal(ActiveFieldProvider::class, 'labelWithTagFalse')]
    public function testLabelWithLabelOptionsAndTagFalse(string|null $label, string $expectedHtml): void
    {
        $this->activeField->label($label, ['tag' => false]);

        self::assertSame(
            $expectedHtml,
            $this->activeField->parts['{label}'],
            "Should not render the label when the 'tag' option is set to 'false'",
        );
    }

    public function testError(): void
    {
        $expectedValue = '<label class="control-label" for="activefieldtestmodel-attributename">Attribute Name</label>';
        $this->activeField->label();

        $this->assertEquals($expectedValue, $this->activeField->parts['{label}']);

        // label = false
        $expectedValue = '';
        $this->activeField->label(false);

        $this->assertEquals($expectedValue, $this->activeField->parts['{label}']);

        // $label = 'Label Name'
        $label = 'Label Name';
        $expectedValue = <<<EOT
<label class="control-label" for="activefieldtestmodel-attributename">{$label}</label>
EOT;
        $this->activeField->label($label);

        $this->assertEquals($expectedValue, $this->activeField->parts['{label}']);
    }

    public function testTabularInputErrors(): void
    {
        $this->activeField->attribute = '[0]' . $this->attributeName;
        $this->helperModel->addError($this->attributeName, 'Error Message');

        $expectedValue = '<div class="form-group field-activefieldtestmodel-0-attributename has-error">';
        $actualValue = $this->activeField->begin();

        $this->assertEquals($expectedValue, $actualValue);
    }

    #[DataProviderExternal(ActiveFieldProvider::class, 'hint')]
    public function testHint(mixed $hint, string $expectedHtml): void
    {
        $this->activeField->hint($hint);

        self::assertEquals(
            $expectedHtml,
            $this->activeField->parts['{hint}'],
            "Should render the hint block with the provided hint content, even if it is an empty string or 'null'.",
        );
    }

    public function testInput(): void
    {
        $expectedValue = <<<'EOD'
<input type="password" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]">
EOD;
        $this->activeField->input('password');

        $this->assertEquals($expectedValue, $this->activeField->parts['{input}']);

        // with options
        $expectedValue = <<<'EOD'
<input type="password" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]" weird="value">
EOD;
        $this->activeField->input('password', ['weird' => 'value']);

        $this->assertEquals($expectedValue, $this->activeField->parts['{input}']);
    }

    public function testTextInput(): void
    {
        $expectedValue = <<<'EOD'
<input type="text" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]">
EOD;
        $this->activeField->textInput();
        $this->assertEquals($expectedValue, $this->activeField->parts['{input}']);
    }

    public function testHiddenInput(): void
    {
        $expectedValue = <<<'EOD'
<input type="hidden" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]">
EOD;
        $this->activeField->hiddenInput();
        $this->assertEquals($expectedValue, $this->activeField->parts['{input}']);
    }

    public function testListBox(): void
    {
        $expectedValue = <<<'EOD'
<input type="hidden" name="ActiveFieldTestModel[attributeName]" value=""><select id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]" size="4">
<option value="1">Item One</option>
<option value="2">Item 2</option>
</select>
EOD;
        $this->activeField->listBox(['1' => 'Item One', '2' => 'Item 2']);
        $this->assertEqualsWithoutLE($expectedValue, $this->activeField->parts['{input}']);

        // https://github.com/yiisoft/yii2/issues/8848
        $expectedValue = <<<'EOD'
<input type="hidden" name="ActiveFieldTestModel[attributeName]" value=""><select id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]" size="4">
<option value="value1" disabled>Item One</option>
<option value="value2" label="value 2">Item 2</option>
</select>
EOD;
        $this->activeField->listBox(['value1' => 'Item One', 'value2' => 'Item 2'], ['options' => [
            'value1' => ['disabled' => true],
            'value2' => ['label' => 'value 2'],
        ]]);
        $this->assertEqualsWithoutLE($expectedValue, $this->activeField->parts['{input}']);

        $expectedValue = <<<'EOD'
<input type="hidden" name="ActiveFieldTestModel[attributeName]" value=""><select id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]" size="4">
<option value="value1" disabled>Item One</option>
<option value="value2" selected label="value 2">Item 2</option>
</select>
EOD;
        $this->activeField->model->{$this->attributeName} = 'value2';
        $this->activeField->listBox(['value1' => 'Item One', 'value2' => 'Item 2'], ['options' => [
            'value1' => ['disabled' => true],
            'value2' => ['label' => 'value 2'],
        ]]);
        $this->assertEqualsWithoutLE($expectedValue, $this->activeField->parts['{input}']);
    }

    public function testRadioList(): void
    {
        $expectedValue = <<<'EOD'
<div class="form-group field-activefieldtestmodel-attributename">
<label class="control-label">Attribute Name</label>
<input type="hidden" name="ActiveFieldTestModel[attributeName]" value=""><div id="activefieldtestmodel-attributename" role="radiogroup"><label><input type="radio" name="ActiveFieldTestModel[attributeName]" value="1"> Item One</label></div>
<div class="hint-block">Hint for attributeName attribute</div>
<div class="help-block"></div>
</div>
EOD;
        $this->activeField->radioList(['1' => 'Item One']);
        $this->assertEqualsWithoutLE($expectedValue, $this->activeField->render());
    }

    #[DataProviderExternal(ActiveFieldProvider::class, 'radioEnclosedByLabelFalse')]
    public function testRadioEnclosedByLabelFalse(array $options, string $expectedLabel): void
    {
        $this->activeField->radio($options, false);

        self::assertSame(
            $expectedLabel,
            $this->activeField->parts['{label}'],
            "Should not render the label when the 'enclosedByLabel' option is set to 'false'.",
        );
    }

    #[DataProviderExternal(ActiveFieldProvider::class, 'checkboxEnclosedByLabelFalse')]
    public function testCheckboxEnclosedByLabelFalse(array $options, string $expectedLabel): void
    {
        $this->activeField->checkbox($options, false);

        self::assertSame(
            $expectedLabel,
            $this->activeField->parts['{label}'],
            "Should not render the label when the 'enclosedByLabel' option is set to 'false'.",
        );
    }

    public function testGetClientOptionsReturnEmpty(): void
    {
        // setup: we want the real deal here!
        $this->activeField->setClientOptionsEmpty(false);

        // expected empty
        $actualValue = $this->activeField->getClientOptions();
        $this->assertEmpty($actualValue);
    }

    public function testGetClientOptionsWithActiveAttributeInScenario(): void
    {
        $this->activeField->setClientOptionsEmpty(false);

        $this->activeField->model->addRule($this->attributeName, 'yiiunit\framework\widgets\TestValidator');
        $this->activeField->form->enableClientValidation = false;

        // expected empty
        $actualValue = $this->activeField->getClientOptions();
        $this->assertEmpty($actualValue);
    }

    public function testGetClientOptionsClientValidation(): void
    {
        $this->activeField->setClientOptionsEmpty(false);

        $this->activeField->model->addRule($this->attributeName, 'yiiunit\framework\widgets\TestValidator');
        $this->activeField->enableClientValidation = true;
        $actualValue = $this->activeField->getClientOptions();
        $expectedJsExpression = 'function (attribute, value, messages, deferred, $form) {return true;}';
        $this->assertEquals($expectedJsExpression, $actualValue['validate']);

        $this->assertNotTrue(isset($actualValue['validateOnChange']));
        $this->assertNotTrue(isset($actualValue['validateOnBlur']));
        $this->assertNotTrue(isset($actualValue['validateOnType']));
        $this->assertNotTrue(isset($actualValue['validationDelay']));
        $this->assertNotTrue(isset($actualValue['enableAjaxValidation']));
        $this->assertNotTrue(isset($actualValue['hasWhenClient']));

        $this->activeField->validateOnChange = $expectedValidateOnChange = false;
        $this->activeField->validateOnBlur = $expectedValidateOnBlur = false;
        $this->activeField->validateOnType = $expectedValidateOnType = true;
        $this->activeField->validationDelay = $expectedValidationDelay = 100;
        $this->activeField->enableAjaxValidation = $expectedEnableAjaxValidation = true;

        $actualValue = $this->activeField->getClientOptions();

        $this->assertSame($expectedValidateOnChange, $actualValue['validateOnChange']);
        $this->assertSame($expectedValidateOnBlur, $actualValue['validateOnBlur']);
        $this->assertSame($expectedValidateOnType, $actualValue['validateOnType']);
        $this->assertSame($expectedValidationDelay, $actualValue['validationDelay']);
        $this->assertSame($expectedEnableAjaxValidation, $actualValue['enableAjaxValidation']);
    }

    public function testGetClientOptionsValidatorWhenClientSet(): void
    {
        $this->activeField->setClientOptionsEmpty(false);
        $this->activeField->enableAjaxValidation = true;
        $this->activeField->model->addRule($this->attributeName, 'yiiunit\framework\widgets\TestValidator');

        foreach ($this->activeField->model->validators as $validator) {
            $validator->whenClient = "function (attribute, value) { return 'yii2' == 'yii2'; }"; // js
        }

        $actualValue = $this->activeField->getClientOptions();
        $expectedJsExpression = 'function (attribute, value, messages, deferred, $form) {if ((function (attribute, value) '
            . "{ return 'yii2' == 'yii2'; })(attribute, value)) { return true; }}";

        $this->assertEquals($expectedJsExpression, $actualValue['validate']->expression);
        $this->assertTrue($actualValue['hasWhenClient']);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/8779
     */
    public function testEnctype(): void
    {
        $this->activeField->fileInput();
        $this->assertEquals('multipart/form-data', $this->activeField->form->options['enctype']);
    }

    /**
     * @link https://github.com/yiisoft/yii2/issues/7627
     */
    public function testGetClientOptionsWithCustomInputId(): void
    {
        $this->activeField->setClientOptionsEmpty(false);

        $this->activeField->model->addRule($this->attributeName, 'yiiunit\framework\widgets\TestValidator');
        $this->activeField->inputOptions['id'] = 'custom-input-id';
        $this->activeField->textInput();
        $actualValue = $this->activeField->getClientOptions();

        $this->assertArraySubset([
            'id' => 'custom-input-id',
            'name' => $this->attributeName,
            'container' => '.field-custom-input-id',
            'input' => '#custom-input-id',
        ], $actualValue);

        $this->activeField->textInput(['id' => 'custom-textinput-id']);
        $actualValue = $this->activeField->getClientOptions();

        $this->assertArraySubset([
            'id' => 'custom-textinput-id',
            'name' => $this->attributeName,
            'container' => '.field-custom-textinput-id',
            'input' => '#custom-textinput-id',
        ], $actualValue);
    }

    public function testAriaAttributes(): void
    {
        $this->activeField->addAriaAttributes = true;

        $expectedValue = <<<'EOD'
<div class="form-group field-activefieldtestmodel-attributename">
<label class="control-label" for="activefieldtestmodel-attributename">Attribute Name</label>
<input type="text" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]">
<div class="hint-block">Hint for attributeName attribute</div>
<div class="help-block"></div>
</div>
EOD;

        $actualValue = $this->activeField->render();
        $this->assertEqualsWithoutLE($expectedValue, $actualValue);
    }

    public function testAriaRequiredAttribute(): void
    {
        $this->activeField->addAriaAttributes = true;
        $this->helperModel->addRule([$this->attributeName], 'required');

        $expectedValue = <<<'EOD'
<div class="form-group field-activefieldtestmodel-attributename required">
<label class="control-label" for="activefieldtestmodel-attributename">Attribute Name</label>
<input type="text" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]" aria-required="true">
<div class="hint-block">Hint for attributeName attribute</div>
<div class="help-block"></div>
</div>
EOD;

        $actualValue = $this->activeField->render();
        $this->assertEqualsWithoutLE($expectedValue, $actualValue);
    }

    public function testAriaInvalidAttribute(): void
    {
        $this->activeField->addAriaAttributes = true;
        $this->helperModel->addError($this->attributeName, 'Some error');

        $expectedValue = <<<'EOD'
<div class="form-group field-activefieldtestmodel-attributename has-error">
<label class="control-label" for="activefieldtestmodel-attributename">Attribute Name</label>
<input type="text" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]" aria-invalid="true">
<div class="hint-block">Hint for attributeName attribute</div>
<div class="help-block">Some error</div>
</div>
EOD;

        $actualValue = $this->activeField->render();
        $this->assertEqualsWithoutLE($expectedValue, $actualValue);
    }

    public function testTabularAriaAttributes(): void
    {
        $this->activeField->attribute = '[0]' . $this->attributeName;
        $this->activeField->addAriaAttributes = true;

        $expectedValue = <<<'EOD'
<div class="form-group field-activefieldtestmodel-0-attributename">
<label class="control-label" for="activefieldtestmodel-0-attributename">Attribute Name</label>
<input type="text" id="activefieldtestmodel-0-attributename" class="form-control" name="ActiveFieldTestModel[0][attributeName]">
<div class="hint-block">Hint for attributeName attribute</div>
<div class="help-block"></div>
</div>
EOD;

        $actualValue = $this->activeField->render();
        $this->assertEqualsWithoutLE($expectedValue, $actualValue);
    }

    public function testTabularAriaRequiredAttribute(): void
    {
        $this->activeField->attribute = '[0]' . $this->attributeName;
        $this->activeField->addAriaAttributes = true;
        $this->helperModel->addRule([$this->attributeName], 'required');

        $expectedValue = <<<'EOD'
<div class="form-group field-activefieldtestmodel-0-attributename required">
<label class="control-label" for="activefieldtestmodel-0-attributename">Attribute Name</label>
<input type="text" id="activefieldtestmodel-0-attributename" class="form-control" name="ActiveFieldTestModel[0][attributeName]" aria-required="true">
<div class="hint-block">Hint for attributeName attribute</div>
<div class="help-block"></div>
</div>
EOD;

        $actualValue = $this->activeField->render();
        $this->assertEqualsWithoutLE($expectedValue, $actualValue);
    }

    public function testTabularAriaInvalidAttribute(): void
    {
        $this->activeField->attribute = '[0]' . $this->attributeName;
        $this->activeField->addAriaAttributes = true;
        $this->helperModel->addError($this->attributeName, 'Some error');

        $expectedValue = <<<'EOD'
<div class="form-group field-activefieldtestmodel-0-attributename has-error">
<label class="control-label" for="activefieldtestmodel-0-attributename">Attribute Name</label>
<input type="text" id="activefieldtestmodel-0-attributename" class="form-control" name="ActiveFieldTestModel[0][attributeName]" aria-invalid="true">
<div class="hint-block">Hint for attributeName attribute</div>
<div class="help-block">Some error</div>
</div>
EOD;

        $actualValue = $this->activeField->render();
        $this->assertEqualsWithoutLE($expectedValue, $actualValue);
    }

    public function testEmptyTag(): void
    {
        $this->activeField->options = ['tag' => false];
        $expectedValue = '<input type="hidden" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]">';
        $actualValue = $this->activeField->hiddenInput()->label(false)->error(false)->hint(false)->render();
        $this->assertEqualsWithoutLE($expectedValue, trim($actualValue));
    }

    public function testWidget(): void
    {
        $this->activeField->widget(TestInputWidget::class);
        $this->assertEquals('Render: ' . TestInputWidget::class, $this->activeField->parts['{input}']);
        $widget = TestInputWidget::$lastInstance;

        $this->assertSame($this->activeField->model, $widget->model);
        $this->assertEquals($this->activeField->attribute, $widget->attribute);
        $this->assertSame($this->activeField->form->view, $widget->view);
        $this->assertSame($this->activeField, $widget->field);

        $this->activeField->widget(TestInputWidget::class, ['options' => ['id' => 'test-id']]);
        $this->assertEquals('test-id', $this->activeField->labelOptions['for']);
    }

    public function testWidgetOptions(): void
    {
        $this->activeField->form->validationStateOn = ActiveForm::VALIDATION_STATE_ON_INPUT;
        $this->activeField->model->addError('attributeName', 'error');

        $this->activeField->widget(TestInputWidget::class);
        $widget = TestInputWidget::$lastInstance;
        $expectedOptions = [
            'class' => 'form-control has-error',
            'aria-invalid' => 'true',
            'id' => 'activefieldtestmodel-attributename',
        ];
        $this->assertEquals($expectedOptions, $widget->options);

        $this->activeField->inputOptions = [];
        $this->activeField->widget(TestInputWidget::class);
        $widget = TestInputWidget::$lastInstance;
        $expectedOptions = [
            'class' => 'has-error',
            'aria-invalid' => 'true',
            'id' => 'activefieldtestmodel-attributename',
        ];
        $this->assertEquals($expectedOptions, $widget->options);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/14773
     */
    #[Depends('testHiddenInput')]
    public function testOptionsClass(): void
    {
        $this->activeField->options = ['class' => 'test-wrapper'];
        $expectedValue = <<<'HTML'
<div class="test-wrapper field-activefieldtestmodel-attributename">

<input type="hidden" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]">


</div>
HTML;
        $actualValue = $this->activeField->hiddenInput()->label(false)->error(false)->hint(false)->render();
        $this->assertEqualsWithoutLE($expectedValue, trim($actualValue));

        $this->activeField->options = ['class' => ['test-wrapper', 'test-add']];
        $expectedValue = <<<'HTML'
<div class="test-wrapper test-add field-activefieldtestmodel-attributename">

<input type="hidden" id="activefieldtestmodel-attributename" class="form-control" name="ActiveFieldTestModel[attributeName]">


</div>
HTML;
        $actualValue = $this->activeField->hiddenInput()->label(false)->error(false)->hint(false)->render();
        $this->assertEqualsWithoutLE($expectedValue, trim($actualValue));
    }

    public function testInputOptionsTransferToWidget(): void
    {
        $widget = $this->activeField->widget(TestMaskedInput::class, [
            'mask' => '999-999-9999',
            'options' => ['placeholder' => 'pholder_direct'],
        ]);
        $this->assertStringContainsString('placeholder="pholder_direct"', (string) $widget);

        // use regex clientOptions instead mask
        $widget = $this->activeField->widget(TestMaskedInput::class, [
            'options' => ['placeholder' => 'pholder_direct'],
            'clientOptions' => ['regex' => '^.*$'],
        ]);
        $this->assertStringContainsString('placeholder="pholder_direct"', (string) $widget);

        // transfer options from ActiveField to widget
        $this->activeField->inputOptions = ['placeholder' => 'pholder_input'];
        $widget = $this->activeField->widget(TestMaskedInput::class, [
            'mask' => '999-999-9999',
        ]);
        $this->assertStringContainsString('placeholder="pholder_input"', (string) $widget);

        // set both AF and widget options (second one takes precedence)
        $this->activeField->inputOptions = ['placeholder' => 'pholder_both_input'];
        $widget = $this->activeField->widget(TestMaskedInput::class, [
            'mask' => '999-999-9999',
            'options' => ['placeholder' => 'pholder_both_direct']
        ]);
        $this->assertStringContainsString('placeholder="pholder_both_direct"', (string) $widget);
    }

    public function testExceptionToString(): void
    {
        $field = new TestActiveFieldWithException();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception in toString.');

        (string) $field;
    }

    /**
     * Helper methods.
     */
    protected function getView()
    {
        $view = new View();
        $view->setAssetManager(new AssetManager([
            'basePath' => '@testWebRoot/assets',
            'baseUrl' => '@testWeb/assets',
        ]));

        return $view;
    }
}

class ActiveFieldTestModel extends DynamicModel
{
    public function attributeHints()
    {
        return [
            'attributeName' => 'Hint for attributeName attribute',
        ];
    }
}

/**
 * Helper Classes.
 */
class ActiveFieldExtend extends ActiveField
{
    private $getClientOptionsEmpty;

    public function __construct($getClientOptionsEmpty = true)
    {
        $this->getClientOptionsEmpty = $getClientOptionsEmpty;
    }

    public function setClientOptionsEmpty($value): void
    {
        $this->getClientOptionsEmpty = (bool) $value;
    }

    /**
     * Useful to test other methods from ActiveField, that call ActiveField::getClientOptions()
     * but it's return value is not relevant for the test being run.
     */
    public function getClientOptions()
    {
        return ($this->getClientOptionsEmpty) ? [] : parent::getClientOptions();
    }
}

class TestValidator extends Validator
{
    public function clientValidateAttribute($object, $attribute, $view)
    {
        return 'return true;';
    }

    public function setWhenClient($js): void
    {
        $this->whenClient = $js;
    }
}

class TestInputWidget extends InputWidget
{
    /**
     * @var static
     */
    public static $lastInstance;

    public function init(): void
    {
        parent::init();
        self::$lastInstance = $this;
    }

    public function run()
    {
        return 'Render: ' . get_class($this);
    }
}

class TestMaskedInput extends MaskedInput
{
    /**
     * @var static
     */
    public static $lastInstance;

    public function init(): void
    {
        parent::init();
        self::$lastInstance = $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function run()
    {
        return 'Options: ' . implode(
            ', ',
            array_map(
                static fn ($v, $k): string => sprintf('%s="%s"', $k, $v),
                $this->options,
                array_keys($this->options),
            ),
        );
    }
}

class TestActiveFieldWithException extends ActiveField
{
    public function render($content = null)
    {
        throw new Exception('Test exception in toString.');
    }
}
