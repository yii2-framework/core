<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\helpers\providers;

/**
 * Data provider for {@see \yiiunit\framework\helpers\HtmlAttributeTest} test cases.
 *
 * Provides representative input/output pairs for HTML attribute handling.
 *
 * @copyright Copyright (c) 2008 Yii Software LLC.
 * @license https://www.yiiframework.com/license/
 */
final class HtmlAttributeProvider
{
    /**
     * @phpstan-return array<string, array{string, array<string, mixed>}>
     */
    public static function renderTagAttributes(): array
    {
        return [
            'empty attributes' => [
                '',
                [],
            ],
            'name and encoded value' => [
                ' name="test" value="1&lt;&gt;"',
                ['name' => 'test', 'empty' => null, 'value' => '1<>'],
            ],
            'boolean attributes' => [
                ' checked disabled',
                ['checked' => true, 'disabled' => true, 'hidden' => false],
            ],
            'class array' => [
                ' class="first second"',
                ['class' => ['first', 'second']],
            ],
            'class array with null and empty' => [
                ' class="first second"',
                ['class' => ['first', null, 'second', '']],
            ],
            'empty class array' => [
                '',
                ['class' => []],
            ],
            'style array' => [
                ' style="width: 100px; height: 200px;"',
                ['style' => ['width' => '100px', 'height' => '200px']],
            ],
            'empty style array' => [
                '',
                ['style' => []],
            ],
            'data attribute with empty array' => [
                " data-foo='[]'",
                ['data' => ['foo' => []]],
            ],
            'data attribute with true' => [
                ' data-foo',
                ['data' => ['foo' => true]],
            ],
            'data attribute with false' => [
                '',
                ['data' => ['foo' => false]],
            ],
            'data attribute with null' => [
                '',
                ['data' => ['foo' => null]],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{array<string, mixed>, string|string[], array<string, mixed>}>
     */
    public static function addCssClass(): array
    {
        return [
            'add to empty options' => [
                [],
                'test',
                ['class' => 'test'],
            ],
            'add duplicate string class' => [
                ['class' => 'test'],
                'test',
                ['class' => 'test'],
            ],
            'add new string class' => [
                ['class' => 'test'],
                'test2',
                ['class' => 'test test2'],
            ],
            'add duplicate after two classes' => [
                ['class' => 'test test2'],
                'test',
                ['class' => 'test test2'],
            ],
            'add third string class' => [
                ['class' => 'test test2'],
                'test3',
                ['class' => 'test test2 test3'],
            ],
            'add string to array class' => [
                ['class' => ['test']],
                'test2',
                ['class' => ['test', 'test2']],
            ],
            'add duplicate string to array class' => [
                ['class' => ['test', 'test2']],
                'test2',
                ['class' => ['test', 'test2']],
            ],
            'add array to array class' => [
                ['class' => ['test', 'test2']],
                ['test3'],
                ['class' => ['test', 'test2', 'test3']],
            ],
            'add array of classes to string class' => [
                ['class' => 'test'],
                ['test1', 'test2'],
                ['class' => 'test test1 test2'],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{array<string, mixed>, array<string, string>, array<string, string>}>
     */
    public static function mergeCssClass(): array
    {
        return [
            'existing key is preserved' => [
                ['class' => ['persistent' => 'test1']],
                ['persistent' => 'test2'],
                ['persistent' => 'test1'],
            ],
            'new key is added' => [
                ['class' => ['persistent' => 'test1']],
                ['additional' => 'test2'],
                ['persistent' => 'test1', 'additional' => 'test2'],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{array<string, mixed>, string|array<string, string>, bool, string}>
     */
    public static function addCssStyle(): array
    {
        return [
            'string style with overwrite' => [
                ['style' => 'width: 100px; height: 200px;'],
                'width: 110px; color: red;',
                true,
                'width: 110px; height: 200px; color: red;',
            ],
            'array style with overwrite' => [
                ['style' => 'width: 100px; height: 200px;'],
                ['width' => '110px', 'color' => 'red'],
                true,
                'width: 110px; height: 200px; color: red;',
            ],
            'string style without overwrite' => [
                ['style' => 'width: 100px; height: 200px;'],
                'width: 110px; color: red;',
                false,
                'width: 100px; height: 200px; color: red;',
            ],
            'add to empty options with overwrite' => [
                [],
                'width: 110px; color: red;',
                true,
                'width: 110px; color: red;',
            ],
            'add to empty options without overwrite' => [
                [],
                'width: 110px; color: red;',
                false,
                'width: 110px; color: red;',
            ],
            'array style to array options without overwrite' => [
                ['style' => ['width' => '100px']],
                ['color' => 'red'],
                false,
                'width: 100px; color: red;',
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{array<string, mixed>, string|string[], string}>
     */
    public static function removeCssStyle(): array
    {
        return [
            'remove single property' => [
                ['style' => 'width: 110px; height: 200px; color: red;'],
                'width',
                'height: 200px; color: red;',
            ],
            'remove property as array' => [
                ['style' => 'height: 200px; color: red;'],
                ['height'],
                'color: red;',
            ],
            'remove from array style' => [
                ['style' => ['color' => 'red', 'width' => '100px']],
                ['color'],
                'width: 100px;',
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{array<string, mixed>, string|string[], array<string, mixed>}>
     */
    public static function removeCssClass(): array
    {
        return [
            'remove from string class' => [
                ['class' => 'test test2 test3'],
                'test2',
                ['class' => 'test test3'],
            ],
            'remove non-existent from string class' => [
                ['class' => 'test test3'],
                'test2',
                ['class' => 'test test3'],
            ],
            'remove first from string class' => [
                ['class' => 'test test3'],
                'test',
                ['class' => 'test3'],
            ],
            'remove last string class leaves empty' => [
                ['class' => 'test3'],
                'test3',
                [],
            ],
            'remove from array class' => [
                ['class' => ['test', 'test2', 'test3']],
                'test2',
                ['class' => ['test', 2 => 'test3']],
            ],
            'remove one from sparse array class' => [
                ['class' => ['test', 2 => 'test3']],
                'test',
                ['class' => [2 => 'test3']],
            ],
            'remove array of classes from string class' => [
                ['class' => 'test test1 test2'],
                ['test1', 'test2'],
                ['class' => 'test'],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, array<string, mixed>}>
     */
    public static function booleanAttributes(): array
    {
        return [
            'required false' => [
                '<input type="email" name="mail">',
                ['required' => false],
            ],
            'required non-boolean value' => [
                '<input type="email" name="mail" required="hi">',
                ['required' => 'hi'],
            ],
            'required true' => [
                '<input type="email" name="mail" required>',
                ['required' => true],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, array<string, mixed>}>
     */
    public static function dataAttributes(): array
    {
        return [
            'angular-style attributes' => [
                <<<HTML
                <link src="xyz" ng-a="1" ng-b="c">
                HTML,
                ['src' => 'xyz', 'ng' => ['a' => 1, 'b' => 'c']],
            ],
            'aria attributes' => [
                <<<HTML
                <link src="xyz" aria-a="1" aria-b="c">
                HTML,
                ['src' => 'xyz', 'aria' => ['a' => 1, 'b' => 'c']],
            ],
            'data attributes' => [
                <<<HTML
                <link src="xyz" data-a="1" data-b="c">
                HTML,
                ['src' => 'xyz', 'data' => ['a' => 1, 'b' => 'c']],
            ],
            'data-ng attributes' => [
                <<<HTML
                <link src="xyz" data-ng-a="1" data-ng-b="c">
                HTML,
                ['src' => 'xyz', 'data-ng' => ['a' => 1, 'b' => 'c']],
            ],
            'JSON encoded attribute' => [
                <<<HTML
                <link src='{"a":1,"b":"It\u0027s"}'>
                HTML,
                ['src' => ['a' => 1, 'b' => "It's"]],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function validAttributeNames(): array
    {
        return [
            'cyrillic without brackets' => ['ИІК', 'ИІК'],
            'indexed prefix and suffix' => ['[0]a[0]', 'a'],
            'indexed prefix simple' => ['[0]a', 'a'],
            'indexed prefix with cyrillic' => ['[0]ИІК[0]', 'ИІК'],
            'indexed prefix with dot suffix' => ['[0]a.[0]', 'a.'],
            'indexed prefix with unicode dots' => ['[0]test.ööößß.d', 'test.ööößß.d'],
            'mixed brackets with dots' => ['asd]asdf.asdfa[asdfa', 'asdf.asdfa'],
            'reversed brackets with cyrillic' => [']ИІК[', 'ИІК'],
            'simple name with index' => ['a[0]', 'a'],
            'simple name' => ['a', 'a'],
            'single umlaut' => ['ä', 'ä'],
            'unicode with double dots' => ['asdf]öáöio..[asdfasdf', 'öáöio..'],
            'unicode without brackets' => ['öáöio', 'öáöio'],
        ];
    }

    /**
     * @phpstan-return array<string, array{string}>
     */
    public static function invalidAttributeNames(): array
    {
        return [
            'comma' => ['a,b'],
            'dots and spaces' => ['. ..'],
            'plus sign' => ['a +b'],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function getInputId(): array
    {
        return self::inputIdDataset();
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function getInputIdByName(): array
    {
        return self::inputIdDataset();
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    private static function inputIdDataset(): array
    {
        return [
            'bracket name' => ['foo[]', 'dynamicmodel-foo'],
            'camel case' => ['FooBar', 'dynamicmodel-foobar'],
            'cyrillic name' => ['ФуБарБаз', 'dynamicmodel-фубарбаз'],
            'dotted name' => ['foo.bar', 'dynamicmodel-foo-bar'],
            'german umlaut in name' => ['bild_groß_dateiname', 'dynamicmodel-bild_groß_dateiname'],
            'nested brackets' => ['foo[bar][baz]', 'dynamicmodel-foo-bar-baz'],
            'simple name' => ['foo', 'dynamicmodel-foo'],
            'underscore mixed case' => ['Foo_Bar', 'dynamicmodel-foo_bar'],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function getInputNameWithoutBrackets(): array
    {
        return [
            'nested with trailing empty brackets' => ['MyForm[grid1][key][]', 'MyForm[grid1][key]'],
            'simple name with brackets' => ['selection[]', 'selection'],
            'simple name' => ['selection', 'selection'],
            'trailing empty brackets' => ['MyForm[grid1][]', 'MyForm[grid1]'],
            'trailing keyed brackets' => ['MyForm[grid1][key]', 'MyForm[grid1][key]'],
        ];
    }
}
