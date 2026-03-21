<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db\conditions\providers;

use yii\db\Expression;

/**
 * Base data provider for hash condition builder test cases.
 *
 * Provides representative input/output pairs for the hash condition builder (column-value pair conditions).
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class HashConditionBuilderProvider
{
    /**
     * @phpstan-return array<string, array{mixed[], string, mixed[]}>
     */
    public static function buildCondition(): array
    {
        return [
            'expression value' => [
                ['a' => new Expression('CONCAT(col1, col2)'), 'b' => 2],
                <<<SQL
                ([[a]]=CONCAT(col1, col2)) AND ([[b]]=:qp0)
                SQL,
                [':qp0' => 2],
            ],
            'scalar values' => [
                ['a' => 1, 'b' => 2],
                <<<SQL
                ([[a]]=:qp0) AND ([[b]]=:qp1)
                SQL,
                [':qp0' => 1, ':qp1' => 2],
            ],
        ];
    }
}
