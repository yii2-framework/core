<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\oci;

use Exception;
use PDO;
use Yii;
use yii\base\InvalidCallException;
use yii\base\NotSupportedException;
use yii\db\CheckConstraint;
use yii\db\Connection;
use yii\db\Constraint;
use yii\db\ConstraintFinderInterface;
use yii\db\ConstraintFinderTrait;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yii\db\MetadataType;
use yii\db\IntegrityException;
use yii\db\oci\ColumnSchema;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;

use function array_change_key_case;
use function array_keys;
use function count;
use function explode;
use function implode;
use function str_replace;
use function strpos;
use function strtoupper;
use function trim;

/**
 * Schema is the class for retrieving metadata from an Oracle database (version 12c and later).
 *
 * @property-read string $lastInsertID The row ID of the last row inserted, or the last value retrieved from
 * the sequence object.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 *
 * @template T of ColumnSchema = ColumnSchema
 * @extends \yii\db\Schema<T>
 */
class Schema extends \yii\db\Schema implements ConstraintFinderInterface
{
    use ConstraintFinderTrait;

    /**
     * {@inheritdoc}
     */
    public $columnSchemaClass = ColumnSchema::class;

    /**
     * @var array map of DB errors and corresponding exceptions
     * If left part is found in DB error message exception class from the right part is used.
     */
    public $exceptionMap = [
        'ORA-00001: unique constraint' => IntegrityException::class,
    ];

