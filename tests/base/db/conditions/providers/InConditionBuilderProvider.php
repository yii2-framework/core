<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db\conditions\providers;

use Generator;
use yii\db\conditions\InCondition;
use yii\db\Expression;
use yii\db\Query;
use yiiunit\data\base\TraversableObject;

/**
 * Base data provider for {@see \yiiunit\base\db\conditions\BaseInConditionBuilderTest} test cases.
 *
 * Provides representative input/output pairs for the IN/NOT IN condition builder. Driver-specific providers extend this
 * class to add or override test cases.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class InConditionBuilderProvider
{
    /**
     * @phpstan-return array<string, array{mixed[]|object, string, mixed[]}>
     */
    public static function buildCondition(): array
    {
        return [
            // composite empty values
            'composite in with empty values' => [
                ['in', ['id', 'name', ], []],
                <<<SQL
                0=1
                SQL,
                [],
            ],
            'composite not in with empty values' => [
                ['not in', ['id', 'name'], []],
                '',
                [],
            ],
            // composite in
            'composite in' => [
                ['in', ['id', 'name'], [['id' => 1, 'name' => 'oy']]],
                <<<SQL
                (([[id]] = :qp0 AND [[name]] = :qp1))
                SQL,
                [':qp0' => 1, ':qp1' => 'oy'],
            ],
            'composite in (just one column)' => [
                ['in', ['id'], [['id' => 1, 'name' => 'Name1'], ['id' => 2, 'name' => 'Name2']]],
                <<<SQL
                [[id]] IN (:qp0, :qp1)
                SQL,
                [':qp0' => 1, ':qp1' => 2],
            ],
            'composite in using array objects (just one column)' => [
                [
                    'in',
                    new TraversableObject(['id']),
                    new TraversableObject([['id' => 1, 'name' => 'Name1'], ['id' => 2, 'name' => 'Name2']]),
                ],
                <<<SQL
                [[id]] IN (:qp0, :qp1)
                SQL,
                [':qp0' => 1, ':qp1' => 2],
            ],
            'composite in using array objects' => [
                [
                    'in',
                    new TraversableObject(['id', 'name']),
                    new TraversableObject([['id' => 1, 'name' => 'oy'], ['id' => 2, 'name' => 'yo']]),
                ],
                <<<SQL
                (([[id]] = :qp0 AND [[name]] = :qp1) OR ([[id]] = :qp2 AND [[name]] = :qp3))
                SQL,
                [':qp0' => 1, ':qp1' => 'oy', ':qp2' => 2, ':qp3' => 'yo'],
            ],
            // composite in with multiple rows
            'composite in with multiple rows' => [
                ['in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                <<<SQL
                (([[id]] = :qp0 AND [[name]] = :qp1) OR ([[id]] = :qp2 AND [[name]] = :qp3))
                SQL,
                [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar'],
            ],
            'composite not in with expression column' => [
                [
                    'not in',
                    [new Expression('id'), 'name'],
                    [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']],
                ],
                <<<SQL
                (([[id]] <> :qp0 OR [[name]] <> :qp1) AND ([[id]] <> :qp2 OR [[name]] <> :qp3))
                SQL,
                [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar'],
            ],
            'composite not in with multiple rows' => [
                ['not in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                <<<SQL
                (([[id]] <> :qp0 OR [[name]] <> :qp1) AND ([[id]] <> :qp2 OR [[name]] <> :qp3))
                SQL,
                [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar'],
            ],
            // composite in with null
            'composite in with all null' => [
                ['in', ['id', 'name'], [['id' => null, 'name' => null]]],
                <<<SQL
                (([[id]] IS NULL AND [[name]] IS NULL))
                SQL,
                [],
            ],
            'composite in with mixed null' => [
                ['in', ['id', 'name'], [['id' => 1, 'name' => 'oy'], ['id' => 2, 'name' => null]]],
                <<<SQL
                (([[id]] = :qp0 AND [[name]] = :qp1) OR ([[id]] = :qp2 AND [[name]] IS NULL))
                SQL,
                [':qp0' => 1, ':qp1' => 'oy', ':qp2' => 2],
            ],
            'composite in with null' => [
                ['in', ['id', 'name'], [['id' => 1, 'name' => null]]],
                <<<SQL
                (([[id]] = :qp0 AND [[name]] IS NULL))
                SQL,
                [':qp0' => 1],
            ],
            'composite not in' => [
                ['not in', ['id', 'name'], [['id' => 1, 'name' => 'oy']]],
                <<<SQL
                (([[id]] <> :qp0 OR [[name]] <> :qp1))
                SQL,
                [':qp0' => 1, ':qp1' => 'oy'],
            ],
            'composite not in with null' => [
                ['not in', ['id', 'name'], [['id' => 1, 'name' => null]]],
                <<<SQL
                (([[id]] <> :qp0 OR [[name]] IS NOT NULL))
                SQL,
                [':qp0' => 1],
            ],
            // empty column
            'InCondition not in with empty column' => [
                new InCondition([], 'not in', 1),
                '',
                [],
            ],
            'InCondition in with empty column' => [
                new InCondition([], 'in', 1),
                <<<SQL
                0=1
                SQL,
                [],
            ],
            // empty values
            'in with empty values' => [
                ['in', 'id', []],
                <<<SQL
                0=1
                SQL,
                [],
            ],
            'not in with empty values' => [
                ['not in', 'id', []],
                '',
                [],
            ],
            // generator as column (non-rewindable Traversable)
            'composite in with generator columns' => [
                new InCondition(
                    self::generatorFrom(['id', 'name']),
                    'in',
                    [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']],
                ),
                <<<SQL
                (([[id]] = :qp0 AND [[name]] = :qp1) OR ([[id]] = :qp2 AND [[name]] = :qp3))
                SQL,
                [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar'],
            ],
            'in with generator values' => [
                new InCondition('id', 'in', self::generatorFrom([1, 2, 3])),
                <<<SQL
                [[id]] IN (:qp0, :qp1, :qp2)
                SQL,
                [':qp0' => 1, ':qp1' => 2, ':qp2' => 3],
            ],
            'in with single generator column' => [
                new InCondition(self::generatorFrom(['id']), 'in', [1, 2]),
                <<<SQL
                [[id]] IN (:qp0, :qp1)
                SQL,
                [':qp0' => 1, ':qp1' => 2],
            ],
            // in using array objects
            'hash condition with traversable' => [
                ['id' => new TraversableObject([1, 2])],
                <<<SQL
                [[id]] IN (:qp0, :qp1)
                SQL,
                [':qp0' => 1, ':qp1' => 2],
            ],
            'in with traversable' => [
                ['in', 'id', new TraversableObject([1, 2, 3])],
                <<<SQL
                [[id]] IN (:qp0, :qp1, :qp2)
                SQL,
                [':qp0' => 1, ':qp1' => 2, ':qp2' => 3],
            ],
            // in with null handling
            'in with traversable containing null' => [
                ['in', 'id', new TraversableObject([1, null])],
                <<<SQL
                [[id]]=:qp0 OR [[id]] IS NULL
                SQL,
                [':qp0' => 1],
            ],
            'in with traversable containing multiple and null' => [
                ['in', 'id', new TraversableObject([1, 2, null])],
                <<<SQL
                [[id]] IN (:qp0, :qp1) OR [[id]] IS NULL
                SQL,
                [':qp0' => 1, ':qp1' => 2],
            ],
            // in with only null
            'in with only null traversable' => [
                ['in', 'id', new TraversableObject([null])],
                <<<SQL
                [[id]] IS NULL
                SQL,
                [],
            ],
            'not in expression with only null traversable' => [
                ['not in', new Expression('id'), new TraversableObject([null])],
                <<<SQL
                [[id]] IS NOT NULL
                SQL,
                [],
            ],
            'not in with only null traversable' => [
                ['not in', 'id', new TraversableObject([null])],
                <<<SQL
                [[id]] IS NOT NULL
                SQL,
                [],
            ],
            // InCondition objects
            'InCondition in with expression column' => [
                new InCondition(new Expression('id'), 'in', 1),
                <<<SQL
                [[id]]=:qp0
                SQL,
                [':qp0' => 1],
            ],
            'InCondition in with multiple values' => [
                new InCondition('id', 'in', [1, 2]),
                <<<SQL
                [[id]] IN (:qp0, :qp1)
                SQL,
                [':qp0' => 1, ':qp1' => 2],
            ],
            'InCondition in with scalar' => [
                new InCondition('id', 'in', 1),
                <<<SQL
                [[id]]=:qp0
                SQL,
                [':qp0' => 1],
            ],
            'InCondition in with single-element array' => [
                new InCondition('id', 'in', [1]),
                <<<SQL
                [[id]]=:qp0
                SQL,
                [':qp0' => 1],
            ],
            'InCondition not in with multiple values' => [
                new InCondition('id', 'not in', [1, 2]),
                <<<SQL
                [[id]] NOT IN (:qp0, :qp1)
                SQL,
                [':qp0' => 1, ':qp1' => 2],
            ],
            'InCondition not in with scalar' => [
                new InCondition('id', 'not in', 1),
                <<<SQL
                [[id]]<>:qp0
                SQL,
                [':qp0' => 1],
            ],
            'InCondition not in with single-element array' => [
                new InCondition('id', 'not in', [1]),
                <<<SQL
                [[id]]<>:qp0
                SQL,
                [':qp0' => 1],
            ],
            // not in with null handling
            'not in with traversable containing multiple and null' => [
                ['not in', 'id', new TraversableObject([1, 2, null])],
                <<<SQL
                [[id]] NOT IN (:qp0, :qp1) AND [[id]] IS NOT NULL
                SQL,
                [':qp0' => 1, ':qp1' => 2],
            ],
            'not in with traversable containing null' => [
                ['not in', 'id', new TraversableObject([1, null])],
                <<<SQL
                [[id]]<>:qp0 AND [[id]] IS NOT NULL
                SQL,
                [':qp0' => 1],
            ],
            // simple in
            'in with single scalar' => [
                ['in', 'id', 1],
                <<<SQL
                [[id]]=:qp0
                SQL,
                [':qp0' => 1],
            ],
            'in with single-element array' => [
                ['in', 'id', [1]],
                <<<SQL
                [[id]]=:qp0
                SQL,
                [':qp0' => 1],
            ],
            'in with single-element traversable' => [
                ['in', 'id', new TraversableObject([1])],
                <<<SQL
                [[id]]=:qp0
                SQL,
                [':qp0' => 1],
            ],
            'in with subquery in values' => [
                ['in', 'id', [1, 2, (new Query())->select('three')->from('digits')]],
                <<<SQL
                [[id]] IN (:qp0, :qp1, (SELECT [[three]] FROM [[digits]]))
                SQL,
                [':qp0' => 1, ':qp1' => 2],
            ],
            'in with subquery' => [
                ['in', 'id', (new Query())->select('id')->from('users')->where(['active' => 1])],
                <<<SQL
                [[id]] IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)
                SQL,
                [':qp0' => 1],
            ],
            'not in with array' => [
                ['not in', 'id', [1, 2, 3]],
                <<<SQL
                [[id]] NOT IN (:qp0, :qp1, :qp2)
                SQL,
                [':qp0' => 1, ':qp1' => 2, ':qp2' => 3],
            ],
            'not in with subquery' => [
                ['not in', 'id', (new Query())->select('id')->from('users')->where(['active' => 1])],
                <<<SQL
                [[id]] NOT IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)
                SQL,
                [':qp0' => 1],
            ],
            // subquery with Expression column (non-array)
            'in with subquery and expression column' => [
                new InCondition(
                    new Expression('id'),
                    'in',
                    (new Query())->select('id')->from('users')->where(['active' => 1]),
                ),
                <<<SQL
                [[id]] IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)
                SQL,
                [':qp0' => 1],
            ],
        ];
    }

    /**
     * Creates a generator from the given array (non-rewindable Traversable).
     */
    private static function generatorFrom(array $items): Generator
    {
        yield from $items;
    }
}
