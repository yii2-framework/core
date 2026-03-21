<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db\conditions\providers;

use yii\db\Expression;
use yii\db\Query;

/**
 * Base data provider for conjunction (AND/OR) condition builder test cases.
 *
 * Provides representative input/output pairs for the conjunction condition builder, covering basic AND/OR, nested
 * conditions, Expression operands, and subquery operands.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class ConjunctionConditionBuilderProvider
{
    /**
     * @phpstan-return array<string, array{mixed[], string, mixed[]}>
     */
    public static function buildCondition(): array
    {
        return [
            // and
            'and basic' => [
                ['and', 'id=1', 'id=2'],
                <<<SQL
                (id=1) AND (id=2)
                SQL,
                [],
            ],
            'and with expression' => [
                ['and', 'id=1', new Expression('id=:qp0', [':qp0' => 2])],
                <<<SQL
                (id=1) AND (id=:qp0)
                SQL,
                [':qp0' => 2],
            ],
            'and with hash and subquery' => [
                ['and', ['expired' => false], (new Query())->select('count(*) > 1')->from('queue')],
                <<<SQL
                ([[expired]]=:qp0) AND ((SELECT count(*) > 1 FROM [[queue]]))
                SQL,
                [':qp0' => false],
            ],
            'and with nested or' => [
                ['and', 'type=1', ['or', 'id=1', 'id=2']],
                <<<SQL
                (type=1) AND ((id=1) OR (id=2))
                SQL,
                [],
            ],

            // empty and single expression
            'and empty returns empty' => [
                ['and', '', ''],
                '',
                [],
            ],
            'and single expression returns unwrapped' => [
                ['and', 'id=1', ''],
                'id=1',
                [],
            ],
            'or empty returns empty' => [
                ['or', '', ''],
                '',
                [],
            ],
            'or single expression returns unwrapped' => [
                ['or', '', 'id=1'],
                'id=1',
                [],
            ],

            // or
            'or basic' => [
                ['or', 'id=1', 'id=2'],
                <<<SQL
                (id=1) OR (id=2)
                SQL,
                [],
            ],
            'or with expression' => [
                ['or', 'type=1', new Expression('id=:qp0', [':qp0' => 1])],
                <<<SQL
                (type=1) OR (id=:qp0)
                SQL,
                [':qp0' => 1],
            ],
            'or with hash and subquery' => [
                ['or', ['expired' => false], (new Query())->select('count(*) > 1')->from('queue')],
                <<<SQL
                ([[expired]]=:qp0) OR ((SELECT count(*) > 1 FROM [[queue]]))
                SQL,
                [':qp0' => false],
            ],
            'or with nested or' => [
                ['or', 'type=1', ['or', 'id=1', 'id=2']],
                <<<SQL
                (type=1) OR ((id=1) OR (id=2))
                SQL,
                [],
            ],
        ];
    }
}
