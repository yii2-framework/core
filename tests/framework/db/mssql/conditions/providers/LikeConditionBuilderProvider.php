<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql\conditions\providers;

/**
 * Data provider for {@see \yiiunit\framework\db\mssql\conditions\LikeConditionBuilderTest} test cases.
 *
 * Provides MSSQL-specific input/output pairs for the LIKE condition builder.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class LikeConditionBuilderProvider extends \yiiunit\base\db\conditions\providers\LikeConditionBuilderProvider
{
    public static function buildCondition(): array
    {
        return self::applyLikeEscape(
            parent::buildCondition(),
            '',
            [
                '\%' => '[%]',
                '\_' => '[_]',
                '[' => '[[]',
                ']' => '[]]',
                '\\\\' => '[\\]',
            ],
        );
    }
}
