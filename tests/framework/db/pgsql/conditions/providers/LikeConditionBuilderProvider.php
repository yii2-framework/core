<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql\conditions\providers;

/**
 * Data provider for {@see \yiiunit\framework\db\pgsql\conditions\LikeConditionBuilderTest} test cases.
 *
 * Provides PostgreSQL-specific input/output pairs for the LIKE/ILIKE condition builder.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class LikeConditionBuilderProvider extends \yiiunit\base\db\conditions\providers\LikeConditionBuilderProvider
{
    public static function buildCondition(): array
    {
        return array_merge(
            parent::buildCondition(),
            [
                // empty ilike values
                'ilike with empty array' => [
                    ['ilike', 'name', []],
                    <<<SQL
                    0=1
                    SQL,
                    [],
                ],
                'not ilike with empty array' => [
                    ['not ilike', 'name', []],
                    '',
                    [],
                ],
                'or ilike with empty array' => [
                    ['or ilike', 'name', []],
                    <<<SQL
                    0=1
                    SQL,
                    [],
                ],
                'or not ilike with empty array' => [
                    ['or not ilike', 'name', []],
                    '',
                    [],
                ],

                // ilike for many values
                'ilike many' => [
                    ['ilike', 'name', ['heyho', 'abc']],
                    <<<SQL
                    [[name]] ILIKE :qp0 AND [[name]] ILIKE :qp1
                    SQL,
                    [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],
                'not ilike many' => [
                    ['not ilike', 'name', ['heyho', 'abc']],
                    <<<SQL
                    [[name]] NOT ILIKE :qp0 AND [[name]] NOT ILIKE :qp1
                    SQL,
                    [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],
                'or ilike many' => [
                    ['or ilike', 'name', ['heyho', 'abc']],
                    <<<SQL
                    [[name]] ILIKE :qp0 OR [[name]] ILIKE :qp1
                    SQL,
                    [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],
                'or not ilike many' => [
                    ['or not ilike', 'name', ['heyho', 'abc']],
                    <<<SQL
                    [[name]] NOT ILIKE :qp0 OR [[name]] NOT ILIKE :qp1
                    SQL,
                    [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],

                // simple ilike
                'ilike' => [
                    ['ilike', 'name', 'heyho'],
                    <<<SQL
                    [[name]] ILIKE :qp0
                    SQL,
                    [':qp0' => '%heyho%'],
                ],
                'not ilike' => [
                    ['not ilike', 'name', 'heyho'],
                    <<<SQL
                    [[name]] NOT ILIKE :qp0
                    SQL,
                    [':qp0' => '%heyho%'],
                ],
                'or ilike' => [
                    ['or ilike', 'name', 'heyho'],
                    <<<SQL
                    [[name]] ILIKE :qp0
                    SQL,
                    [':qp0' => '%heyho%'],
                ],
                'or not ilike' => [
                    ['or not ilike', 'name', 'heyho'],
                    <<<SQL
                    [[name]] NOT ILIKE :qp0
                    SQL,
                    [':qp0' => '%heyho%'],
                ],
            ],
        );
    }
}
