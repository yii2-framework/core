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
use yii\db\conditions\LikeCondition;

/**
 * Unit tests for {@see LikeCondition}.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('condition')]
final class LikeConditionTest extends TestCase
{
    public function testFromArrayDefinition(): void
    {
        $condition = LikeCondition::fromArrayDefinition('LIKE', ['name', 'foo%']);

        self::assertSame(
            'name',
            $condition->getColumn(),
            'Column should match the first operand.',
        );
        self::assertSame(
            'LIKE',
            $condition->getOperator(),
            'Operator should match the provided operator.',
        );
        self::assertSame(
            'foo%',
            $condition->getValue(),
            'Value should match the second operand.',
        );
        self::assertNull(
            $condition->getEscapingReplacements(),
            "Escaping replacements should default to 'null'.",
        );
    }

    public function testFromArrayDefinitionWithEscapingReplacements(): void
    {
        $replacements = ['%' => '\%', '_' => '\_'];

        $condition = LikeCondition::fromArrayDefinition('LIKE', ['name', 'foo%', $replacements]);

        self::assertSame(
            'name',
            $condition->getColumn(),
            'Column should match the first operand.',
        );
        self::assertSame(
            'foo%',
            $condition->getValue(),
            'Value should match the second operand.',
        );
        self::assertSame(
            $replacements,
            $condition->getEscapingReplacements(),
            'Escaping replacements should match the third operand.',
        );
    }

    public function testFromArrayDefinitionWithFalseEscapingReplacements(): void
    {
        $condition = LikeCondition::fromArrayDefinition('LIKE', ['name', 'foo%', false]);

        self::assertFalse(
            $condition->getEscapingReplacements(),
            "Escaping replacements set to 'false' should disable escaping.",
        );
    }


    public function testSetEscapingReplacements(): void
    {
        $condition = new LikeCondition('name', 'LIKE', 'foo%');

        self::assertNull(
            $condition->getEscapingReplacements(),
            "Default escaping replacements should be 'null'."
        );

        $replacements = ['%' => '\%', '_' => '\_'];

        $condition->setEscapingReplacements($replacements);

        self::assertSame(
            $replacements,
            $condition->getEscapingReplacements(),
            'Escaping replacements should match the set value.',
        );
    }

    public function testSetEscapingReplacementsToFalse(): void
    {
        $condition = new LikeCondition('name', 'LIKE', 'foo%');

        $condition->setEscapingReplacements(false);

        self::assertFalse(
            $condition->getEscapingReplacements(),
            "Setting escaping replacements to 'false' should disable escaping.",
        );
    }

    public function testSetEscapingReplacementsToNull(): void
    {
        $condition = new LikeCondition('name', 'LIKE', 'foo%');

        $condition->setEscapingReplacements(['%' => '\%']);
        $condition->setEscapingReplacements(null);

        self::assertNull(
            $condition->getEscapingReplacements(),
            "Setting escaping replacements to 'null' should delegate escaping to the builder.",
        );
    }

    public function testSetEscapingReplacementsToEmptyArray(): void
    {
        $condition = new LikeCondition('name', 'LIKE', 'foo%');

        $condition->setEscapingReplacements([]);

        self::assertEmpty(
            $condition->getEscapingReplacements(),
            "Setting escaping replacements to an empty array should keep builder-managed escaping semantics.",
        );
    }

   public function testThrowInvalidArgumentExceptionWhenFromArrayDefinitionHasMissingOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'LIKE' requires two operands.");

        LikeCondition::fromArrayDefinition('LIKE', []);
    }

    public function testThrowInvalidArgumentExceptionWhenFromArrayDefinitionHasOneOperand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'NOT LIKE' requires two operands.");

        LikeCondition::fromArrayDefinition('NOT LIKE', ['name']);
    }
}