    /**
     * {@inheritdoc}
     */
    protected $tableQuoteCharacter = '"';


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->defaultSchema === null) {
            $username = $this->db->username;

            if (empty($username)) {
                $username = $this->db->masters[0]['username'] ?? '';
            }

            $this->defaultSchema = strtoupper($username);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_USERS.html
     */
    protected function findSchemaNames()
    {
        $sql = <<<SQL
            SELECT "u"."USERNAME"
            FROM "ALL_USERS" "u"
            WHERE "u"."ORACLE_MAINTAINED" = 'N'
            ORDER BY "u"."USERNAME" ASC
            SQL;

        return $this->db->createCommand($sql)->queryColumn();
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/21/refrn/ALL_TABLES.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_VIEWS.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_MVIEWS.html
     */
    protected function findTableNames($schema = '')
    {
        if ($schema === '') {
            $sql = <<<SQL
                SELECT "t"."TABLE_NAME"
                FROM "USER_TABLES" "t"
                UNION ALL
                SELECT "v"."VIEW_NAME" AS "TABLE_NAME"
                FROM "USER_VIEWS" "v"
                UNION ALL
                SELECT "m"."MVIEW_NAME" AS "TABLE_NAME"
                FROM "USER_MVIEWS" "m"
                ORDER BY "TABLE_NAME"
                SQL;
            $command = $this->db->createCommand($sql);
        } else {
            $sql = <<<SQL
                SELECT "t"."TABLE_NAME"
                FROM "ALL_TABLES" "t"
                WHERE "t"."OWNER" = :schema
                UNION ALL
                SELECT "v"."VIEW_NAME" AS "TABLE_NAME"
                FROM "ALL_VIEWS" "v"
                WHERE "v"."OWNER" = :schema
                UNION ALL
                SELECT "m"."MVIEW_NAME" AS "TABLE_NAME"
                FROM "ALL_MVIEWS" "m"
                WHERE "m"."OWNER" = :schema
                ORDER BY "TABLE_NAME"
                SQL;
            $command = $this->db->createCommand($sql, [':schema' => $schema]);
        }

        $rows = $command->queryAll();

        $names = [];

        foreach ($rows as $row) {
            if ($this->db->slavePdo->getAttribute(PDO::ATTR_CASE) === PDO::CASE_LOWER) {
                $row = array_change_key_case($row, CASE_UPPER);
            }

            $names[] = $row['TABLE_NAME'];
        }

        return $names;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableSchema($name)
    {
        $table = $this->resolveTableName($name);

        if ($this->findColumns($table)) {
            $this->findConstraints($table);

            foreach ($table->columns as $column) {
                $column->defaultValue = $column->isPrimaryKey
                    ? null
                    : $column->defaultPhpTypecast($column->defaultValue);
            }

            return $table;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTablePrimaryKey($tableName)
    {
        return $this->loadTableConstraints($tableName, MetadataType::PRIMARY_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableForeignKeys($tableName)
    {
        return $this->loadTableConstraints($tableName, MetadataType::FOREIGN_KEYS);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_INDEXES.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_IND_COLUMNS.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_CONSTRAINTS.html
     */
    protected function loadTableIndexes($tableName)
    {
        $sql = <<<SQL
            SELECT
                "ui"."INDEX_NAME" AS "name",
                "uicol"."COLUMN_NAME" AS "column_name",
                CASE "ui"."UNIQUENESS" WHEN 'UNIQUE' THEN 1 ELSE 0 END AS "index_is_unique",
                CASE WHEN "uc"."CONSTRAINT_NAME" IS NOT NULL THEN 1 ELSE 0 END AS "index_is_primary"
            FROM "ALL_INDEXES" "ui"
            LEFT JOIN "ALL_IND_COLUMNS" "uicol"
                ON "uicol"."INDEX_OWNER" = "ui"."OWNER"
                AND "uicol"."INDEX_NAME" = "ui"."INDEX_NAME"
            LEFT JOIN "ALL_CONSTRAINTS" "uc"
                ON "uc"."OWNER" = "ui"."TABLE_OWNER"
                AND "uc"."CONSTRAINT_NAME" = "ui"."INDEX_NAME"
                AND "uc"."CONSTRAINT_TYPE" = 'P'
            WHERE "ui"."TABLE_OWNER" = :schemaName
                AND "ui"."TABLE_NAME" = :tableName
                AND "ui"."INDEX_TYPE" != 'LOB'
            ORDER BY "uicol"."COLUMN_POSITION" ASC
            SQL;

        $resolvedName = $this->resolveTableName($tableName);
        $indexes = $this->db->createCommand(
            $sql,
            [
                ':schemaName' => $resolvedName->schemaName,
                ':tableName' => $resolvedName->name,
            ],
        )->queryAll();
        $indexes = $this->normalizePdoRowKeyCase($indexes, true);
        $indexes = ArrayHelper::index($indexes, null, 'name');

        $result = [];

        foreach ($indexes as $name => $index) {
            $result[] = new IndexConstraint(
                [
                    'isPrimary' => (bool) $index[0]['index_is_primary'],
                    'isUnique' => (bool) $index[0]['index_is_unique'],
                    'name' => $name,
                    'columnNames' => ArrayHelper::getColumn($index, 'column_name'),
                ],
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableUniques($tableName)
    {
        return $this->loadTableConstraints($tableName, MetadataType::UNIQUES);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTableChecks($tableName)
    {
        return $this->loadTableConstraints($tableName, MetadataType::CHECKS);
    }

    /**
     * {@inheritdoc}
     * @throws NotSupportedException if this method is called.
     */
    protected function loadTableDefaultValues($tableName)
    {
        throw new NotSupportedException('Oracle does not support default value constraints.');
    }

    /**
     * {@inheritdoc}
     */
    public function releaseSavepoint($name)
    {
        // does nothing as Oracle does not support this
    }

    /**
     * {@inheritdoc}
     */
    public function quoteSimpleTableName($name)
    {
        return strpos($name, '"') !== false ? $name : '"' . $name . '"';
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder()
    {
        return Yii::createObject(QueryBuilder::class, [$this->db]);
    }

    /**
     * {@inheritdoc}
     */
    public function createColumnSchemaBuilder($type, $length = null)
    {
        return Yii::createObject(ColumnSchemaBuilder::class, [$type, $length]);
    }

    /**
     * Collects the table column metadata.
     *
     * @param TableSchema $table the table schema
     * @return bool whether the table exists
     *
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_TAB_COLUMNS.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_OBJECTS.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_COL_COMMENTS.html
     */
    protected function findColumns($table)
    {
        $sql = <<<SQL
            SELECT
                "a"."COLUMN_NAME",
                "a"."DATA_TYPE",
                "a"."DATA_PRECISION",
                "a"."DATA_SCALE",
                (
                    CASE "a"."CHAR_USED" WHEN 'C' THEN "a"."CHAR_LENGTH"
                        ELSE "a"."DATA_LENGTH"
                    END
                ) AS "DATA_LENGTH",
                "a"."NULLABLE",
                "a"."DATA_DEFAULT",
                "com"."COMMENTS" AS "COLUMN_COMMENT"
            FROM "ALL_TAB_COLUMNS" "a"
            INNER JOIN "ALL_OBJECTS" "b"
                ON "b"."OWNER" = "a"."OWNER"
                AND "b"."OBJECT_NAME" = "a"."TABLE_NAME"
            LEFT JOIN "ALL_COL_COMMENTS" "com"
                ON "a"."OWNER" = "com"."OWNER"
                AND "a"."TABLE_NAME" = "com"."TABLE_NAME"
                AND "a"."COLUMN_NAME" = "com"."COLUMN_NAME"
            WHERE "a"."OWNER" = :schemaName
                AND "b"."OBJECT_TYPE" IN ('TABLE', 'VIEW', 'MATERIALIZED VIEW')
                AND "b"."OBJECT_NAME" = :tableName
            ORDER BY "a"."COLUMN_ID"
            SQL;

        try {
            $columns = $this->db->createCommand(
                $sql,
                [
                    ':tableName' => $table->name,
                    ':schemaName' => $table->schemaName,
                ],
            )->queryAll();
        } catch (Exception $e) {
            return false;
        }

        if (empty($columns)) {
            return false;
        }

        foreach ($columns as $column) {
            if ($this->db->slavePdo->getAttribute(PDO::ATTR_CASE) === PDO::CASE_LOWER) {
                $column = array_change_key_case($column, CASE_UPPER);
            }

            $c = $this->createColumn($column);

            $table->columns[$c->name] = $c;
        }

        return true;
    }

    /**
     * Sequence name of table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @return string|null whether the sequence exists
     *
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_DEPENDENCIES.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_TRIGGERS.html
     */
    protected function getTableSequenceName($tableName, $schemaName)
    {
        $sequenceNameSql = <<<SQL
            SELECT "ad"."REFERENCED_NAME" AS "SEQUENCE_NAME"
            FROM "ALL_DEPENDENCIES" "ad"
            INNER JOIN "ALL_TRIGGERS" "at"
                ON "at"."OWNER" = "ad"."OWNER"
                AND "at"."TRIGGER_NAME" = "ad"."NAME"
            WHERE "at"."TABLE_OWNER" = :schemaName
                AND "at"."TABLE_NAME" = :tableName
                AND "ad"."TYPE" = 'TRIGGER'
                AND "ad"."REFERENCED_TYPE" = 'SEQUENCE'
            SQL;

        $sequenceName = $this->db->createCommand(
            $sequenceNameSql,
            [
                ':schemaName' => $schemaName,
                ':tableName' => $tableName,
            ],
        )->queryScalar();

        return $sequenceName === false ? null : $sequenceName;
    }

    /**
     * @Overrides method in class 'Schema'
     * @see https://www.php.net/manual/en/function.PDO-lastInsertId.php -> Oracle does not support this
     *
     * Returns the ID of the last inserted row or sequence value.
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     * @throws InvalidCallException if the DB connection is not active
     */
    public function getLastInsertID($sequenceName = '')
    {
        if ($this->db->isActive) {
            // get the last insert id from the master connection
            $sequenceName = $this->quoteSimpleTableName($sequenceName);

            $sql = <<<SQL
            SELECT {$sequenceName}.CURRVAL FROM DUAL
            SQL;

            return $this->db->useMaster(
                static fn(Connection $db) => $db->createCommand($sql)->queryScalar(),
            );
        } else {
            throw new InvalidCallException('DB Connection is not active.');
        }
    }

    /**
     * Creates ColumnSchema instance.
     *
     * @param array $column
     * @return T
     */
    protected function createColumn($column)
    {
        $c = $this->createColumnSchema();

        $c->name = $column['COLUMN_NAME'];
        $c->allowNull = $column['NULLABLE'] === 'Y';
        $c->comment = $column['COLUMN_COMMENT'] === null ? '' : $column['COLUMN_COMMENT'];
        $c->isPrimaryKey = false;

        $this->extractColumnType(
            $c,
            $column['DATA_TYPE'],
            $column['DATA_PRECISION'],
            $column['DATA_SCALE'],
            $column['DATA_LENGTH'],
        );
        $this->extractColumnSize(
            $c,
            $column['DATA_TYPE'],
            $column['DATA_PRECISION'],
            $column['DATA_SCALE'],
            $column['DATA_LENGTH'],
        );

        $c->phpType = $this->getColumnPhpType($c);

        // store raw default for deferred resolution in `loadTableSchema()`, where `isPrimaryKey` is known
        $c->defaultValue = $column['DATA_DEFAULT'] ?? null;

        return $c;
    }

    /**
     * Finds constraints and fills them into TableSchema object passed.
     *
     * @param TableSchema $table
     *
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_CONS_COLUMNS.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_CONSTRAINTS.html
     */
    protected function findConstraints($table)
    {
        $sql = <<<SQL
            SELECT
                "d"."CONSTRAINT_NAME",
                "d"."CONSTRAINT_TYPE",
                "c"."COLUMN_NAME",
                "c"."POSITION",
                "d"."R_CONSTRAINT_NAME",
                "e"."TABLE_NAME" AS "TABLE_REF",
                "f"."COLUMN_NAME" AS "COLUMN_REF",
                "c"."TABLE_NAME"
            FROM "ALL_CONS_COLUMNS" "c"
            INNER JOIN "ALL_CONSTRAINTS" "d"
                ON "d"."OWNER" = "c"."OWNER"
                AND "d"."CONSTRAINT_NAME" = "c"."CONSTRAINT_NAME"
            LEFT JOIN "ALL_CONSTRAINTS" "e"
                ON "e"."OWNER" = "d"."R_OWNER"
                AND "e"."CONSTRAINT_NAME" = "d"."R_CONSTRAINT_NAME"
            LEFT JOIN "ALL_CONS_COLUMNS" "f"
                ON "f"."OWNER" = "e"."OWNER"
                AND "f"."CONSTRAINT_NAME" = "e"."CONSTRAINT_NAME"
                AND "f"."POSITION" = "c"."POSITION"
            WHERE "c"."OWNER" = :schemaName
                AND "c"."TABLE_NAME" = :tableName
            ORDER BY "d"."CONSTRAINT_NAME", "c"."POSITION"
            SQL;

        $command = $this->db->createCommand(
            $sql,
            [
                ':tableName' => $table->name,
                ':schemaName' => $table->schemaName,
            ],
        );

        $constraints = [];

        foreach ($command->queryAll() as $row) {
            if ($this->db->slavePdo->getAttribute(PDO::ATTR_CASE) === PDO::CASE_LOWER) {
                $row = array_change_key_case($row, CASE_UPPER);
            }

            if ($row['CONSTRAINT_TYPE'] === 'P') {
                $table->columns[$row['COLUMN_NAME']]->isPrimaryKey = true;
                $table->primaryKey[] = $row['COLUMN_NAME'];

                if (empty($table->sequenceName)) {
                    $table->sequenceName = $this->getTableSequenceName($table->name, $table->schemaName);
                }
            }

            if ($row['CONSTRAINT_TYPE'] !== 'R') {
                // this condition is not checked in SQL WHERE because of an Oracle Bug:
                // see https://github.com/yiisoft/yii2/pull/8844
                continue;
            }

            $name = $row['CONSTRAINT_NAME'];

            if (!isset($constraints[$name])) {
                $constraints[$name] = [
                    'tableName' => $row['TABLE_REF'],
                    'columns' => [],
                ];
            }

            $constraints[$name]['columns'][$row['COLUMN_NAME']] = $row['COLUMN_REF'];
        }

        foreach ($constraints as $name => $constraint) {
            $table->foreignKeys[$name] = [
                $constraint['tableName'],
                ...$constraint['columns'],
            ];
        }
    }

    /**
     * Returns all unique indexes for the given table.
     * Each array element is of the following structure:.
     *
     * ```
     * [
     *     'IndexName1' => ['col1' [, ...]],
     *     'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * @param TableSchema $table the table metadata
     * @return array all unique indexes for the given table.
     * @since 2.0.4
     *
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_INDEXES.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_IND_COLUMNS.html
     */
    public function findUniqueIndexes($table)
    {
        $query = <<<SQL
            SELECT
                "dic"."INDEX_NAME" AS "index_name",
                "dic"."COLUMN_NAME" AS "column_name"
            FROM "ALL_INDEXES" "di"
            INNER JOIN "ALL_IND_COLUMNS" "dic"
                ON "dic"."INDEX_OWNER" = "di"."OWNER"
                AND "dic"."TABLE_OWNER" = "di"."TABLE_OWNER"
                AND "dic"."TABLE_NAME" = "di"."TABLE_NAME"
                AND "dic"."INDEX_NAME" = "di"."INDEX_NAME"
            WHERE "di"."UNIQUENESS" = 'UNIQUE'
                AND "di"."TABLE_OWNER" = :schemaName
                AND "di"."TABLE_NAME" = :tableName
            ORDER BY "dic"."TABLE_NAME", "dic"."INDEX_NAME", "dic"."COLUMN_POSITION"
            SQL;

        $result = [];

        $command = $this->db->createCommand(
            $query,
            [
                ':tableName' => $table->name,
                ':schemaName' => $table->schemaName,
            ],
        );

        $rows = $this->normalizePdoRowKeyCase($command->queryAll(), true);

        foreach ($rows as $row) {
            $result[$row['index_name']][] = $row['column_name'];
        }

        return $result;
    }

    /**
     * Extracts the data types for the given column.
     * @param ColumnSchema $column
     * @param string $dbType DB type
     * @param string $precision total number of digits.
     * This parameter is available since version 2.0.4.
     * @param string $scale number of digits on the right of the decimal separator.
     * This parameter is available since version 2.0.4.
     * @param string $length length for character types.
     * This parameter is available since version 2.0.4.
     */
    protected function extractColumnType($column, $dbType, $precision, $scale, $length)
    {
        $column->dbType = $dbType;

        if (strpos($dbType, 'FLOAT') !== false || strpos($dbType, 'DOUBLE') !== false) {
            $column->type = 'double';
        } elseif (strpos($dbType, 'NUMBER') !== false) {
            if ($scale === null || $scale > 0) {
                $column->type = 'decimal';
            } else {
                $column->type = 'integer';
            }
        } elseif (strpos($dbType, 'INTEGER') !== false) {
            $column->type = 'integer';
        } elseif (strpos($dbType, 'BLOB') !== false) {
            $column->type = 'binary';
        } elseif (strpos($dbType, 'CLOB') !== false) {
            $column->type = 'text';
        } elseif (strpos($dbType, 'TIMESTAMP') !== false) {
            $column->type = 'timestamp';
        } else {
            $column->type = 'string';
        }
    }

    /**
     * Extracts size, precision and scale information from column's DB type.
     * @param ColumnSchema $column
     * @param string $dbType the column's DB type
     * @param string $precision total number of digits.
     * This parameter is available since version 2.0.4.
     * @param string $scale number of digits on the right of the decimal separator.
     * This parameter is available since version 2.0.4.
     * @param string $length length for character types.
     * This parameter is available since version 2.0.4.
     */
    protected function extractColumnSize($column, $dbType, $precision, $scale, $length)
    {
        $column->size = trim((string) $length) === '' ? null : (int) $length;
        $column->precision = trim((string) $precision) === '' ? null : (int) $precision;
        $column->scale = trim((string) $scale) === '' ? null : (int) $scale;
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table, $columns)
    {
        $params = [];
        $returnParams = [];

        $sql = $this->db->getQueryBuilder()->insert($table, $columns, $params);
        $tableSchema = $this->getTableSchema($table);

        $returnColumns = $tableSchema->primaryKey;

        if (!empty($returnColumns)) {
            $columnSchemas = $tableSchema->columns;

            $returning = [];

            foreach ((array) $returnColumns as $name) {
                $phName = QueryBuilder::PARAM_PREFIX . (count($params) + count($returnParams));

                $returnParams[$phName] = [
                    'column' => $name,
                    'value' => '',
                ];

                if (!isset($columnSchemas[$name]) || $columnSchemas[$name]->phpType !== 'integer') {
                    $returnParams[$phName]['dataType'] = \PDO::PARAM_STR;
                } else {
                    $returnParams[$phName]['dataType'] = \PDO::PARAM_INT;
                }

                $returnParams[$phName]['size'] = $columnSchemas[$name]->size ?? -1;
                $returning[] = $this->quoteColumnName($name);
            }

            $sql .= ' RETURNING '
                . implode(', ', $returning)
                . ' INTO '
                . implode(', ', array_keys($returnParams));
        }

        $command = $this->db->createCommand($sql, $params);
        $command->prepare(false);

        foreach ($returnParams as $name => &$value) {
            $command->pdoStatement->bindParam($name, $value['value'], $value['dataType'], $value['size']);
        }

        unset($value);

        if (!$command->execute()) {
            return false;
        }

        $result = [];

        foreach ($returnParams as $value) {
            $result[$value['column']] = $value['value'];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveTableName($name)
    {
        $parts = explode('.', str_replace('"', '', $name));

        $tableName = $parts[1] ?? $parts[0];

        $schemaName = isset($parts[1]) ? $parts[0] : $this->defaultSchema;

        $fullName = $schemaName !== $this->defaultSchema
            ? "{$schemaName}.{$tableName}"
            : $tableName;

        $resolvedName = new TableSchema();

        $resolvedName->name = $tableName;
        $resolvedName->schemaName = $schemaName;
        $resolvedName->fullName = $fullName;

        return $resolvedName;
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param string $tableName table name.
     * @param MetadataType $returnType return type.
     *
     * @return mixed constraints.
     *
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_CONSTRAINTS.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_CONS_COLUMNS.html
     */
    private function loadTableConstraints(string $tableName, MetadataType $returnType): mixed
    {
        $sql = <<<SQL
            SELECT
                "uc"."CONSTRAINT_NAME" AS "name",
                "uccol"."COLUMN_NAME" AS "column_name",
                "uc"."CONSTRAINT_TYPE" AS "type",
                "fuc"."OWNER" AS "foreign_table_schema",
                "fuc"."TABLE_NAME" AS "foreign_table_name",
                "fuccol"."COLUMN_NAME" AS "foreign_column_name",
                "uc"."DELETE_RULE" AS "on_delete",
                "uc"."SEARCH_CONDITION" AS "check_expr"
            FROM "ALL_CONSTRAINTS" "uc"
            INNER JOIN "ALL_CONS_COLUMNS" "uccol"
                ON "uccol"."OWNER" = "uc"."OWNER"
                AND "uccol"."CONSTRAINT_NAME" = "uc"."CONSTRAINT_NAME"
            LEFT JOIN "ALL_CONSTRAINTS" "fuc"
                ON "fuc"."OWNER" = "uc"."R_OWNER"
                AND "fuc"."CONSTRAINT_NAME" = "uc"."R_CONSTRAINT_NAME"
            LEFT JOIN "ALL_CONS_COLUMNS" "fuccol"
                ON "fuccol"."OWNER" = "fuc"."OWNER"
                AND "fuccol"."CONSTRAINT_NAME" = "fuc"."CONSTRAINT_NAME"
                AND "fuccol"."POSITION" = "uccol"."POSITION"
            WHERE "uc"."OWNER" = :schemaName
                AND "uc"."TABLE_NAME" = :tableName
            ORDER BY "uccol"."POSITION" ASC
            SQL;

        $resolvedName = $this->resolveTableName($tableName);
        $constraints = $this->db->createCommand(
            $sql,
            [
                ':schemaName' => $resolvedName->schemaName,
                ':tableName' => $resolvedName->name,
            ],
        )->queryAll();
        $constraints = $this->normalizePdoRowKeyCase($constraints, true);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            MetadataType::CHECKS->value => [],
            MetadataType::FOREIGN_KEYS->value => [],
            MetadataType::PRIMARY_KEY->value => null,
            MetadataType::UNIQUES->value => [],
        ];

        foreach ($constraints as $type => $names) {
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'P':
                        $result[MetadataType::PRIMARY_KEY->value] = new Constraint(
                            [
                                'name' => $name,
                                'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                            ],
                        );
                        break;
                    case 'R':
                        $result[MetadataType::FOREIGN_KEYS->value][] = new ForeignKeyConstraint(
                            [
                                'name' => $name,
                                'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                                'foreignSchemaName' => $constraint[0]['foreign_table_schema'],
                                'foreignTableName' => $constraint[0]['foreign_table_name'],
                                'foreignColumnNames' => ArrayHelper::getColumn($constraint, 'foreign_column_name'),
                                'onDelete' => $constraint[0]['on_delete'],
                                'onUpdate' => null,
                            ],
                        );
                        break;
                    case 'U':
                        $result[MetadataType::UNIQUES->value][] = new Constraint(
                            [
                                'name' => $name,
                                'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                            ],
                        );
                        break;
                    case 'C':
                        $result[MetadataType::CHECKS->value][] = new CheckConstraint(
                            [
                                'name' => $name,
                                'columnNames' => ArrayHelper::getColumn($constraint, 'column_name'),
                                'expression' => $constraint[0]['check_expr'],
                            ],
                        );
                        break;
                }
            }
        }

        return $this->cacheAndReturnConstraints($tableName, $result, $returnType);
    }
}
