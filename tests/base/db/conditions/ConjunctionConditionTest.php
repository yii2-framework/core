<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db\conditions;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use yii\db\conditions\AndCondition;
use yii\db\conditions\OrCondition;

/**
 * Unit tests for {@see AndCondition} and {@see OrCondition}.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('condition')]
final class ConjunctionConditionTest extends TestCase
{
    public function testAndFromArrayDefinition(): void
    {
        $condition = AndCondition::fromArrayDefinition('AND', ['id=1', 'id=2']);

        self::assertInstanceOf(
            AndCondition::class,
            $condition,
            'Should return an AndCondition instance.',
        );
        self::assertSame(
            ['id=1', 'id=2'],
            $condition->getExpressions(),
            'Expressions should match the operands.',
        );
        self::assertSame(
            'AND',
            $condition->getOperator(),
            "Operator should be 'AND'.",
        );
    }

    public function testOrFromArrayDefinition(): void
    {
        $condition = OrCondition::fromArrayDefinition('OR', ['id=1', 'id=2']);

        self::assertInstanceOf(
            OrCondition::class,
            $condition,
            'Should return an OrCondition instance.',
        );
        self::assertSame(
            ['id=1', 'id=2'],
            $condition->getExpressions(),
            'Expressions should match the operands.');
        self::assertSame(
            'OR',
            $condition->getOperator(),
            "Operator should be 'OR'."
        );
    }

    public function testAndFromArrayDefinitionWithEmptyOperands(): void
    {
        $condition = AndCondition::fromArrayDefinition('AND', []);

        self::assertEmpty(
            $condition->getExpressions(),
            'Expressions should be empty for empty operands.',
        );
    }

    public function testOrFromArrayDefinitionWithEmptyOperands(): void
    {
        $condition = OrCondition::fromArrayDefinition('OR', []);

        self::assertEmpty(
            $condition->getExpressions(),
            'Expressions should be empty for empty operands.',
        );
    }

    public function testGetExpressionsReturnsConstructorValue(): void
    {
        $condition = new AndCondition(['a', 'b', 'c']);

        self::assertSame(
            ['a', 'b', 'c'],
            $condition->getExpressions(),
            'Should return the constructor value.',
        );
    }
}
