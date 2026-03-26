<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\grid;

use PHPUnit\Framework\Attributes\Group;
use Yii;
use yii\base\InvalidConfigException;
use yii\data\ArrayDataProvider;
use yii\grid\CheckboxColumn;
use yii\grid\GridView;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\web\JqueryAsset;
use yiiunit\framework\i18n\IntlTestHelper;
use yiiunit\TestCase;

#[Group('grid')]
#[Group('checkbox-column')]
class CheckboxColumnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        IntlTestHelper::resetIntlStatus();

        $this->mockApplication();

        Yii::setAlias('@webroot', '@yiiunit/runtime');
        Yii::setAlias('@web', 'http://localhost/');

        FileHelper::createDirectory(Yii::getAlias('@webroot/assets'));

        Yii::$app->assetManager->bundles[JqueryAsset::class] = false;
    }

    public function testInputName(): void
    {
        $column = new CheckboxColumn(['name' => 'selection', 'grid' => $this->getGrid()]);

        self::assertSame(
            <<<HTML
            <th><input type="hidden" name="selection" value=""><input type="checkbox" class="select-on-check-all" name="selection_all" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            "Header cell for name 'selection' does not match.",
        );

        $column = new CheckboxColumn(['name' => 'selections[]', 'grid' => $this->getGrid()]);

        self::assertSame(
            <<<HTML
            <th><input type="hidden" name="selections" value=""><input type="checkbox" class="select-on-check-all" name="selections_all" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            "Header cell for name 'selections[]' does not match.",
        );

        $column = new CheckboxColumn(['name' => 'MyForm[grid1]', 'grid' => $this->getGrid()]);

        self::assertSame(
            <<<HTML
            <th><input type="hidden" name="MyForm[grid1]" value=""><input type="checkbox" class="select-on-check-all" name="MyForm[grid1_all]" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            "Header cell for name 'MyForm[grid1]' does not match.",
        );

        $column = new CheckboxColumn(['name' => 'MyForm[grid1][]', 'grid' => $this->getGrid()]);

        self::assertSame(
            <<<HTML
            <th><input type="hidden" name="MyForm[grid1]" value=""><input type="checkbox" class="select-on-check-all" name="MyForm[grid1_all]" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            "Header cell for name 'MyForm[grid1][]' does not match.",
        );

        $column = new CheckboxColumn(['name' => 'MyForm[grid1][key]', 'grid' => $this->getGrid()]);

        self::assertSame(
            <<<HTML
            <th><input type="hidden" name="MyForm[grid1][key]" value=""><input type="checkbox" class="select-on-check-all" name="MyForm[grid1][key_all]" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            "Header cell for name 'MyForm[grid1][key]' does not match.",
        );

        $column = new CheckboxColumn(['name' => 'MyForm[grid1][key][]', 'grid' => $this->getGrid()]);

        self::assertSame(
            <<<HTML
            <th><input type="hidden" name="MyForm[grid1][key]" value=""><input type="checkbox" class="select-on-check-all" name="MyForm[grid1][key_all]" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            "Header cell for name 'MyForm[grid1][key][]' does not match.",
        );
    }

    public function testUnselectHiddenInput(): void
    {
        $column = new CheckboxColumn(['grid' => $this->getGrid()]);

        self::assertSame(
            <<<HTML
            <th><input type="hidden" name="selection" value=""><input type="checkbox" class="select-on-check-all" name="selection_all" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            'Header cell with default unselect does not match.',
        );

        $column = new CheckboxColumn(['name' => 'MyForm[grid1][]', 'grid' => $this->getGrid()]);

        self::assertSame(
            <<<HTML
            <th><input type="hidden" name="MyForm[grid1]" value=""><input type="checkbox" class="select-on-check-all" name="MyForm[grid1_all]" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            'Header cell with nested input name does not match.',
        );
    }

    public function testUnselectCanBeDisabled(): void
    {
        $column = new CheckboxColumn(['unselect' => null, 'grid' => $this->getGrid()]);

        self::assertSame(
            <<<HTML
            <th><input type="checkbox" class="select-on-check-all" name="selection_all" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            "Header cell with 'null' unselect should not contain hidden input.",
        );
    }

    public function testUnselectHiddenInputPropagatesCheckboxAttributes(): void
    {
        $column = new CheckboxColumn(
            [
                'checkboxOptions' => ['disabled' => true, 'form' => 'bulk-form'],
                'grid' => $this->getGrid(),
            ],
        );

        self::assertSame(
            <<<HTML
            <th><input type="hidden" name="selection" value="" form="bulk-form" disabled><input type="checkbox" class="select-on-check-all" name="selection_all" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            "Hidden input should propagate 'disabled' and 'form' from checkboxOptions.",
        );
    }

    public function testUnselectWithoutHeaderIsRenderedOnce(): void
    {
        $grid = $this->getGrid(
            [
                'showHeader' => false,
                'dataProvider' => new ArrayDataProvider(
                    [
                        'allModels' => [['id' => 1], ['id' => 2]],
                        'key' => 'id',
                    ],
                ),
            ],
        );

        $column = new CheckboxColumn(['grid' => $grid]);

        $firstRow = $column->renderDataCell(['id' => 1], 1, 0);
        $secondRow = $column->renderDataCell(['id' => 2], 2, 1);

        self::assertSame(
            <<<HTML
            <td><input type="hidden" name="selection" value=""><input type="checkbox" name="selection[]" value="1"></td>
            HTML,
            $firstRow,
            'First row should contain hidden unselect input.',
        );
        self::assertSame(
            <<<HTML
            <td><input type="checkbox" name="selection[]" value="2"></td>
            HTML,
            $secondRow,
            'Second row should not contain hidden unselect input.',
        );
    }

    public function testInputValue(): void
    {
        $column = new CheckboxColumn(['grid' => $this->getGrid()]);

        self::assertSame(
            <<<HTML
            <td><input type="checkbox" name="selection[]" value="1"></td>
            HTML,
            $column->renderDataCell([], 1, 0),
            'Data cell with integer key 1 does not match.',
        );
        self::assertSame(
            <<<HTML
            <td><input type="checkbox" name="selection[]" value="42"></td>
            HTML,
            $column->renderDataCell([], 42, 0),
            'Data cell with integer key 42 does not match.',
        );
        self::assertSame(
            <<<HTML
            <td><input type="checkbox" name="selection[]" value="[1,42]"></td>
            HTML,
            $column->renderDataCell([], [1, 42], 0),
            'Data cell with array key does not match.',
        );

        $column = new CheckboxColumn(['checkboxOptions' => ['value' => 42], 'grid' => $this->getGrid()]);

        self::assertSame(
            <<<HTML
            <td><input type="checkbox" name="selection[]" value="42"></td>
            HTML,
            $column->renderDataCell([], 1, 0),
            'Data cell with fixed value option does not match.',
        );

        $column = new CheckboxColumn(
            [
                'checkboxOptions' => static fn($model, $key, $index, $column): array => [],
                'grid' => $this->getGrid(),
            ],
        );

        self::assertSame(
            <<<HTML
            <td><input type="checkbox" name="selection[]" value="1"></td>
            HTML,
            $column->renderDataCell([], 1, 0),
            'Data cell with closure returning empty array does not match for key 1.',
        );
        self::assertSame(
            <<<HTML
            <td><input type="checkbox" name="selection[]" value="42"></td>
            HTML,
            $column->renderDataCell([], 42, 0),
            'Data cell with closure returning empty array does not match for key 42.',
        );
        self::assertSame(
            <<<HTML
            <td><input type="checkbox" name="selection[]" value="[1,42]"></td>
            HTML,
            $column->renderDataCell([], [1, 42], 0),
            'Data cell with closure returning empty array does not match for array key.',
        );

        $column = new CheckboxColumn(
            [
                'checkboxOptions' => static fn($model, $key, $index, $column): array => ['value' => 42],
                'grid' => $this->getGrid(),
            ],
        );

        self::assertSame(
            <<<HTML
            <td><input type="checkbox" name="selection[]" value="42"></td>
            HTML,
            $column->renderDataCell([], 1, 0),
            'Data cell with closure returning fixed value does not match.',
        );
    }

    public function testContent(): void
    {
        $column = new CheckboxColumn(
            [
                'content' => static fn($model, $key, $index, $column): string|null => null,
                'grid' => $this->getGrid(),
            ],
        );

        self::assertSame(
            '<td></td>',
            $column->renderDataCell([], 1, 0),
            'Data cell with null content does not match.',
        );

        $column = new CheckboxColumn(
            [
                'content' => static fn($model, $key, $index, $column): string => Html::checkBox('checkBoxInput', false),
                'grid' => $this->getGrid(),
            ],
        );

        self::assertSame(
            <<<HTML
            <td><input type="checkbox" name="checkBoxInput" value="1"></td>
            HTML,
            $column->renderDataCell([], 1, 0),
            'Data cell with custom content does not match.',
        );
    }

    public function testUnselectOptionsOverridesClosureCheckboxOptions(): void
    {
        $column = new CheckboxColumn(
            [
                'checkboxOptions' => static fn($model, $key, $index, $column): array => [
                    'form' => "form-$key",
                    'disabled' => $key === 2,
                ],
                'unselectOptions' => ['form' => 'bulk-form', 'disabled' => true],
                'grid' => $this->getGrid(),
            ],
        );

        self::assertSame(
            <<<HTML
            <th><input type="hidden" name="selection" value="" form="bulk-form" disabled><input type="checkbox" class="select-on-check-all" name="selection_all" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            "Hidden input should use 'unselectOptions' attributes, not closure result.",
        );
    }

    public function testClosureCheckboxOptionsWithoutUnselectOptionsSkipsInheritance(): void
    {
        $column = new CheckboxColumn(
            [
                'checkboxOptions' => static fn($model, $key, $index, $column): array => [
                    'form' => "form-$key",
                    'disabled' => true,
                ],
                'grid' => $this->getGrid(),
            ],
        );

        self::assertSame(
            <<<HTML
            <th><input type="hidden" name="selection" value=""><input type="checkbox" class="select-on-check-all" name="selection_all" value="1"></th>
            HTML,
            $column->renderHeaderCell(),
            'Hidden input should not inherit attributes from closure in header context.',
        );
    }

    public function testClosureCheckboxOptionsDoesNotAffectHiddenInputWhenNoHeader(): void
    {
        $grid = $this->getGrid(
            [
                'showHeader' => false,
                'dataProvider' => new ArrayDataProvider(
                    [
                        'allModels' => [['id' => 1], ['id' => 2]],
                        'key' => 'id',
                    ],
                ),
            ],
        );

        $column = new CheckboxColumn(
            [
                'checkboxOptions' => static fn($model, $key, $index, $column): array => [
                    'form' => 'my-form',
                    'disabled' => $index === 0,
                ],
                'grid' => $grid,
            ],
        );

        $firstRow = $column->renderDataCell(['id' => 1], 1, 0);
        $secondRow = $column->renderDataCell(['id' => 2], 2, 1);

        self::assertSame(
            <<<HTML
            <td><input type="hidden" name="selection" value=""><input type="checkbox" name="selection[]" value="1" form="my-form" disabled></td>
            HTML,
            $firstRow,
            'Hidden input should not inherit closure attributes; checkbox should.',
        );
        self::assertSame(
            <<<HTML
            <td><input type="checkbox" name="selection[]" value="2" form="my-form"></td>
            HTML,
            $secondRow,
            'Second row should not contain hidden input.',
        );
    }

    public function testUnselectOptionsWithClosureWhenNoHeader(): void
    {
        $grid = $this->getGrid(
            [
                'showHeader' => false,
                'dataProvider' => new ArrayDataProvider(
                    [
                        'allModels' => [['id' => 1], ['id' => 2]],
                        'key' => 'id',
                    ],
                ),
            ],
        );

        $column = new CheckboxColumn(
            [
                'checkboxOptions' => static fn($model, $key, $index, $column): array => [
                    'form' => 'my-form',
                    'disabled' => $index === 0,
                ],
                'unselectOptions' => ['form' => 'my-form', 'disabled' => true],
                'grid' => $grid,
            ],
        );

        $firstRow = $column->renderDataCell(['id' => 1], 1, 0);

        self::assertSame(
            <<<HTML
            <td><input type="hidden" name="selection" value="" form="my-form" disabled><input type="checkbox" name="selection[]" value="1" form="my-form" disabled></td>
            HTML,
            $firstRow,
            "Hidden input should use 'unselectOptions' even in no-header mode.",
        );
    }

    public function testCustomHeaderWithUnselectHiddenInput(): void
    {
        $column = new CheckboxColumn(
            [
                'header' => 'Select',
                'grid' => $this->getGrid(),
            ],
        );

        self::assertSame(
            '<th><input type="hidden" name="selection" value="">Select</th>',
            $column->renderHeaderCell(),
            'Header cell with custom header text does not match.',
        );
    }

    public function testSingleModeHeaderWithUnselectHiddenInput(): void
    {
        $column = new CheckboxColumn(
            [
                'multiple' => false,
                'grid' => $this->getGrid(),
            ],
        );

        self::assertSame(
            '<th><input type="hidden" name="selection" value="">&nbsp;</th>',
            $column->renderHeaderCell(),
            'Header cell in single mode does not match.',
        );
    }

    public function testCssClassIsAppliedToCheckbox(): void
    {
        $column = new CheckboxColumn(
            [
                'cssClass' => 'custom-check',
                'grid' => $this->getGrid(
                    [
                        'dataProvider' => new ArrayDataProvider(
                            [
                                'allModels' => [['id' => 1]],
                                'key' => 'id',
                            ]
                        ),
                    ],
                ),
            ],
        );

        self::assertSame(
            <<<HTML
            <td><input type="checkbox" class="custom-check" name="selection[]" value="1"></td>
            HTML,
            $column->renderDataCell(['id' => 1], 1, 0),
            'Data cell should include custom CSS class on checkbox.',
        );
    }

    public function testThrowInvalidConfigExceptionForEmptyName(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The "name" property must be set.');

        new CheckboxColumn(['name' => '', 'grid' => $this->getGrid()]);
    }

    protected function getGrid(array $config = []): GridView
    {
        return new GridView(
            [
                'dataProvider' => new ArrayDataProvider(['allModels' => [], 'totalCount' => 0]),
                ...$config,
            ],
        );
    }
}
