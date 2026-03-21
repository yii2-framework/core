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
 * Base data provider for simple condition builder test cases.
 *
 * Provides representative input/output pairs for the simple condition builder (`=`, `>`, `>=`, `<`, `<=`, `<>`, `!=`)
 * including Expression operands and subquery operands.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class SimpleConditionBuilderProvider
{
    /**
     * @phpstan-return array<string, array{mixed[]|object, string, mixed[]}>
     */
    public static function buildCondition(): array
    {
        return [
            'equal' => [
                ['=', 'a', 'b'],
                <<<SQL
                [[a]] = :qp0
                SQL,
                [':qp0' => 'b'],
            ],
            'expression column' => [
                ['=', new Expression('date'), '2019-08-01'],
                <<<SQL
                date = :qp0
                SQL,
                [':qp0' => '2019-08-01'],
            ],
            'expression value with params' => [
                ['>=', 'date', new Expression('DATE_SUB(NOW(), INTERVAL :month MONTH)', [':month' => 2])],
                <<<SQL
                [[date]] >= DATE_SUB(NOW(), INTERVAL :month MONTH)
                SQL,
                [':month' => 2],
            ],
            'expression value without params' => [
                ['>=', 'date', new Expression('DATE_SUB(NOW(), INTERVAL 1 MONTH)')],
                <<<SQL
                [[date]] >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                SQL,
                [],
            ],
            'greater or equal' => [
                ['>=', 'a', 'b'],
                <<<SQL
                [[a]] >= :qp0
                SQL,
                [':qp0' => 'b'],
            ],
            'greater than' => [
                ['>', 'a', 1],
                <<<SQL
                [[a]] > :qp0
                SQL,
                [':qp0' => 1],
            ],
            'less or equal' => [
                ['<=', 'a', 'b'],
                <<<SQL
                [[a]] <= :qp0
                SQL,
                [':qp0' => 'b'],
            ],
            'less than' => [
                ['<', 'a', 2],
                <<<SQL
                [[a]] < :qp0
                SQL,
                [':qp0' => 2],
            ],
            'not equal (!=)' => [
                ['!=', 'a', 'b'],
                <<<SQL
                [[a]] != :qp0
                SQL,
                [':qp0' => 'b'],
            ],
            'not equal (<>)' => [
                ['<>', 'a', 3],
                <<<SQL
                [[a]] <> :qp0
                SQL,
                [':qp0' => 3],
            ],
            'null value' => [
                ['=', 'a', null],
                <<<SQL
                [[a]] = NULL
                SQL,
                [],
            ],
            'subquery column' => [
                ['=', (new Query())->select('COUNT(*)')->from('test')->where(['id' => 6]), 0],
                <<<SQL
                (SELECT COUNT(*) FROM [[test]] WHERE [[id]]=:qp0) = :qp1
                SQL,
                [':qp0' => 6, ':qp1' => 0],
            ],
            'subquery value' => [
                ['=', 'date', (new Query())->select('max(date)')->from('test')->where(['id' => 5])],
                <<<SQL
                [[date]] = (SELECT max(date) FROM [[test]] WHERE [[id]]=:qp0)
                SQL,
                [':qp0' => 5],
            ],
        ];
    }
}
