<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci\conditions\providers;

use yii\db\conditions\InCondition;
use yii\db\Expression;
use yii\db\Query;
use yiiunit\data\base\TraversableObject;

/**
 * Data provider for {@see \yiiunit\framework\db\oci\conditions\InConditionBuilderTest} test cases.
 *
 * Provides Oracle-specific input/output pairs for the IN/NOT IN condition builder.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class InConditionBuilderProvider extends \yiiunit\base\db\conditions\providers\InConditionBuilderProvider
{
    public static function buildCondition(): array
    {
        return [
            ...parent::buildCondition(),
            'composite in with subquery' => [
                ['in', ['id', 'name'], (new Query())->select(['id', 'name'])->from('users')->where(['active' => 1])],
                <<<SQL
                ([[id]], [[name]]) IN (SELECT [[id]], [[name]] FROM [[users]] WHERE [[active]]=:qp0)
                SQL,
                [':qp0' => 1],
            ],
            'composite in with subquery and expression column' => [
                new InCondition(
                    [new Expression('id')],
                    'in',
                    (new Query())->select('id')->from('users')->where(['active' => 1]),
                ),
                <<<SQL
                ([[id]]) IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)
                SQL,
                [':qp0' => 1],
            ],
            'composite not in with subquery' => [
                [
                    'not in',
                    ['id', 'name'],
                    (new Query())->select(['id', 'name'])->from('users')->where(['active' => 1]),
                ],
                <<<SQL
                ([[id]], [[name]]) NOT IN (SELECT [[id]], [[name]] FROM [[users]] WHERE [[active]]=:qp0)
                SQL,
                [':qp0' => 1],
            ],
            // Oracle split condition (>1000 values)
            'in with more than 1000 values' => [
                ['in', 'id', range(0, 2500)],
                '([[id]] IN (' . implode(', ', array_map(static fn ($i) => ":qp$i", range(0, 999))) . '))'
                . ' OR ([[id]] IN (' . implode(', ', array_map(static fn ($i) => ":qp$i", range(1000, 1999))) . '))'
                . ' OR ([[id]] IN (' . implode(', ', array_map(static fn ($i) => ":qp$i", range(2000, 2500))) . '))',
                array_combine(
                    array_map(static fn ($i) => ":qp$i", range(0, 2500)),
                    range(0, 2500),
                ),
            ],
            'not in with more than 1000 values' => [
                ['not in', 'id', range(0, 2500)],
                '([[id]] NOT IN (' . implode(', ', array_map(static fn ($i) => ":qp$i", range(0, 999))) . '))'
                . ' AND ([[id]] NOT IN (' . implode(', ', array_map(static fn ($i) => ":qp$i", range(1000, 1999))) . '))'
                . ' AND ([[id]] NOT IN (' . implode(', ', array_map(static fn ($i) => ":qp$i", range(2000, 2500))) . '))',
                array_combine(
                    array_map(static fn ($i) => ":qp$i", range(0, 2500)),
                    range(0, 2500),
                ),
            ],
            'not in with more than 1000 values traversable' => [
                ['not in', 'id', new TraversableObject(range(0, 2500))],
                '([[id]] NOT IN (' . implode(', ', array_map(static fn ($i) => ":qp$i", range(0, 999))) . '))'
                . ' AND ([[id]] NOT IN (' . implode(', ', array_map(static fn ($i) => ":qp$i", range(1000, 1999))) . '))'
                . ' AND ([[id]] NOT IN (' . implode(', ', array_map(static fn ($i) => ":qp$i", range(2000, 2500))) . '))',
                array_combine(
                    array_map(static fn ($i) => ":qp$i", range(0, 2500)),
                    range(0, 2500),
                ),
            ],
        ];
    }
}
