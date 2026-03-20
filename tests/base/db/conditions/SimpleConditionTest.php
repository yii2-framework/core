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
use yii\base\InvalidArgumentException;
use yii\db\conditions\SimpleCondition;

/**
 * Unit tests for {@see SimpleCondition}.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('condition')]
final class SimpleConditionTest extends TestCase
{
    public function testFromArrayDefinition(): void
    {
        $condition = SimpleCondition::fromArrayDefinition('=', ['a', 'b']);

        self::assertSame(
            'a',
            $condition->getColumn(),
            'Column should match the first operand.',
        );
        self::assertSame(
            '=',
            $condition->getOperator(),
            'Operator should match.',
        );
        self::assertSame(
            'b',
            $condition->getValue(),
            'Value should match the second operand.',
        );
    }

    public function testFromArrayDefinitionWithNullValue(): void
    {
        $condition = SimpleCondition::fromArrayDefinition('=', ['a', null]);

        self::assertSame(
            'a',
            $condition->getColumn(),
            'Column should match the first operand.',
        );
        self::assertNull(
            $condition->getValue(),
            "Value should be 'null'.",
        );
    }

    public function testThrowInvalidArgumentExceptionWhenFromArrayDefinitionHasMissingOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator '=' requires two operands.");

        SimpleCondition::fromArrayDefinition('=', []);
    }

    public function testThrowInvalidArgumentExceptionWhenFromArrayDefinitionHasOneOperand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator '>' requires two operands.");

        SimpleCondition::fromArrayDefinition('>', ['a']);
    }

    public function testThrowInvalidArgumentExceptionWhenFromArrayDefinitionHasThreeOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator '<' requires two operands.");

        SimpleCondition::fromArrayDefinition('<', ['a', 'b', 'c']);
    }
}
