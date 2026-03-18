<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\mysql;

use yii\base\InvalidArgumentException;
use yii\db\Exception;
use yii\db\Expression;
use yii\db\JsonExpression;
use yii\db\Query;

/**
 * QueryBuilder is the query builder for MySQL databases.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    public $typeMap = [
        Schema::TYPE_PK => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_UPK => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_BIGPK => 'bigint NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
        Schema::TYPE_CHAR => 'char(1)',
        Schema::TYPE_STRING => 'varchar(255)',
        Schema::TYPE_TEXT => 'text',
        Schema::TYPE_TINYINT => 'tinyint',
        Schema::TYPE_SMALLINT => 'smallint',
        Schema::TYPE_INTEGER => 'int',
        Schema::TYPE_BIGINT => 'bigint',
        Schema::TYPE_FLOAT => 'float',
        Schema::TYPE_DOUBLE => 'double',
        Schema::TYPE_DECIMAL => 'decimal(10,0)',
        Schema::TYPE_DATE => 'date',
        Schema::TYPE_BINARY => 'blob',
        Schema::TYPE_BOOLEAN => 'tinyint(1)',
        Schema::TYPE_MONEY => 'decimal(19,4)',
        Schema::TYPE_JSON => 'json'
    ];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->typeMap = [
            ...$this->typeMap,
            ...$this->defaultTimeTypeMap(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultExpressionBuilders()
    {
        return [
            ...parent::defaultExpressionBuilders(),
            JsonExpression::class => JsonExpressionBuilder::class,
        ];
    }

    /**
     * Builds a SQL statement for renaming a column.
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $oldName the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB column.
     * @throws Exception
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/alter-table.html
     */
    public function renameColumn($table, $oldName, $newName)
    {
        $quotedTable = $this->db->quoteTableName($table);

        $row = $this->db->createCommand(
            <<<SQL
            SHOW CREATE TABLE {$quotedTable}
            SQL
        )->queryOne();

        if ($row === false) {
            throw new Exception("Unable to find column '$oldName' in table '$table'.");
        }

        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }

        $quotedOldName = $this->db->quoteColumnName($oldName);
        $quotedNewName = $this->db->quoteColumnName($newName);

        if (preg_match_all('/^\s*[`"](.*?)[`"]\s+(.*?),?$/m', $sql, $matches)) {
            foreach ($matches[1] as $i => $c) {
                if ($c === $oldName) {
                    return <<<SQL
                    ALTER TABLE {$quotedTable} CHANGE {$quotedOldName} {$quotedNewName} {$matches[2][$i]}
                    SQL;
                }
            }
        }

        // try to give back a SQL anyway
        return <<<SQL
        ALTER TABLE {$quotedTable} CHANGE {$quotedOldName} {$quotedNewName}
        SQL;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://bugs.mysql.com/bug.php?id=48875
     * @see https://dev.mysql.com/doc/refman/8.0/en/alter-table.html
     */
    public function createIndex($name, $table, $columns, $unique = false)
    {
        $quotedTable = $this->db->quoteTableName($table);
        $quotedName = $this->db->quoteTableName($name);

        $unique = $unique ? ' UNIQUE' : '';

        $columns = $this->buildColumns($columns);

        return <<<SQL
        ALTER TABLE {$quotedTable} ADD{$unique} INDEX {$quotedName} ({$columns})
        SQL;
    }

    /**
     * Builds a SQL statement for dropping a foreign key constraint.
     * @param string $name the name of the foreign key constraint to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose foreign is to be dropped. The name will be properly quoted by the method.
     * @return string the SQL statement for dropping a foreign key constraint.
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/alter-table.html
     */
    public function dropForeignKey($name, $table)
    {
        $quotedTable = $this->db->quoteTableName($table);
        $quotedName = $this->db->quoteColumnName($name);

        return <<<SQL
        ALTER TABLE {$quotedTable} DROP FOREIGN KEY {$quotedName}
        SQL;
    }

    /**
     * Builds a SQL statement for removing a primary key constraint to an existing table.
     * @param string $name the name of the primary key constraint to be removed.
     * @param string $table the table that the primary key constraint will be removed from.
     * @return string the SQL statement for removing a primary key constraint from an existing table.
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/alter-table.html
     */
    public function dropPrimaryKey($name, $table)
    {
        $quotedTable = $this->db->quoteTableName($table);

        return <<<SQL
        ALTER TABLE {$quotedTable} DROP PRIMARY KEY
        SQL;
    }

    /**
     * {@inheritdoc}
     */
    public function dropUnique($name, $table)
    {
        return $this->dropIndex($name, $table);
    }

    /**
     * Creates a SQL statement for resetting the sequence value of a table's primary key.
     * The sequence will be reset such that the primary key of the next new row inserted
     * will have the specified value or 1.
     * @param string $tableName the name of the table whose primary key sequence will be reset
     * @param mixed $value the value for the primary key of the next new row inserted. If this is not set,
     * the next new row's primary key will have a value 1.
     * @return string the SQL statement for resetting sequence
     * @throws InvalidArgumentException if the table does not exist or there is no sequence associated with the table.
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/alter-table.html
     */
    public function resetSequence($tableName, $value = null)
    {
        $table = $this->db->getTableSchema($tableName);

        if ($table !== null && $table->sequenceName !== null) {
            $tableName = $this->db->quoteTableName($tableName);

            if ($value === null) {
                $key = $this->db->quoteColumnName(reset($table->primaryKey));

                $value = $this->db->createCommand(
                    <<<SQL
                    SELECT MAX({$key}) FROM {$tableName}
                    SQL
                )->queryScalar() + 1;
            } else {
                $value = (int) $value;
            }

            return <<<SQL
            ALTER TABLE {$tableName} AUTO_INCREMENT={$value}
            SQL;
        } elseif ($table === null) {
            throw new InvalidArgumentException("Table not found: $tableName");
        }

        throw new InvalidArgumentException("There is no sequence associated with table '$tableName'.");
    }

    /**
     * Builds a SQL statement for enabling or disabling integrity check.
     * @param bool $check whether to turn on or off the integrity check.
     * @param string $schema the schema of the tables. Meaningless for MySQL.
     * @param string $table the table name. Meaningless for MySQL.
     * @return string the SQL statement for checking integrity
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/server-system-variables.html#sysvar_foreign_key_checks
     */
    public function checkIntegrity($check = true, $schema = '', $table = '')
    {
        $value = $check ? 1 : 0;

        return <<<SQL
        SET FOREIGN_KEY_CHECKS = {$value}
        SQL;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/select.html
     */
    public function buildLimit($limit, $offset)
    {
        $sql = '';

        if ($this->hasLimit($limit)) {
            $sql = "LIMIT {$limit}";

            if ($this->hasOffset($offset)) {
                $sql .= " OFFSET {$offset}";
            }
        } elseif ($this->hasOffset($offset)) {
            // limit is not optional in MySQL
            // https://stackoverflow.com/questions/255517/mysql-offset-infinite-rows/271650#271650
            $sql = <<<SQL
            LIMIT {$offset}, 18446744073709551615
            SQL;
        }

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    protected function hasLimit($limit)
    {
        // In MySQL limit argument must be nonnegative integer constant
        return ctype_digit((string) $limit);
    }

    /**
     * {@inheritdoc}
     */
    protected function hasOffset($offset)
    {
        // In MySQL offset argument must be nonnegative integer constant
        $offset = (string) $offset;

        return ctype_digit($offset) && $offset !== '0';
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareInsertValues($table, $columns, $params = [])
    {
        [$names, $placeholders, $values, $params] = parent::prepareInsertValues($table, $columns, $params);

        if (!$columns instanceof Query && empty($names)) {
            $tableSchema = $this->db->getSchema()->getTableSchema($table);

            if ($tableSchema !== null) {
                if (!empty($tableSchema->primaryKey)) {
                    $columns = $tableSchema->primaryKey;
                    $defaultValue = 'NULL';
                } else {
                    $columns = [reset($tableSchema->columns)->name];
                    $defaultValue = 'DEFAULT';
                }

                foreach ($columns as $name) {
                    $names[] = $this->db->quoteColumnName($name);
                    $placeholders[] = $defaultValue;
                }
            }
        }
        return [$names, $placeholders, $values, $params];
    }

    /**
     * {@inheritdoc}
     * @see https://dev.mysql.com/doc/refman/8.0/en/insert-on-duplicate.html
     */
    public function upsert($table, $insertColumns, $updateColumns, &$params)
    {
        $insertSql = $this->insert($table, $insertColumns, $params);

        [$uniqueNames, , $updateNames] = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns);

        if (empty($uniqueNames)) {
            return $insertSql;
        }

        if ($updateNames === []) {
            // there are no columns to update
            $updateColumns = false;
        }

        if ($updateColumns === true) {
            $updateColumns = [];

            foreach ($updateNames as $name) {
                $updateColumns[$name] = new Expression('VALUES(' . $this->db->quoteColumnName($name) . ')');
            }

        } elseif ($updateColumns === false) {
            $name = $this->db->quoteColumnName(reset($uniqueNames));
            $updateColumns = [$name => new Expression($this->db->quoteTableName($table) . '.' . $name)];
        }

        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        return "{$insertSql} ON DUPLICATE KEY UPDATE " . implode(', ', $updates);
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/alter-table.html
     */
    public function addCommentOnColumn($table, $column, $comment)
    {
        // Strip existing comment which may include escaped quotes
        $definition = trim(preg_replace("/COMMENT '(?:''|[^'])*'/i", '', $this->getColumnDefinition($table, $column)));

        $checkRegex = '/CHECK *(\(([^()]|(?-2))*\))/';

        $check = preg_match($checkRegex, $definition, $checkMatches);

        if ($check === 1) {
            $definition = preg_replace($checkRegex, '', $definition);
        }

        $quotedTable = $this->db->quoteTableName($table);
        $quotedColumn = $this->db->quoteColumnName($column);
        $quotedComment = $this->db->quoteValue($comment);

        $columnDef = empty($definition) ? '' : " {$definition}";

        $alterSql = <<<SQL
        ALTER TABLE {$quotedTable} CHANGE {$quotedColumn} {$quotedColumn}{$columnDef} COMMENT {$quotedComment}
        SQL;

        if ($check === 1) {
            $alterSql .= ' ' . $checkMatches[0];
        }

        return $alterSql;
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/alter-table.html
     */
    public function addCommentOnTable($table, $comment)
    {
        $quotedTable = $this->db->quoteTableName($table);
        $quotedComment = $this->db->quoteValue($comment);

        return <<<SQL
        ALTER TABLE {$quotedTable} COMMENT {$quotedComment}
        SQL;
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function dropCommentFromColumn($table, $column)
    {
        return $this->addCommentOnColumn($table, $column, '');
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function dropCommentFromTable($table)
    {
        return $this->addCommentOnTable($table, '');
    }

    /**
     * {@inheritdoc}
     * @since 2.0.8
     */
    public function selectExists($rawSql)
    {
        $quotedAlias = $this->db->quoteColumnName('result');

        return <<<SQL
        SELECT EXISTS({$rawSql}) AS {$quotedAlias}
        SQL;
    }

    /**
     * Gets column definition.
     *
     * @param string $table table name
     * @param string $column column name
     * @return string|null the column definition
     * @throws Exception in case when table does not contain column
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/show-create-table.html
     */
    private function getColumnDefinition($table, $column)
    {
        $quotedTable = $this->db->quoteTableName($table);

        $row = $this->db->createCommand(
            <<<SQL
            SHOW CREATE TABLE {$quotedTable}
            SQL
        )->queryOne();

        if ($row === false) {
            throw new Exception("Unable to find column '$column' in table '$table'.");
        }

        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }

        if (preg_match_all('/^\s*[`"](.*?)[`"]\s+(.*?),?$/m', $sql, $matches)) {
            foreach ($matches[1] as $i => $c) {
                if ($c === $column) {
                    return $matches[2][$i];
                }
            }
        }

        return null;
    }

    /**
     * Returns the map for default time type with fractional seconds precision.
     *
     * @return array
     */
    private function defaultTimeTypeMap()
    {
        return [
            Schema::TYPE_DATETIME => 'datetime(0)',
            Schema::TYPE_TIMESTAMP => 'timestamp(0)',
            Schema::TYPE_TIME => 'time(0)',
        ];
    }
}
