<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\conditions;

use ArrayAccess;
use yii\db\Expression;
use yii\db\ExpressionBuilderInterface;
use yii\db\ExpressionBuilderTrait;
use yii\db\ExpressionInterface;
use yii\db\Query;

use function count;
use function in_array;
use function is_array;

/**
 * Builds raw SQL from {@see InCondition} expression objects.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 * @since 2.0.14
 */
class InConditionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * Builds the raw SQL from the expression that will not be additionally escaped or quoted.
     *
     * @param ExpressionInterface|InCondition $expression the expression to be built.
     * @param array $params the binding parameters.
     *
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = strtoupper($expression->getOperator());
        $column = $expression->getColumn();
        $values = $expression->getValues();

        if ($column === []) {
            // no columns to test against
            return $operator === 'IN' ? '0=1' : '';
        }

        if ($values instanceof Query) {
            return $this->buildSubqueryInCondition(
                $operator,
                $column,
                $values,
                $params,
            );
        }

        if (!is_array($values)) {
            // ensure values is an array
            $values = (array) $values;
        }

        if (is_array($column)) {
            if (count($column) > 1) {
                return $this->buildCompositeInCondition(
                    $operator,
                    $column,
                    $values,
                    $params,
                );
            }

            $column = reset($column);
        }

        if ($column instanceof Expression) {
            $column = $column->expression;
        }

        $rawValues = $values;

        $nullCondition = null;
        $nullConditionOperator = null;

        if (isset($rawValues) && in_array(null, $rawValues, true)) {
            $nullCondition = $this->getNullCondition($operator, $column);

            $nullConditionOperator = $operator === 'IN' ? 'OR' : 'AND';
        }

        $sqlValues = $this->buildValues($expression, $values, $params);

        if ($sqlValues === []) {
            if ($nullCondition === null) {
                return $operator === 'IN' ? '0=1' : '';
            }

            return $nullCondition;
        }

        $column = $this->quoteColumn($column);

        if (count($sqlValues) > 1) {
            $sql = "$column $operator (" . implode(', ', $sqlValues) . ')';
        } else {
            $operator = $operator === 'IN' ? '=' : '<>';
            $sql = "{$column}{$operator}" . reset($sqlValues);
        }

        return $nullCondition !== null && $nullConditionOperator !== null
            ? "{$sql} {$nullConditionOperator} {$nullCondition}"
            : $sql;
    }

    /**
     * Builds value placeholders to be used in {@see InCondition}.
     *
     * @param ConditionInterface|InCondition $condition the condition being built.
     * @param array $values the values to bind.
     * @param array $params the binding parameters.
     *
     * @return array prepared SQL placeholders.
     */
    protected function buildValues(ConditionInterface $condition, array $values, array &$params): array
    {
        $sqlValues = [];

        $column = $condition->getColumn();

        if (is_array($column)) {
            $column = reset($column);
        }

        if ($column instanceof Expression) {
            $column = $column->expression;
        }

        foreach ($values as $i => $value) {
            if (is_array($value) || $value instanceof ArrayAccess) {
                $value = $value[$column] ?? null;
            }

            if ($value === null) {
                continue;
            } elseif ($value instanceof ExpressionInterface) {
                $sqlValues[$i] = $this->queryBuilder->buildExpression($value, $params);
            } else {
                $sqlValues[$i] = $this->queryBuilder->bindParam($value, $params);
            }
        }

        return $sqlValues;
    }

    /**
     * Builds SQL for subquery-based `IN` condition.
     *
     * @param string $operator the operator (`IN` or `NOT IN`).
     * @param array|string|ExpressionInterface $columns the column name(s).
     * @param Query $values the subquery.
     * @param array $params the binding parameters.
     *
     * @return string the built SQL.
     */
    protected function buildSubqueryInCondition(
        string $operator,
        array|string|ExpressionInterface $columns,
        Query $values,
        array &$params,
    ): string {
        $sql = $this->queryBuilder->buildExpression($values, $params);

        if (is_array($columns)) {
            foreach ($columns as $i => $col) {
                if ($col instanceof Expression) {
                    $col = $col->expression;
                }

                $columns[$i] = $this->quoteColumn($col);
            }

            return '(' . implode(', ', $columns) . ") $operator $sql";
        }

        if ($columns instanceof Expression) {
            $columns = $columns->expression;
        }

        return $this->quoteColumn($columns) . " $operator $sql";
    }

    /**
     * Builds SQL for composite (multi-column)` `IN` condition.
     *
     * @param string $operator the operator (`IN` or `NOT IN`).
     * @param array $columns the column names.
     * @param array $values the value rows.
     * @param array $params the binding parameters.
     *
     * @return string the built SQL.
     */
    protected function buildCompositeInCondition(
        string $operator,
        array $columns,
        array $values,
        array &$params,
    ): string {
        $quotedColumns = [];

        foreach ($columns as $i => $column) {
            if ($column instanceof Expression) {
                $column = $column->expression;
            }

            $quotedColumns[$i] = $this->quoteColumn($column);
        }

        $vss = [];

        $notEqualOperator = $this->getNotEqualOperator();

        foreach ($values as $value) {
            $vs = [];

            foreach ($columns as $i => $column) {
                if ($column instanceof Expression) {
                    $column = $column->expression;
                }

                if (isset($value[$column])) {
                    $phName = $this->queryBuilder->bindParam($value[$column], $params);

                    $vs[] = $quotedColumns[$i] . ($operator === 'IN' ? ' = ' : " {$notEqualOperator} ") . $phName;
                } else {
                    $vs[] = $quotedColumns[$i] . ($operator === 'IN' ? ' IS' : ' IS NOT') . ' NULL';
                }
            }

            $vss[] = '(' . implode($operator === 'IN' ? ' AND ' : ' OR ', $vs) . ')';
        }

        if ($vss === []) {
            return $operator === 'IN' ? '0=1' : '';
        }

        return '(' . implode($operator === 'IN' ? ' OR ' : ' AND ', $vss) . ')';
    }

    /**
     * Returns the comparison operator used for `NOT IN` decomposition.
     *
     * Override in driver-specific builders to use a different operator (for example, `!=`).
     */
    protected function getNotEqualOperator(): string
    {
        return '<>';
    }

    /**
     * Builds `IS NULL` or `IS NOT NULL` condition for a column based on the operator.
     *
     * @param string $operator the operator (`IN` or `NOT IN`).
     * @param string $column the column name.
     *
     * @return string the `null` condition SQL.
     */
    protected function getNullCondition(string $operator, string $column): string
    {
        $column = $this->queryBuilder->db->quoteColumnName($column);

        return $column . ($operator === 'IN' ? ' IS NULL' : ' IS NOT NULL');
    }

    /**
     * Quotes a column name if it does not contain parentheses.
     *
     * @param string $column the column name to quote.
     *
     * @return string the quoted column name.
     */
    private function quoteColumn(string $column): string
    {
        return !str_contains($column, '(')
            ? $this->queryBuilder->db->quoteColumnName($column)
            : $column;
    }
}
