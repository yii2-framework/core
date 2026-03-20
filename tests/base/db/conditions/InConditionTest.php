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
use yii\db\ArrayExpression;
use yii\db\conditions\InCondition;

/**
 * Unit tests for {@see InCondition}.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('condition')]
final class InConditionTest extends TestCase
{
    public function testGetColumnPreservesArrayExpression(): void
    {
        $arrayExpression = new ArrayExpression([1, 2, 3], 'integer');
        $condition = new InCondition($arrayExpression, 'IN', [1, 2, 3]);

        $column = $condition->getColumn();

        self::assertInstanceOf(
            ArrayExpression::class,
            $column,
            "Should preserve ArrayExpression instead of converting it to a plain 'array'.",
        );
        self::assertSame(
            [1, 2, 3],
            $column->getValue(),
            "Should remain intact after 'getColumn()'.",
        );
        self::assertSame(
            'integer',
            $column->getType(),
            "Should be preserved after 'getColumn()'.",
        );
        self::assertSame(
            1,
            $column->getDimension(),
            "Should be preserved after 'getColumn()'.",
        );
    }

    public function testGetValuesPreservesArrayExpression(): void
    {
        $arrayExpression = new ArrayExpression([1, 2, 3], 'integer');
        $condition = new InCondition('id', 'IN', $arrayExpression);

        $values = $condition->getValues();

        self::assertInstanceOf(
            ArrayExpression::class,
            $values,
            "Should preserve ArrayExpression instead of converting it to a plain 'array'.",
        );
        self::assertSame(
            [1, 2, 3],
            $values->getValue(),
            "Should remain intact after 'getValues()'.",
        );
        self::assertSame(
            'integer',
            $values->getType(),
            "Should be preserved after 'getValues()'.",
        );
        self::assertSame(
            1,
            $values->getDimension(),
            "Should be preserved after 'getValues()'.",
        );
    }
}
