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
 * Base data provider for NOT condition builder test cases.
 *
 * Provides representative input/output pairs for the NOT condition builder, including string conditions, subqueries,
 * and Expression operands with parameters.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class NotConditionBuilderProvider
{
    /**
     * @phpstan-return array<string, array{mixed[]|object, string, mixed[]}>
     */
    public static function buildCondition(): array
    {
        return [
            'not empty string returns empty' => [
                ['not', ''],
                '',
                [],
            ],
            'not expression with params' => [
                ['not', new Expression('any_expression(:a)', [':a' => 1])],
                <<<SQL
                NOT (any_expression(:a))
                SQL,
                [':a' => 1],
            ],
            'not string' => [
                ['not', 'name'],
                <<<SQL
                NOT (name)
                SQL,
                [],
            ],
            'not subquery' => [
                ['not', (new Query())->select('exists')->from('some_table')],
                <<<SQL
                NOT ((SELECT [[exists]] FROM [[some_table]]))
                SQL,
                [],
            ],
        ];
    }
}
