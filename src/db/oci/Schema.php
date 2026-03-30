<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\oci;

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
 * @property-read string $lastInsertID The row ID of the last row inserted, or the last value retrieved from the
 * sequence object.
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
     * @var array Map of DB errors and corresponding exceptions.
     * If left part is found in DB error message exception class from the right part is used.
     */
    public $exceptionMap = ['ORA-00001: unique constraint' => IntegrityException::class];

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
                SELECT "t"."TABLE_NAME" AS "table_name"
                FROM "USER_TABLES" "t"
                UNION ALL
                SELECT "v"."VIEW_NAME" AS "table_name"
                FROM "USER_VIEWS" "v"
                UNION ALL
                SELECT "m"."MVIEW_NAME" AS "table_name"
                FROM "USER_MVIEWS" "m"
                ORDER BY "table_name"
                SQL;
            $command = $this->db->createCommand($sql);
        } else {
            $sql = <<<SQL
                SELECT "t"."TABLE_NAME" AS "table_name"
                FROM "ALL_TABLES" "t"
                WHERE "t"."OWNER" = :schema
                UNION ALL
                SELECT "v"."VIEW_NAME" AS "table_name"
                FROM "ALL_VIEWS" "v"
                WHERE "v"."OWNER" = :schema
                UNION ALL
                SELECT "m"."MVIEW_NAME" AS "table_name"
                FROM "ALL_MVIEWS" "m"
                WHERE "m"."OWNER" = :schema
                ORDER BY "table_name"
                SQL;
            $command = $this->db->createCommand($sql, [':schema' => $schema]);
        }

        $rows = $this->normalizePdoRowKeyCase($command->queryAll(), true);

        $names = [];

        foreach ($rows as $row) {
            $names[] = $row['table_name'];
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
                $column->defaultValue = $column->isPrimaryKey && $column->autoIncrement
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
     *
     * @throws NotSupportedException if this method is called.
     */
    protected function loadTableDefaultValues($tableName)
    {
        throw new NotSupportedException('Oracle does not support default value constraints.');
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
     * Collects the metadata of table columns.
     *
     * @param TableSchema $table The table metadata.
     *
     * @return bool Whether the table exists in the database.
     *
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_TAB_COLUMNS.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_OBJECTS.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_COL_COMMENTS.html
     */
    protected function findColumns($table)
    {
        $sql = <<<SQL
            SELECT
                "a"."COLUMN_NAME" AS "column_name",
                "a"."DATA_TYPE" AS "data_type",
                "a"."DATA_PRECISION" AS "data_precision",
                "a"."DATA_SCALE" AS "data_scale",
                (
                    CASE "a"."CHAR_USED" WHEN 'C' THEN "a"."CHAR_LENGTH"
                        ELSE "a"."DATA_LENGTH"
                    END
                ) AS "data_length",
                "a"."NULLABLE" AS "nullable",
                "a"."DATA_DEFAULT" AS "data_default",
                "com"."COMMENTS" AS "column_comment"
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

        $columns = $this->db->createCommand(
            $sql,
            [
                ':tableName' => $table->name,
                ':schemaName' => $table->schemaName,
            ],
        )->queryAll();

        if (empty($columns)) {
            return false;
        }

        $columns = $this->normalizePdoRowKeyCase($columns, true);

        foreach ($columns as $column) {
            $c = $this->createColumn($column);

            $table->columns[$c->name] = $c;
        }

        return true;
    }

    /**
     * Sequence name of table.
     *
     * @param string $tableName Table name.
     * @param string $schemaName Schema name.
     *
     * @return string|null Sequence name, or `null` if the table has no trigger-based sequence.
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
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string $sequenceName Name of the sequence object (required by some DBMS).
     *
     * @throws InvalidCallException if the DB connection is not active.
     *
     * @return string The row ID of the last row inserted, or the last value retrieved from the sequence object.
     *
     * @see https://www.php.net/manual/en/function.PDO-lastInsertId.php -> Oracle does not support this
     */
    public function getLastInsertID($sequenceName = '')
    {
        if (!$this->db->isActive) {
            throw new InvalidCallException('DB Connection is not active.');
        }

        // get the last insert id from the master connection
        $sequenceName = $this->quoteSimpleTableName($sequenceName);

        $sql = <<<SQL
        SELECT {$sequenceName}.CURRVAL FROM DUAL
        SQL;

        return $this->db->useMaster(
            static fn(Connection $db) => $db->createCommand($sql)->queryScalar(),
        );
    }

    /**
     * Creates ColumnSchema instance.
     *
     * @param array $column Column metadata.
     *
     * @return T The column schema instance.
     */
    protected function createColumn($column)
    {
        $c = $this->createColumnSchema();

        $c->name = $column['column_name'];
        $c->allowNull = $column['nullable'] === 'Y';
        $c->comment = $column['column_comment'] === null ? '' : $column['column_comment'];
        $c->isPrimaryKey = false;
        $c->dbType = $column['data_type'];
        // store raw default for deferred resolution in `loadTableSchema()`, where `isPrimaryKey` is known
        $c->defaultValue = $column['data_default'] ?? null;

        $c->size = trim((string) $column['data_length']) === '' ? null : (int) $column['data_length'];
        $c->precision = trim((string) $column['data_precision']) === '' ? null : (int) $column['data_precision'];
        $c->scale = trim((string) $column['data_scale']) === '' ? null : (int) $column['data_scale'];

        $c->resolveType($column['data_type']);
        $c->phpType = $c->resolvePhpType();

        return $c;
    }

    /**
     * Finds constraints and fills them into TableSchema object passed.
     *
     * @param TableSchema $table The table schema.
     *
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_CONS_COLUMNS.html
     * @see https://docs.oracle.com/en/database/oracle/oracle-database/19/refrn/ALL_CONSTRAINTS.html
     */
    protected function findConstraints($table)
    {
        $sql = <<<SQL
            SELECT
                "d"."CONSTRAINT_NAME" AS "constraint_name",
                "d"."CONSTRAINT_TYPE" AS "constraint_type",
                "c"."COLUMN_NAME" AS "column_name",
                "c"."POSITION" AS "position",
                "d"."R_CONSTRAINT_NAME" AS "r_constraint_name",
                "e"."TABLE_NAME" AS "table_ref",
                "f"."COLUMN_NAME" AS "column_ref",
                "c"."TABLE_NAME" AS "table_name"
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
        $rows = $this->normalizePdoRowKeyCase($command->queryAll(), true);

        foreach ($rows as $row) {
            if ($row['constraint_type'] === 'P') {
                $table->columns[$row['column_name']]->isPrimaryKey = true;
                $table->primaryKey[] = $row['column_name'];

                if (empty($table->sequenceName)) {
                    $table->sequenceName = $this->getTableSequenceName($table->name, $table->schemaName);
                }
            }

            if ($row['constraint_type'] !== 'R') {
                // this condition is not checked in SQL WHERE because of an Oracle Bug:
                // see https://github.com/yiisoft/yii2/pull/8844
                continue;
            }

            $name = $row['constraint_name'];

            if (!isset($constraints[$name])) {
                $constraints[$name] = [
                    'tableName' => $row['table_ref'],
                    'columns' => [],
                ];
            }

            $constraints[$name]['columns'][$row['column_name']] = $row['column_ref'];
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
     *
     * Each array element is of the following structure:
     *
     * ```
     * [
     *     'IndexName1' => ['col1' [, ...]],
     *     'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * @param TableSchema $table The table metadata.
     *
     * @return array All unique indexes for the given table.
     *
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
                    $returnParams[$phName]['dataType'] = PDO::PARAM_STR;
                } else {
                    $returnParams[$phName]['dataType'] = PDO::PARAM_INT;
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
        $parts = $this->splitQuotedName($name, '"', '"');

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
     * @param string $tableName Table name.
     * @param MetadataType $returnType Return type.
     *
     * @return mixed Constraints.
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
