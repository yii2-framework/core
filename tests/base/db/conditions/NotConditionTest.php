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
use yii\db\conditions\NotCondition;

/**
 * Unit tests for {@see NotCondition}.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('condition')]
final class NotConditionTest extends TestCase
{
    public function testFromArrayDefinition(): void
    {
        $condition = NotCondition::fromArrayDefinition('NOT', ['name']);

        self::assertInstanceOf(
            NotCondition::class,
            $condition,
            'Should return a NotCondition instance.',
        );
        self::assertSame(
            'name',
            $condition->getCondition(),
            'Condition should match the operand.',
        );
    }

    public function testGetConditionReturnsConstructorValue(): void
    {
        $condition = new NotCondition('active = 1');

        self::assertSame(
            'active = 1',
            $condition->getCondition(),
            'Should return the constructor value.'
        );
    }

    public function testThrowInvalidArgumentExceptionWhenFromArrayDefinitionHasNoOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'NOT' requires exactly one operand.");

        NotCondition::fromArrayDefinition('NOT', []);
    }

    public function testThrowInvalidArgumentExceptionWhenFromArrayDefinitionHasTwoOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'NOT' requires exactly one operand.");

        NotCondition::fromArrayDefinition('NOT', ['a', 'b']);
    }
}
