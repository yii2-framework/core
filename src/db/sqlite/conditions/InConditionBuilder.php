<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\sqlite\conditions;

use yii\base\NotSupportedException;
use yii\db\ExpressionInterface;
use yii\db\Query;

use function is_array;

/**
 * Builds raw SQL from {@see \yii\db\conditions\InCondition} expression objects for SQLite.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 * @since 2.0.14
 */
class InConditionBuilder extends \yii\db\conditions\InConditionBuilder
{
    /**
     * {@inheritdoc}
     *
     * @throws NotSupportedException if `$columns` is an array.
     */
    protected function buildSubqueryInCondition(
        string $operator,
        array|string|ExpressionInterface $columns,
        Query $values,
        array &$params,
    ): string {
        if (is_array($columns)) {
            throw new NotSupportedException(__METHOD__ . ' is not supported by SQLite.');
        }

        return parent::buildSubqueryInCondition($operator, $columns, $values, $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function getNotEqualOperator(): string
    {
        return '!=';
    }
}
