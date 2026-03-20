<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db\providers;

use yii\db\ColumnSchemaBuilder;
use yii\db\Schema;

/**
 * Base data provider for column type test cases.
 *
 * Provides representative input/output pairs for `QueryBuilder::getColumnType()` and `ColumnSchemaBuilder::__toString()`
 * across all database drivers. Driver-specific providers extend this class to add or override test cases.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class ColumnTypeProvider
{
    public static function columnTypes(): array
    {
        return [
            'bigint' => [
                Schema::TYPE_BIGINT,
                static fn($t): ColumnSchemaBuilder => $t->bigInteger(),
                [
                    'mysql' => 'bigint',
                    'oci' => 'NUMBER(20)',
                    'pgsql' => 'bigint',
                    'sqlite' => 'bigint',
                    'sqlsrv' => 'bigint',
                ],
            ],
            'bigint check' => [
                Schema::TYPE_BIGINT . ' CHECK (value > 5)',
                static fn($t): ColumnSchemaBuilder => $t->bigInteger()->check('value > 5'),
                [
                    'mysql' => 'bigint CHECK (value > 5)',
                    'oci' => 'NUMBER(20) CHECK (value > 5)',
                    'pgsql' => 'bigint CHECK (value > 5)',
                    'sqlite' => 'bigint CHECK (value > 5)',
                    'sqlsrv' => 'bigint CHECK (value > 5)',
                ],
            ],
            'bigint not null' => [
                Schema::TYPE_BIGINT . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->bigInteger()->notNull(),
                [
                    'mysql' => 'bigint NOT NULL',
                    'oci' => 'NUMBER(20) NOT NULL',
                    'pgsql' => 'bigint NOT NULL',
                    'sqlite' => 'bigint NOT NULL',
                    'sqlsrv' => 'bigint NOT NULL',
                ],
            ],
           'bigint(8)' => [
                Schema::TYPE_BIGINT . '(8)',
                static fn($t): ColumnSchemaBuilder => $t->bigInteger(8),
                [
                    'mysql' => 'bigint',
                    'pgsql' => 'bigint',
                    'sqlite' => 'bigint',
                    'oci' => 'NUMBER(8)',
                    'sqlsrv' => 'bigint',
                ],
            ],
            'bigint(8) check' => [
                Schema::TYPE_BIGINT . '(8) CHECK (value > 5)',
                static fn($t): ColumnSchemaBuilder => $t->bigInteger(8)->check('value > 5'),
                [
                    'mysql' => 'bigint CHECK (value > 5)',
                    'oci' => 'NUMBER(8) CHECK (value > 5)',
                    'pgsql' => 'bigint CHECK (value > 5)',
                    'sqlite' => 'bigint CHECK (value > 5)',
                    'sqlsrv' => 'bigint CHECK (value > 5)',
                ],
            ],
            'bigpk' => [
                Schema::TYPE_BIGPK,
                static fn($t): ColumnSchemaBuilder => $t->bigPrimaryKey(),
                [
                    'mysql' => 'bigint NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'bigserial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            'binary' => [
                Schema::TYPE_BINARY,
                static fn($t): ColumnSchemaBuilder => $t->binary(),
                [
                    'mysql' => 'blob',
                    'oci' => 'BLOB',
                    'pgsql' => 'bytea',
                    'sqlite' => 'blob',
                    'sqlsrv' => 'varbinary(max)',
                ],
            ],
            'boolean' => [
                Schema::TYPE_BOOLEAN,
                static fn($t): ColumnSchemaBuilder => $t->boolean(),
                [
                    'mysql' => 'tinyint(1)',
                    'oci' => 'NUMBER(1)',
                    'pgsql' => 'boolean',
                    'sqlite' => 'boolean',
                    'sqlsrv' => 'bit',
                ],
            ],
            'boolean not null default 1' => [
                Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 1',
                static fn($t): ColumnSchemaBuilder => $t->boolean()->notNull()->defaultValue(1),
                [
                    'mysql' => 'tinyint(1) NOT NULL DEFAULT 1',
                    'sqlite' => 'boolean NOT NULL DEFAULT 1',
                    'sqlsrv' => 'bit NOT NULL DEFAULT 1',
                ],
            ],
            'char' => [
                Schema::TYPE_CHAR,
                static fn($t): ColumnSchemaBuilder => $t->char(),
                [
                    'mysql' => 'char(1)',
                    'oci' => 'CHAR(1)',
                    'pgsql' => 'char(1)',
                    'sqlite' => 'char(1)',
                ],
            ],
            'char check (double quotes)' => [
                Schema::TYPE_CHAR . ' CHECK (value LIKE "test%")',
                static fn($t): ColumnSchemaBuilder => $t->char()->check('value LIKE "test%"'),
                [
                    'mysql' => 'char(1) CHECK (value LIKE "test%")',
                    'sqlite' => 'char(1) CHECK (value LIKE "test%")',
                ],
            ],
            'char not null' => [
                Schema::TYPE_CHAR . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->char()->notNull(),
                [
                    'mysql' => 'char(1) NOT NULL',
                    'oci' => 'CHAR(1) NOT NULL',
                    'pgsql' => 'char(1) NOT NULL',
                    'sqlite' => 'char(1) NOT NULL',
                ],
            ],
            'char(6)' => [
                Schema::TYPE_CHAR . '(6)',
                static fn($t): ColumnSchemaBuilder => $t->char(6),
                [
                    'mysql' => 'char(6)',
                    'oci' => 'CHAR(6)',
                    'pgsql' => 'char(6)',
                    'sqlite' => 'char(6)',
                ],
            ],
            'char(6) check (double quotes)' => [
                Schema::TYPE_CHAR .
                '(6) CHECK (value LIKE "test%")',
                static fn($t): ColumnSchemaBuilder => $t->char(6)->check('value LIKE "test%"'),
                [
                    'mysql' => 'char(6) CHECK (value LIKE "test%")',
                    'sqlite' => 'char(6) CHECK (value LIKE "test%")',
                ],
            ],
            'date' => [
                Schema::TYPE_DATE,
                static fn($t): ColumnSchemaBuilder => $t->date(),
                [
                    'mysql' => 'date',
                    'oci' => 'DATE',
                    'pgsql' => 'date',
                    'sqlite' => 'date',
                    'sqlsrv' => 'date',
                ],
            ],
            'date not null' => [
                Schema::TYPE_DATE . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->date()->notNull(),
                [
                    'mysql' => 'date NOT NULL',
                    'oci' => 'DATE NOT NULL',
                    'pgsql' => 'date NOT NULL',
                    'sqlite' => 'date NOT NULL',
                    'sqlsrv' => 'date NOT NULL',
                ],
            ],
            'datetime' => [
                Schema::TYPE_DATETIME,
                static fn($t): ColumnSchemaBuilder => $t->dateTime(),
                [
                    'oci' => 'TIMESTAMP',
                    'pgsql' => 'timestamp(0)',
                    'sqlite' => 'datetime',
                    'sqlsrv' => 'datetime',
                ],
            ],
            'datetime not null' => [
                Schema::TYPE_DATETIME . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->dateTime()->notNull(),
                [
                    'oci' => 'TIMESTAMP NOT NULL',
                    'pgsql' => 'timestamp(0) NOT NULL',
                    'sqlite' => 'datetime NOT NULL',
                    'sqlsrv' => 'datetime NOT NULL',
                ],
            ],
            'decimal' => [
                Schema::TYPE_DECIMAL,
                static fn($t): ColumnSchemaBuilder => $t->decimal(),
                [
                    'mysql' => 'decimal(10,0)',
                    'oci' => 'NUMBER',
                    'pgsql' => 'numeric(10,0)',
                    'sqlite' => 'decimal(10,0)',
                    'sqlsrv' => 'decimal(18,0)',
                ],
            ],
            'decimal check' => [
                Schema::TYPE_DECIMAL . ' CHECK (value > 5.6)',
                static fn($t): ColumnSchemaBuilder => $t->decimal()->check('value > 5.6'),
                [
                    'mysql' => 'decimal(10,0) CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'pgsql' => 'numeric(10,0) CHECK (value > 5.6)',
                    'sqlite' => 'decimal(10,0) CHECK (value > 5.6)',
                    'sqlsrv' => 'decimal(18,0) CHECK (value > 5.6)',
                ],
            ],
            'decimal not null' => [
                Schema::TYPE_DECIMAL . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->decimal()->notNull(),
                [
                    'mysql' => 'decimal(10,0) NOT NULL',
                    'oci' => 'NUMBER NOT NULL',
                    'pgsql' => 'numeric(10,0) NOT NULL',
                    'sqlite' => 'decimal(10,0) NOT NULL',
                    'sqlsrv' => 'decimal(18,0) NOT NULL',
                ],
            ],
            'decimal(12,4)' => [
                Schema::TYPE_DECIMAL . '(12,4)',
                static fn($t): ColumnSchemaBuilder => $t->decimal(12, 4),
                [
                    'mysql' => 'decimal(12,4)',
                    'oci' => 'NUMBER',
                    'pgsql' => 'numeric(12,4)',
                    'sqlite' => 'decimal(12,4)',
                    'sqlsrv' => 'decimal(12,4)',
                ],
            ],
            'decimal(12,4) check' => [
                Schema::TYPE_DECIMAL . '(12,4) CHECK (value > 5.6)',
                static fn($t): ColumnSchemaBuilder => $t->decimal(12, 4)->check('value > 5.6'),
                [
                    'mysql' => 'decimal(12,4) CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'pgsql' => 'numeric(12,4) CHECK (value > 5.6)',
                    'sqlite' => 'decimal(12,4) CHECK (value > 5.6)',
                    'sqlsrv' => 'decimal(12,4) CHECK (value > 5.6)',
                ],
            ],
            'double' => [
                Schema::TYPE_DOUBLE,
                static fn($t): ColumnSchemaBuilder => $t->double(),
                [
                    'mysql' => 'double',
                    'oci' => 'NUMBER',
                    'pgsql' => 'double precision',
                    'sqlite' => 'double',
                    'sqlsrv' => 'float',
                ],
            ],
            'double check' => [
                Schema::TYPE_DOUBLE . ' CHECK (value > 5.6)',
                static fn($t): ColumnSchemaBuilder => $t->double()->check('value > 5.6'),
                [
                    'mysql' => 'double CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'double CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            'double not null' => [
                Schema::TYPE_DOUBLE . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->double()->notNull(),
                [
                    'mysql' => 'double NOT NULL',
                    'oci' => 'NUMBER NOT NULL',
                    'pgsql' => 'double precision NOT NULL',
                    'sqlite' => 'double NOT NULL',
                    'sqlsrv' => 'float NOT NULL',
                ],
            ],
            'double(16)' => [
                Schema::TYPE_DOUBLE . '(16)',
                static fn($t): ColumnSchemaBuilder => $t->double(16),
                [
                    'mysql' => 'double',
                    'oci' => 'NUMBER',
                    'sqlite' => 'double',
                    'sqlsrv' => 'float',
                ],
            ],
            'double(16) check' => [
                Schema::TYPE_DOUBLE . '(16) CHECK (value > 5.6)',
                static fn($t): ColumnSchemaBuilder => $t->double(16)->check('value > 5.6'),
                [
                    'mysql' => 'double CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'double CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            'float' => [
                Schema::TYPE_FLOAT,
                static fn($t): ColumnSchemaBuilder => $t->float(),
                [
                    'mysql' => 'float',
                    'oci' => 'NUMBER',
                    'pgsql' => 'double precision',
                    'sqlite' => 'float',
                    'sqlsrv' => 'float',
                ],
            ],
            'float check' => [
                Schema::TYPE_FLOAT . ' CHECK (value > 5.6)',
                static fn($t): ColumnSchemaBuilder => $t->float()->check('value > 5.6'),
                [
                    'mysql' => 'float CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'float CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            'float not null' => [
                Schema::TYPE_FLOAT . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->float()->notNull(),
                [
                    'mysql' => 'float NOT NULL',
                    'oci' => 'NUMBER NOT NULL',
                    'pgsql' => 'double precision NOT NULL',
                    'sqlite' => 'float NOT NULL',
                    'sqlsrv' => 'float NOT NULL',
                ],
            ],
            'float(16)' => [
                Schema::TYPE_FLOAT . '(16)',
                static fn($t): ColumnSchemaBuilder => $t->float(16),
                [
                    'mysql' => 'float',
                    'oci' => 'NUMBER',
                    'sqlite' => 'float',
                    'sqlsrv' => 'float',
                ],
            ],
            'float(16) check' => [
                Schema::TYPE_FLOAT . '(16) CHECK (value > 5.6)',
                static fn($t): ColumnSchemaBuilder => $t->float(16)->check('value > 5.6'),
                [
                    'mysql' => 'float CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'float CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            'integer' => [
                Schema::TYPE_INTEGER,
                static fn($t): ColumnSchemaBuilder => $t->integer(),
                [
                    'mysql' => 'int',
                    'oci' => 'NUMBER(10)',
                    'pgsql' => 'integer',
                    'sqlite' => 'integer',
                    'sqlsrv' => 'int',
                ],
            ],
            'integer check' => [
                Schema::TYPE_INTEGER . ' CHECK (value > 5)',
                static fn($t): ColumnSchemaBuilder => $t->integer()->check('value > 5'),
                [
                    'mysql' => 'int CHECK (value > 5)',
                    'oci' => 'NUMBER(10) CHECK (value > 5)',
                    'pgsql' => 'integer CHECK (value > 5)',
                    'sqlite' => 'integer CHECK (value > 5)',
                    'sqlsrv' => 'int CHECK (value > 5)',
                ],
            ],
            'integer comment' => [
                Schema::TYPE_INTEGER . " COMMENT 'test comment'",
                static fn($t): ColumnSchemaBuilder => $t->integer()->comment('test comment'),
                [
                    'mysql' => "int COMMENT 'test comment'",
                    'sqlsrv' => 'int',
                ],
                ['sqlsrv' => 'integer'],
            ],
            'integer first' => [
                Schema::TYPE_INTEGER . ' FIRST',
                static fn($t): ColumnSchemaBuilder => $t->integer()->first(),
                [
                    'mysql' => 'int FIRST',
                    'sqlsrv' => 'int',
                ],
                [
                    'oci' => 'NUMBER(10)',
                    'pgsql' => 'integer',
                    'sqlsrv' => 'integer',
                ],
            ],
            'integer not null' => [
                Schema::TYPE_INTEGER . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->integer()->notNull(),
                [
                    'mysql' => 'int NOT NULL',
                    'oci' => 'NUMBER(10) NOT NULL',
                    'pgsql' => 'integer NOT NULL',
                    'sqlite' => 'integer NOT NULL',
                    'sqlsrv' => 'int NOT NULL',
                ],
            ],
            'integer not null first' => [
                Schema::TYPE_INTEGER . ' NOT NULL FIRST',
                static fn($t): ColumnSchemaBuilder => $t->integer()->append('NOT NULL')->first(),
                [
                    'mysql' => 'int NOT NULL FIRST',
                    'sqlsrv' => 'int NOT NULL',
                ],
                [
                    'oci' => 'NUMBER(10) NOT NULL',
                    'sqlsrv' => 'integer NOT NULL',
                ],
            ],
            'integer(8)' => [
                Schema::TYPE_INTEGER . '(8)',
                static fn($t): ColumnSchemaBuilder => $t->integer(8),
                [
                    'mysql' => 'int',
                    'oci' => 'NUMBER(8)',
                    'pgsql' => 'integer',
                    'sqlite' => 'integer',
                    'sqlsrv' => 'int',
                ],
            ],
            'integer(8) check' => [
                Schema::TYPE_INTEGER . '(8) CHECK (value > 5)',
                static fn($t): ColumnSchemaBuilder => $t->integer(8)->check('value > 5'),
                [
                    'mysql' => 'int CHECK (value > 5)',
                    'oci' => 'NUMBER(8) CHECK (value > 5)',
                    'pgsql' => 'integer CHECK (value > 5)',
                    'sqlite' => 'integer CHECK (value > 5)',
                    'sqlsrv' => 'int CHECK (value > 5)',
                ],
            ],
            'money' => [
                Schema::TYPE_MONEY,
                static fn($t): ColumnSchemaBuilder => $t->money(),
                [
                    'mysql' => 'decimal(19,4)',
                    'oci' => 'NUMBER(19,4)',
                    'pgsql' => 'numeric(19,4)',
                    'sqlite' => 'decimal(19,4)',
                    'sqlsrv' => 'decimal(19,4)',
                ],
            ],
            'money check' => [
                Schema::TYPE_MONEY . ' CHECK (value > 0.0)',
                static fn($t): ColumnSchemaBuilder => $t->money()->check('value > 0.0'),
                [
                    'mysql' => 'decimal(19,4) CHECK (value > 0.0)',
                    'oci' => 'NUMBER(19,4) CHECK (value > 0.0)',
                    'pgsql' => 'numeric(19,4) CHECK (value > 0.0)',
                    'sqlite' => 'decimal(19,4) CHECK (value > 0.0)',
                    'sqlsrv' => 'decimal(19,4) CHECK (value > 0.0)',
                ],
            ],
            'money not null' => [
                Schema::TYPE_MONEY . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->money()->notNull(),
                [
                    'mysql' => 'decimal(19,4) NOT NULL',
                    'oci' => 'NUMBER(19,4) NOT NULL',
                    'pgsql' => 'numeric(19,4) NOT NULL',
                    'sqlite' => 'decimal(19,4) NOT NULL',
                    'sqlsrv' => 'decimal(19,4) NOT NULL',
                ],
            ],
            'money(16,2)' => [
                Schema::TYPE_MONEY . '(16,2)',
                static fn($t): ColumnSchemaBuilder => $t->money(16, 2),
                [
                    'mysql' => 'decimal(16,2)',
                    'oci' => 'NUMBER(16,2)',
                    'pgsql' => 'numeric(16,2)',
                    'sqlite' => 'decimal(16,2)',
                    'sqlsrv' => 'decimal(16,2)',
                ],
            ],
            'money(16,2) check' => [
                Schema::TYPE_MONEY . '(16,2) CHECK (value > 0.0)',
                static fn($t): ColumnSchemaBuilder => $t->money(16, 2)->check('value > 0.0'),
                [
                    'mysql' => 'decimal(16,2) CHECK (value > 0.0)',
                    'oci' => 'NUMBER(16,2) CHECK (value > 0.0)',
                    'pgsql' => 'numeric(16,2) CHECK (value > 0.0)',
                    'sqlite' => 'decimal(16,2) CHECK (value > 0.0)',
                    'sqlsrv' => 'decimal(16,2) CHECK (value > 0.0)',
                ],
            ],
            'pk' => [
                Schema::TYPE_PK,
                static fn($t): ColumnSchemaBuilder => $t->primaryKey(),
                [
                    'mysql' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'oci' => 'NUMBER(10) NOT NULL PRIMARY KEY',
                    'pgsql' => 'serial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
            ],
            'pk check' => [
                Schema::TYPE_PK . ' CHECK (value > 5)',
                static fn($t): ColumnSchemaBuilder => $t->primaryKey()->check('value > 5'),
                [
                    'mysql' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY CHECK (value > 5)',
                    'oci' => 'NUMBER(10) NOT NULL PRIMARY KEY CHECK (value > 5)',
                    'pgsql' => 'serial NOT NULL PRIMARY KEY CHECK (value > 5)',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL CHECK (value > 5)',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY CHECK (value > 5)',
                ],
            ],
            'pk comment' => [
                Schema::TYPE_PK . " COMMENT 'test comment'",
                static fn($t): ColumnSchemaBuilder => $t->primaryKey()->comment('test comment'),
                [
                    'mysql' => "int NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'test comment'",
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
                ['sqlsrv' => 'pk'],
            ],
            'pk first' => [
                Schema::TYPE_PK . ' FIRST',
                static fn($t): ColumnSchemaBuilder => $t->primaryKey()->first(),
                [
                    'mysql' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
                [
                    'oci' => 'NUMBER(10) NOT NULL PRIMARY KEY',
                    'sqlsrv' => 'pk',
                ],
            ],
            'pk(8)' => [
                Schema::TYPE_PK . '(8)',
                static fn($t): ColumnSchemaBuilder => $t->primaryKey(8),
                [
                    'mysql' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'oci' => 'NUMBER(8) NOT NULL PRIMARY KEY',
                ],
            ],
            'pk(8) check' => [
                Schema::TYPE_PK . '(8) CHECK (value > 5)',
                static fn($t): ColumnSchemaBuilder => $t->primaryKey(8)->check('value > 5'),
                [
                    'mysql' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY CHECK (value > 5)',
                    'oci' => 'NUMBER(8) NOT NULL PRIMARY KEY CHECK (value > 5)',
                ],
            ],
            'smallint' => [
                Schema::TYPE_SMALLINT,
                static fn($t): ColumnSchemaBuilder => $t->smallInteger(),
                [
                    'mysql' => 'smallint',
                    'oci' => 'NUMBER(5)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'smallint',
                    'sqlsrv' => 'smallint',
                ],
            ],
            'smallint(8)' => [
                Schema::TYPE_SMALLINT . '(8)',
                static fn($t): ColumnSchemaBuilder => $t->smallInteger(8),
                [
                    'mysql' => 'smallint',
                    'oci' => 'NUMBER(8)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'smallint',
                    'sqlsrv' => 'smallint',
                ],
            ],
            'string' => [
                Schema::TYPE_STRING,
                static fn($t): ColumnSchemaBuilder => $t->string(),
                [
                    'mysql' => 'varchar(255)',
                    'pgsql' => 'varchar(255)',
                    'sqlite' => 'varchar(255)',
                    'oci' => 'VARCHAR2(255)',
                    'sqlsrv' => 'nvarchar(255)',
                ],
            ],
            'string check (double quotes)' => [
                Schema::TYPE_STRING . " CHECK (value LIKE 'test%')",
                static fn($t): ColumnSchemaBuilder => $t->string()->check("value LIKE 'test%'"),
                [
                    'mysql' => "varchar(255) CHECK (value LIKE 'test%')",
                    'sqlite' => "varchar(255) CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(255) CHECK (value LIKE 'test%')",
                ],
            ],
            'string check (single quotes)' => [
                Schema::TYPE_STRING . ' CHECK (value LIKE \'test%\')',
                static fn($t): ColumnSchemaBuilder => $t->string()->check('value LIKE \'test%\''),
                [
                    'oci' => 'VARCHAR2(255) CHECK (value LIKE \'test%\')',
                    'pgsql' => 'varchar(255) CHECK (value LIKE \'test%\')',
                ],
            ],
            'string first' => [
                Schema::TYPE_STRING . ' FIRST',
                static fn($t): ColumnSchemaBuilder => $t->string()->first(),
                [
                    'mysql' => 'varchar(255) FIRST',
                    'sqlsrv' => 'nvarchar(255)',
                ],
                [
                    'oci' => 'VARCHAR2(255)',
                    'sqlsrv' => 'string',
                ],
            ],
            'string not null' => [
                Schema::TYPE_STRING . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->string()->notNull(),
                [
                    'mysql' => 'varchar(255) NOT NULL',
                    'oci' => 'VARCHAR2(255) NOT NULL',
                    'pgsql' => 'varchar(255) NOT NULL',
                    'sqlite' => 'varchar(255) NOT NULL',
                    'sqlsrv' => 'nvarchar(255) NOT NULL',
                ],
            ],
            'string not null first' => [
                Schema::TYPE_STRING . ' NOT NULL FIRST',
                static fn($t): ColumnSchemaBuilder => $t->string()->append('NOT NULL')->first(),
                [
                    'mysql' => 'varchar(255) NOT NULL FIRST',
                    'sqlsrv' => 'nvarchar(255) NOT NULL',
                ],
                [
                    'oci' => 'VARCHAR2(255) NOT NULL',
                    'sqlsrv' => 'string NOT NULL',
                ],
            ],
            'string(32)' => [
                Schema::TYPE_STRING . '(32)',
                static fn($t): ColumnSchemaBuilder => $t->string(32),
                [
                    'mysql' => 'varchar(32)',
                    'oci' => 'VARCHAR2(32)',
                    'pgsql' => 'varchar(32)',
                    'sqlite' => 'varchar(32)',
                    'sqlsrv' => 'nvarchar(32)',
                ],
            ],
            'string(32) check (double quotes)' => [
                Schema::TYPE_STRING . "(32) CHECK (value LIKE 'test%')",
                static fn($t): ColumnSchemaBuilder => $t->string(32)->check("value LIKE 'test%'"),
                [
                    'mysql' => "varchar(32) CHECK (value LIKE 'test%')",
                    'sqlite' => "varchar(32) CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(32) CHECK (value LIKE 'test%')",
                ],
            ],
            'string(32) check (single quotes)' => [
                Schema::TYPE_STRING . '(32) CHECK (value LIKE \'test%\')',
                static fn($t): ColumnSchemaBuilder => $t->string(32)->check('value LIKE \'test%\''),
                [
                    'oci' => 'VARCHAR2(32) CHECK (value LIKE \'test%\')',
                    'pgsql' => 'varchar(32) CHECK (value LIKE \'test%\')',
                ],
            ],
            'text' => [
                Schema::TYPE_TEXT,
                static fn($t): ColumnSchemaBuilder => $t->text(),
                [
                    'mysql' => 'text',
                    'oci' => 'CLOB',
                    'pgsql' => 'text',
                    'sqlite' => 'text',
                    'sqlsrv' => 'nvarchar(max)',
                ],
            ],
            'text check (double quotes)' => [
                Schema::TYPE_TEXT . " CHECK (value LIKE 'test%')",
                static fn($t): ColumnSchemaBuilder => $t->text()->check("value LIKE 'test%'"),
                [
                    'mysql' => "text CHECK (value LIKE 'test%')",
                    'sqlite' => "text CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(max) CHECK (value LIKE 'test%')",
                ],
            ],
            'text check (double quotes) with csb override' => [
                Schema::TYPE_TEXT . " CHECK (value LIKE 'test%')",
                static fn($t): ColumnSchemaBuilder => $t->text()->check("value LIKE 'test%'"),
                [
                    'mysql' => "text CHECK (value LIKE 'test%')",
                    'sqlite' => "text CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(max) CHECK (value LIKE 'test%')",
                ],
                Schema::TYPE_TEXT . " CHECK (value LIKE 'test%')",
            ],
            'text check (single quotes)' => [
                Schema::TYPE_TEXT . ' CHECK (value LIKE \'test%\')',
                static fn($t): ColumnSchemaBuilder => $t->text()->check('value LIKE \'test%\''),
                [
                    'oci' => 'CLOB CHECK (value LIKE \'test%\')',
                    'pgsql' => 'text CHECK (value LIKE \'test%\')',
                ],
            ],
            'text check (single quotes) with csb override' => [
                Schema::TYPE_TEXT . ' CHECK (value LIKE \'test%\')',
                static fn($t): ColumnSchemaBuilder => $t->text()->check('value LIKE \'test%\''),
                [
                    'oci' => 'CLOB CHECK (value LIKE \'test%\')',
                    'pgsql' => 'text CHECK (value LIKE \'test%\')',
                ],
                Schema::TYPE_TEXT . ' CHECK (value LIKE \'test%\')',
            ],
            'text not null' => [
                Schema::TYPE_TEXT . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->text()->notNull(),
                [
                    'mysql' => 'text NOT NULL',
                    'oci' => 'CLOB NOT NULL',
                    'pgsql' => 'text NOT NULL',
                    'sqlite' => 'text NOT NULL',
                    'sqlsrv' => 'nvarchar(max) NOT NULL',
                ],
            ],
            'text not null with csb override' => [
                Schema::TYPE_TEXT . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->text()->notNull(),
                [
                    'mysql' => 'text NOT NULL',
                    'oci' => 'CLOB NOT NULL',
                    'pgsql' => 'text NOT NULL',
                    'sqlite' => 'text NOT NULL',
                    'sqlsrv' => 'nvarchar(max) NOT NULL',
                ],
                Schema::TYPE_TEXT . ' NOT NULL',
            ],
            'text with csb override' => [
                Schema::TYPE_TEXT,
                static fn($t): ColumnSchemaBuilder => $t->text(),
                [
                    'mysql' => 'text',
                    'oci' => 'CLOB',
                    'pgsql' => 'text',
                    'sqlite' => 'text',
                    'sqlsrv' => 'nvarchar(max)',
                ],
                Schema::TYPE_TEXT,
            ],
            'time' => [
                Schema::TYPE_TIME,
                static fn($t): ColumnSchemaBuilder => $t->time(),
                [
                    'oci' => 'TIMESTAMP',
                    'pgsql' => 'time(0)',
                    'sqlite' => 'time',
                    'sqlsrv' => 'time',
                ],
            ],
            'time not null' => [
                Schema::TYPE_TIME . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->time()->notNull(),
                [
                    'oci' => 'TIMESTAMP NOT NULL',
                    'pgsql' => 'time(0) NOT NULL',
                    'sqlite' => 'time NOT NULL',
                    'sqlsrv' => 'time NOT NULL',
                ],
            ],
            'timestamp' => [
                Schema::TYPE_TIMESTAMP,
                static fn($t): ColumnSchemaBuilder => $t->timestamp(),
                [
                    'oci' => 'TIMESTAMP',
                    'pgsql' => 'timestamp(0)',
                    'sqlite' => 'timestamp',
                    'sqlsrv' => 'datetime',
                ],
            ],
            'timestamp not null' => [
                Schema::TYPE_TIMESTAMP . ' NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->timestamp()->notNull(),
                [
                    'oci' => 'TIMESTAMP NOT NULL',
                    'pgsql' => 'timestamp(0) NOT NULL',
                    'sqlite' => 'timestamp NOT NULL',
                    'sqlsrv' => 'datetime NOT NULL',
                ],
            ],
            'timestamp null default null' => [
                Schema::TYPE_TIMESTAMP . ' NULL DEFAULT NULL',
                static fn($t): ColumnSchemaBuilder => $t->timestamp()->defaultValue(null),
                [
                    'pgsql' => 'timestamp(0) NULL DEFAULT NULL',
                    'sqlite' => 'timestamp NULL DEFAULT NULL',
                    'sqlsrv' => 'datetime NULL DEFAULT NULL',
                ],
            ],
            'tinyint' => [
                Schema::TYPE_TINYINT,
                static fn($t): ColumnSchemaBuilder => $t->tinyInteger(),
                [
                    'mysql' => 'tinyint',
                    'oci' => 'NUMBER(3)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'tinyint',
                    'sqlsrv' => 'tinyint',
                ],
            ],
            'tinyint unsigned' => [
                Schema::TYPE_TINYINT . ' UNSIGNED',
                static fn($t): ColumnSchemaBuilder => $t->tinyInteger()->unsigned(),
                [
                    'mysql' => 'tinyint UNSIGNED',
                    'sqlite' => 'tinyint UNSIGNED',
                ],
            ],
            'tinyint(2)' => [
                Schema::TYPE_TINYINT . '(2)',
                static fn($t): ColumnSchemaBuilder => $t->tinyInteger(2),
                [
                    'mysql' => 'tinyint',
                    'oci' => 'NUMBER(2)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'tinyint',
                    'sqlsrv' => 'tinyint',
                ],
            ],
            'upk' => [
                Schema::TYPE_UPK,
                static fn($t): ColumnSchemaBuilder => $t->primaryKey()->unsigned(),
                [
                    'mysql' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'serial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            'ubigpk' => [
                Schema::TYPE_UBIGPK,
                static fn($t): ColumnSchemaBuilder => $t->bigPrimaryKey()->unsigned(),
                [
                    'mysql' => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'bigserial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
        ];
    }

}
