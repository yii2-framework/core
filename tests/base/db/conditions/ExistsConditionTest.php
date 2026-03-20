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
use yii\db\conditions\ExistsCondition;
use yii\db\Query;

/**
 * Unit tests for {@see ExistsCondition}.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('condition')]
final class ExistsConditionTest extends TestCase
{
    public function testFromArrayDefinition(): void
    {
        $subQuery = (new Query())->select('id')->from('users');

        $condition = ExistsCondition::fromArrayDefinition('EXISTS', [$subQuery]);

        self::assertInstanceOf(
            ExistsCondition::class,
            $condition,
            'Should return an ExistsCondition instance.',
        );
        self::assertSame(
            'EXISTS',
            $condition->getOperator(),
            'Operator should match.',
        );
        self::assertSame(
            $subQuery,
            $condition->getQuery(),
            'Query should match the provided subquery.'
        );
    }

    public function testGetOperatorReturnsConstructorValue(): void
    {
        $condition = new ExistsCondition('NOT EXISTS', new Query());

        self::assertSame(
            'NOT EXISTS',
            $condition->getOperator(),
            'Should return the constructor value.',
        );
    }

    public function testGetQueryReturnsConstructorValue(): void
    {
        $query = (new Query())->select('1')->from('test');

        $condition = new ExistsCondition('EXISTS', $query);

        self::assertSame(
            $query,
            $condition->getQuery(),
            'Should return the constructor value.',
        );
    }

    public function testThrowInvalidArgumentExceptionWhenFromArrayDefinitionHasNoOperands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subquery for EXISTS operator must be a Query object.');

        ExistsCondition::fromArrayDefinition('EXISTS', []);
    }

    public function testThrowInvalidArgumentExceptionWhenFromArrayDefinitionHasNonQueryOperand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subquery for EXISTS operator must be a Query object.');

        ExistsCondition::fromArrayDefinition('EXISTS', ['not a query']);
    }
}
