<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\db\oci\conditions;

use yii\db\conditions\InCondition;
use yii\db\ExpressionInterface;

use function count;
use function is_array;

/**
 * Builds raw SQL from {@see InCondition} expression objects for Oracle.
 *
 * Splits long `IN` conditions into chunks of 1000 parameters to comply with Oracle limitations.
 */
class InConditionBuilder extends \yii\db\conditions\InConditionBuilder
{
    /**
     * {@inheritdoc}
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $splitCondition = $this->splitCondition($expression, $params);
        if ($splitCondition !== null) {
            return $splitCondition;
        }

        return parent::build($expression, $params);
    }

    /**
     * Splits long `IN` conditions into series of smaller ones when the value count exceeds 1000.
     *
     * Oracle DBMS does not support more than 1000 parameters in an `IN` condition.
     *
     * @param InCondition $condition the condition to evaluate.
     * @param array $params the binding parameters.
     *
     * @return string|null `null` when split is not required, otherwise the built SQL condition.
     */
    protected function splitCondition(InCondition $condition, array &$params): ?string
    {
        $operator = $condition->getOperator();
        $values = $condition->getValues();
        $column = $condition->getColumn();

        if (!is_array($values)) {
            return null;
        }

        $maxParameters = 1000;
        $count = count($values);
        if ($count <= $maxParameters) {
            return null;
        }

        $slices = [];
        for ($i = 0; $i < $count; $i += $maxParameters) {
            $slices[] = $this->queryBuilder->createConditionFromArray(
                [$operator, $column, array_slice($values, $i, $maxParameters)],
            );
        }
        array_unshift($slices, ($operator === 'IN') ? 'OR' : 'AND');

        return $this->queryBuilder->buildCondition($slices, $params);
    }
}
