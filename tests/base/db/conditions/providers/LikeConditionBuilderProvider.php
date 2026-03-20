<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db\conditions\providers;

use yii\db\conditions\LikeCondition;
use yii\db\Expression;

/**
 * Base data provider for LIKE condition builder test cases.
 *
 * Provides representative input/output pairs for the LIKE/NOT LIKE condition builder. Driver-specific providers extend
 * this class to add or override test cases.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class LikeConditionBuilderProvider
{
    public static function buildCondition(): array
    {
        return [
            // empty values
            'like with empty array' => [
                ['like', 'name', []],
                <<<SQL
                0=1
                SQL,
                [],
            ],
            'not like with empty array' => [
                ['not like', 'name', []],
                '',
                [],
            ],
            'or like with empty array' => [
                ['or like', 'name', []],
                <<<SQL
                0=1
                SQL,
                [],
            ],
            'or not like with empty array' => [
                ['or not like', 'name', []],
                '',
                [],
            ],

            // like for many values
            'like many' => [
                ['like', 'name', ['foo%', '[abc]']],
                <<<SQL
                [[name]] LIKE :qp0 AND [[name]] LIKE :qp1
                SQL,
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%'],
            ],
            'not like many' => [
                ['not like', 'name', ['foo%', '[abc]']],
                <<<SQL
                [[name]] NOT LIKE :qp0 AND [[name]] NOT LIKE :qp1
                SQL,
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%'],
            ],
            'or like many' => [
                ['or like', 'name', ['foo%', '[abc]']],
                <<<SQL
                [[name]] LIKE :qp0 OR [[name]] LIKE :qp1
                SQL,
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%'],
            ],
            'or not like many' => [
                ['or not like', 'name', ['foo%', '[abc]']],
                <<<SQL
                [[name]] NOT LIKE :qp0 OR [[name]] NOT LIKE :qp1
                SQL,
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%'],
            ],

            // like object conditions
            'LikeCondition like expression' => [
                new LikeCondition('name', 'like', new Expression('CONCAT("test", name, "%")')),
                <<<SQL
                [[name]] LIKE CONCAT("test", name, "%")
                SQL,
                [],
            ],
            'LikeCondition like mixed' => [
                new LikeCondition('name', 'like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                <<<SQL
                [[name]] LIKE CONCAT("test", name, "%") AND [[name]] LIKE :qp0
                SQL,
                [':qp0' => '%\\\ab\_c%'],
            ],
            'LikeCondition not like expression' => [
                new LikeCondition('name', 'not like', new Expression('CONCAT("test", name, "%")')),
                <<<SQL
                [[name]] NOT LIKE CONCAT("test", name, "%")
                SQL,
                [],
            ],
            'LikeCondition not like mixed' => [
                new LikeCondition('name', 'not like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                <<<SQL
                [[name]] NOT LIKE CONCAT("test", name, "%") AND [[name]] NOT LIKE :qp0
                SQL,
                [':qp0' => '%\\\ab\_c%'],
            ],
            'LikeCondition or like expression' => [
                new LikeCondition('name', 'or like', new Expression('CONCAT("test", name, "%")')),
                <<<SQL
                [[name]] LIKE CONCAT("test", name, "%")
                SQL,
                [],
            ],
            'LikeCondition or like mixed' => [
                new LikeCondition('name', 'or like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                <<<SQL
                [[name]] LIKE CONCAT("test", name, "%") OR [[name]] LIKE :qp0
                SQL,
                [':qp0' => '%\\\ab\_c%'],
            ],
            'LikeCondition or not like expression' => [
                new LikeCondition('name', 'or not like', new Expression('CONCAT("test", name, "%")')),
                <<<SQL
                [[name]] NOT LIKE CONCAT("test", name, "%")
                SQL,
                [],
            ],
            'LikeCondition or not like mixed' => [
                new LikeCondition('name', 'or not like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                <<<SQL
                [[name]] NOT LIKE CONCAT("test", name, "%") OR [[name]] NOT LIKE :qp0
                SQL,
                [':qp0' => '%\\\ab\_c%'],
            ],

            // like with Expression
            'like expression' => [
                ['like', 'name', new Expression('CONCAT("test", name, "%")')],
                <<<SQL
                [[name]] LIKE CONCAT("test", name, "%")
                SQL,
                [],
            ],
            'like mixed expression and string' => [
                ['like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                <<<SQL
                [[name]] LIKE CONCAT("test", name, "%") AND [[name]] LIKE :qp0
                SQL,
                [':qp0' => '%\\\ab\_c%'],
            ],
            // @see https://github.com/yiisoft/yii2/issues/15630
            'like without escape (issue #15630)' => [
                ['like', 'location.title_ru', 'vi%', false],
                <<<SQL
                [[location]].[[title_ru]] LIKE :qp0
                SQL,
                [':qp0' => 'vi%'],
            ],
            'not like expression' => [
                ['not like', 'name', new Expression('CONCAT("test", name, "%")')],
                <<<SQL
                [[name]] NOT LIKE CONCAT("test", name, "%")
                SQL,
                [],
            ],
            'not like mixed expression and string' => [
                ['not like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                <<<SQL
                [[name]] NOT LIKE CONCAT("test", name, "%") AND [[name]] NOT LIKE :qp0
                SQL,
                [':qp0' => '%\\\ab\_c%'],
            ],
            'or like expression' => [
                ['or like', 'name', new Expression('CONCAT("test", name, "%")')],
                <<<SQL
                [[name]] LIKE CONCAT("test", name, "%")
                SQL,
                [],
            ],
            'or like mixed expression and string' => [
                ['or like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                <<<SQL
                [[name]] LIKE CONCAT("test", name, "%") OR [[name]] LIKE :qp0
                SQL,
                [':qp0' => '%\\\ab\_c%'],
            ],
            'or not like expression' => [
                ['or not like', 'name', new Expression('CONCAT("test", name, "%")')],
                <<<SQL
                [[name]] NOT LIKE CONCAT("test", name, "%")
                SQL,
                [],
            ],
            'or not like mixed expression and string' => [
                ['or not like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                <<<SQL
                [[name]] NOT LIKE CONCAT("test", name, "%") OR [[name]] NOT LIKE :qp0
                SQL,
                [':qp0' => '%\\\ab\_c%'],
            ],

            // like with expression as columnName
            'like with expression column' => [
                ['like', new Expression('name'), 'teststring'],
                <<<SQL
                name LIKE :qp0
                SQL,
                [':qp0' => '%teststring%'],
            ],

            // simple like
            'like' => [
                ['like', 'name', 'foo%'],
                <<<SQL
                [[name]] LIKE :qp0
                SQL,
                [':qp0' => '%foo\%%'],
            ],
            'not like' => [
                ['not like', 'name', 'foo%'],
                <<<SQL
                [[name]] NOT LIKE :qp0
                SQL,
                [':qp0' => '%foo\%%'],
            ],
            'or like' => [
                ['or like', 'name', 'foo%'],
                <<<SQL
                [[name]] LIKE :qp0
                SQL,
                [':qp0' => '%foo\%%'],
            ],
            'or not like' => [
                ['or not like', 'name', 'foo%'],
                <<<SQL
                [[name]] NOT LIKE :qp0
                SQL,
                [':qp0' => '%foo\%%'],
            ],
        ];
    }

    /**
     * Applies driver-specific LIKE escape transformations to test cases.
     *
     * @param array $cases test cases from `buildCondition()`
     * @param string $escapeSql SQL fragment appended after each LIKE clause (e.g., ` ESCAPE '\\'`)
     * @param array $paramReplacements map of escape character replacements for parameter values
     */
    protected static function applyLikeEscape(array $cases, string $escapeSql, array $paramReplacements): array
    {
        foreach ($cases as &$case) {
            if ($escapeSql !== '') {
                preg_match_all(
                    '/(?P<condition>LIKE.+?)( AND| OR|$)/',
                    $case[1],
                    $matches,
                    PREG_SET_ORDER,
                );

                foreach ($matches as $match) {
                    $case[1] = str_replace($match['condition'], $match['condition'] . $escapeSql, $case[1]);
                }
            }

            if ($paramReplacements !== []) {
                foreach ($case[2] as $name => $value) {
                    if (is_string($value)) {
                        $case[2][$name] = strtr($value, $paramReplacements);
                    }
                }
            }
        }

        return $cases;
    }
}
