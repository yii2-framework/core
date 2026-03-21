<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db\conditions\providers;

use yii\db\Query;

/**
 * Base data provider for EXISTS/NOT EXISTS condition builder test cases.
 *
 * Provides representative input/output pairs for the EXISTS condition builder, including simple subqueries,
 * subqueries with bound parameters, and subqueries with array parameters.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class ExistsConditionBuilderProvider
{
    /**
     * @phpstan-return array<string, array{mixed[], string, mixed[]}>
     */
    public static function buildCondition(): array
    {
        return [
            'exists with subquery' => [
                ['exists', (new Query())->select('id')->from('users')->where(['active' => 1])],
                <<<SQL
                EXISTS (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)
                SQL,
                [':qp0' => 1],
            ],
            'not exists with subquery' => [
                ['not exists', (new Query())->select('id')->from('users')->where(['active' => 1])],
                <<<SQL
                NOT EXISTS (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)
                SQL,
                [':qp0' => 1],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function existsWithFullQuery(): array
    {
        return [
            'exists' => [
                'exists',
                <<<SQL
                SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE EXISTS (SELECT [[1]] FROM [[Website]] [[w]])
                SQL,
            ],
            'not exists' => [
                'not exists',
                <<<SQL
                SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE NOT EXISTS (SELECT [[1]] FROM [[Website]] [[w]])
                SQL,
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, mixed[]}>
     */
    public static function existsWithParameters(): array
    {
        return [
            'exists with bound parameters' => [
                <<<SQL
                SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]] WHERE (w.id = t.website_id) AND (w.merchant_id = :merchant_id))) AND (t.some_column = :some_value)
                SQL,
                [':some_value' => 'asd', ':merchant_id' => 6],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, mixed[]}>
     */
    public static function existsWithArrayParameters(): array
    {
        return [
            'exists with array parameters' => [
                <<<SQL
                SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]] WHERE (w.id = t.website_id) AND (([[w]].[[merchant_id]]=:qp0) AND ([[w]].[[user_id]]=:qp1)))) AND ([[t]].[[some_column]]=:qp2)
                SQL,
                [':qp0' => 6, ':qp1' => '210', ':qp2' => 'asd'],
            ],
        ];
    }
}
