<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\rbac;

use yii\db\Connection;

/**
 * No-op cascade strategy for databases with native `ON UPDATE CASCADE` / `ON DELETE CASCADE` support.
 *
 * Suitable for MySQL and PostgreSQL.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class DefaultCascadeStrategy implements CascadeStrategyInterface
{
    public function updateItem(
        Connection $db,
        string $oldName,
        string $newName,
        string $itemTable,
        string $itemChildTable,
        string $assignmentTable,
    ): callable|null {
        return null;
    }

    public function updateRule(Connection $db, string $oldName, string $newName, string $itemTable): callable|null
    {
        return null;
    }
}
