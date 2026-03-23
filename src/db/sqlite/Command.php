<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\sqlite;

use yii\db\QueryMode;
use yii\db\SqlToken;
use yii\helpers\StringHelper;
use function count;

/**
 * Command represents an SQLite's SQL statement to be executed against a database.
 *
 * {@inheritdoc}
 *
 * @author Sergey Makinen <sergey@makinen.ru>
 * @since 2.0.14
 */
class Command extends \yii\db\Command
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $sql = $this->getSql();
        $params = $this->params;
        $statements = $this->splitStatements($sql, $params);

        if ($statements === false) {
            return parent::execute();
        }

        $result = null;
        foreach ($statements as $statement) {
            [$statementSql, $statementParams] = $statement;

            $this->setSql($statementSql)->bindValues($statementParams);

            $result = parent::execute();
        }

        $this->setSql($sql)->bindValues($params);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function queryInternal(QueryMode $queryMode)
    {
        $sql = $this->getSql();

        $params = $this->params;

        $statements = $this->splitStatements($sql, $params);

        if ($statements === false) {
            return parent::queryInternal($queryMode);
        }

        [$lastStatementSql, $lastStatementParams] = array_pop($statements);

        foreach ($statements as $statement) {
            [$statementSql, $statementParams] = $statement;
            $this->setSql($statementSql)->bindValues($statementParams);
            parent::execute();
        }

        // disable query cache for split batches: earlier statements may mutate data, and the cache key would only
        // reflect the tail statement's SQL, causing different batches with the same final SELECT to share cached
        // results.
        $originalCacheDuration = $this->queryCacheDuration;
        $this->queryCacheDuration = -1;

        $this->setSql($lastStatementSql)->bindValues($lastStatementParams);

        try {
            $result = parent::queryInternal($queryMode);
        } finally {
            $this->queryCacheDuration = $originalCacheDuration;
            $this->setSql($sql)->bindValues($params);
        }

        return $result;
    }

    /**
     * Splits the specified SQL code into individual SQL statements and returns them or `false` if there's a single
     * statement.
     *
     * @param string $sql
     * @param array $params
     *
     * @return list<array{string, array}>|false
     */
    private function splitStatements($sql, $params)
    {
        $semicolonIndex = strpos($sql, ';');

        if ($semicolonIndex === false || $semicolonIndex === StringHelper::byteLength($sql) - 1) {
            return false;
        }

        $tokenizer = new SqlTokenizer($sql);

        $codeToken = $tokenizer->tokenize();

        if (count($codeToken->getChildren()) === 1) {
            return false;
        }

        $statements = [];

        foreach ($codeToken->getChildren() as $statement) {
            $statements[] = [$statement->getSql(), $this->extractUsedParams($statement, $params)];
        }
        return $statements;
    }

    /**
     * Returns named bindings used in the specified statement token.
     *
     * @param SqlToken $statement
     * @param array $params
     *
     * @return array
     */
    private function extractUsedParams(SqlToken $statement, $params)
    {
        preg_match_all('/(?P<placeholder>:\w+)/', $statement->getSql(), $matches, PREG_SET_ORDER);

        $result = [];

        foreach ($matches as $match) {
            $phName = ltrim($match['placeholder'], ':');

            if (isset($params[$phName])) {
                $result[$phName] = $params[$phName];
            } elseif (isset($params[':' . $phName])) {
                $result[':' . $phName] = $params[':' . $phName];
            }
        }

        return $result;
    }
}
