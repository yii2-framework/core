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
 * Cascade strategy for SQLite, which does not enforce foreign key constraints by default.
 *
 * Performs manual UPDATE on referencing rows before the parent row is renamed.
 * Because SQLite does not enforce FK constraints, the update order does not matter.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class SqliteCascadeStrategy implements CascadeStrategyInterface
{
    public function updateItem(
        Connection $db,
        string $oldName,
        string $newName,
        string $itemTable,
        string $itemChildTable,
        string $assignmentTable,
    ): callable|null {
        $db->createCommand()
            ->update(
                $itemChildTable,
                ['parent' => $newName],
                ['parent' => $oldName],
            )
            ->execute();
        $db->createCommand()
            ->update(
                $itemChildTable,
                ['child' => $newName],
                ['child' => $oldName],
            )
            ->execute();
        $db->createCommand()
            ->update(
                $assignmentTable,
                ['item_name' => $newName],
                ['item_name' => $oldName],
            )
            ->execute();

        return null;
    }

    public function updateRule(Connection $db, string $oldName, string $newName, string $itemTable): callable|null
    {
        $db->createCommand()
            ->update(
                $itemTable,
                ['rule_name' => $newName],
                ['rule_name' => $oldName],
            )
            ->execute();

        return null;
    }
}
