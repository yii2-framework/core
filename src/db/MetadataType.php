<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db;

/**
 * Represents the types of table metadata that can be loaded and cached by the schema system.
 *
 * Used by {@see Schema::getTableMetadata()}, {@see Schema::getSchemaMetadata()}, and {@see Schema::setTableMetadata()}
 * to identify the kind of metadata being requested or stored, replacing the previous dynamic method dispatch via
 * `'loadTable' . ucfirst($type)`.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
enum MetadataType: string
{
    /**
     * Check constraints. Resolves to an array of {@see CheckConstraint} instances.
     */
    case CHECKS = 'checks';

    /**
     * Default value constraints. Resolves to an array of {@see DefaultValueConstraint} instances.
     */
    case DEFAULT_VALUES = 'defaultValues';

    /**
     * Foreign key constraints. Resolves to an array of {@see ForeignKeyConstraint} instances.
     */
    case FOREIGN_KEYS = 'foreignKeys';

    /**
     * Index constraints. Resolves to an array of {@see IndexConstraint} instances.
     */
    case INDEXES = 'indexes';

    /**
     * Primary key constraint. Resolves to a {@see Constraint} instance or `null`.
     */
    case PRIMARY_KEY = 'primaryKey';

    /**
     * Table schema metadata. Resolves to a {@see TableSchema} instance or `null`.
     */
    case SCHEMA = 'schema';

    /**
     * Unique constraints. Resolves to an array of {@see Constraint} instances.
     */
    case UNIQUES = 'uniques';
}
