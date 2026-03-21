<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db\conditions\providers;

use yii\db\conditions\BetweenColumnsCondition;
use yii\db\Expression;
use yii\db\Query;

/**
 * Base data provider for BETWEEN/NOT BETWEEN condition builder test cases.
 *
 * Provides representative input/output pairs for the BETWEEN and BETWEEN COLUMNS condition builders. Driver-specific
 * providers extend this class to add or override test cases.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class BetweenConditionBuilderProvider
{
    /**
     * @phpstan-return array<string, array{mixed[]|object, string, mixed[]}>
     */
    public static function buildCondition(): array
    {
        return [
            // between
            'between with expressions' => [
                ['between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), new Expression('NOW()')],
                <<<SQL
                [[date]] BETWEEN (NOW() - INTERVAL 1 MONTH) AND NOW()
                SQL,
                [],
            ],
            'between with expression and scalar' => [
                ['between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), 123],
                <<<SQL
                [[date]] BETWEEN (NOW() - INTERVAL 1 MONTH) AND :qp0
                SQL,
                [':qp0' => 123],
            ],
            'between with scalars' => [
                ['between', 'id', 1, 10],
                <<<SQL
                [[id]] BETWEEN :qp0 AND :qp1
                SQL,
                [':qp0' => 1, ':qp1' => 10],
            ],
            'not between with expressions' => [
                ['not between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), new Expression('NOW()')],
                <<<SQL
                [[date]] NOT BETWEEN (NOW() - INTERVAL 1 MONTH) AND NOW()
                SQL,
                [],
            ],
            'not between with expression and scalar' => [
                ['not between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), 123],
                <<<SQL
                [[date]] NOT BETWEEN (NOW() - INTERVAL 1 MONTH) AND :qp0
                SQL,
                [':qp0' => 123],
            ],
            'not between with scalars' => [
                ['not between', 'id', 1, 10],
                <<<SQL
                [[id]] NOT BETWEEN :qp0 AND :qp1
                SQL,
                [':qp0' => 1, ':qp1' => 10],
            ],

            // between columns
            'between columns with expression value' => [
                new BetweenColumnsCondition(new Expression('NOW()'), 'BETWEEN', 'create_time', 'update_time'),
                <<<SQL
                NOW() BETWEEN [[create_time]] AND [[update_time]]
                SQL,
                [],
            ],
            'between columns with scalar value' => [
                new BetweenColumnsCondition('2018-02-11', 'BETWEEN', 'create_time', 'update_time'),
                <<<SQL
                :qp0 BETWEEN [[create_time]] AND [[update_time]]
                SQL,
                [':qp0' => '2018-02-11'],
            ],
            'not between columns with expression value' => [
                new BetweenColumnsCondition(new Expression('NOW()'), 'NOT BETWEEN', 'create_time', 'update_time'),
                <<<SQL
                NOW() NOT BETWEEN [[create_time]] AND [[update_time]]
                SQL,
                [],
            ],
            'not between columns with unquoted column' => [
                new BetweenColumnsCondition('2018-02-11', 'NOT BETWEEN', 'NOW()', 'update_time'),
                <<<SQL
                :qp0 NOT BETWEEN NOW() AND [[update_time]]
                SQL,
                [':qp0' => '2018-02-11'],
            ],
            'not between columns with subquery' => [
                new BetweenColumnsCondition(
                    new Expression('NOW()'),
                    'NOT BETWEEN',
                    (new Query())->select('min_date')->from('some_table'),
                    'max_date',
                ),
                <<<SQL
                NOW() NOT BETWEEN (SELECT [[min_date]] FROM [[some_table]]) AND [[max_date]]
                SQL,
                [],
            ],
        ];
    }
}
