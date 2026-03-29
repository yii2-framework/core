<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\rbac;

use yii\db\Connection;
use yii\db\Query;

/**
 * Cascade strategy for databases that enforce FK constraints but do not support `ON UPDATE CASCADE`.
 *
 * For renames: saves and deletes referencing rows, lets the parent row be renamed, then re-inserts with the new name.
 *
 * Suitable for Oracle and MSSQL.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class SoftCascadeStrategy implements CascadeStrategyInterface
{
    public function updateItem(
        Connection $db,
        string $oldName,
        string $newName,
        string $itemTable,
        string $itemChildTable,
        string $assignmentTable,
    ): callable|null {
        $childRows = (new Query())
            ->from($itemChildTable)
            ->where(['or', ['parent' => $oldName], ['child' => $oldName]])
            ->all($db);
        $assignmentRows = (new Query())
            ->from($assignmentTable)
            ->where(['item_name' => $oldName])
            ->all($db);

        if ($childRows === [] && $assignmentRows === []) {
            return null;
        }

        $db->createCommand()
            ->delete(
                $itemChildTable,
                ['or', ['parent' => $oldName], ['child' => $oldName]],
            )
            ->execute();
        $db->createCommand()
            ->delete(
                $assignmentTable,
                ['item_name' => $oldName],
            )
            ->execute();

        return static function () use (
                $db,
                $oldName,
                $newName,
                $itemChildTable,
                $assignmentTable,
                $childRows,
                $assignmentRows,
            ): void {
            foreach ($childRows as $row) {
                $db->createCommand()
                    ->insert(
                        $itemChildTable,
                        [
                            'parent' => $row['parent'] === $oldName ? $newName : $row['parent'],
                            'child' => $row['child'] === $oldName ? $newName : $row['child'],
                        ],
                    )->execute();
            }

            foreach ($assignmentRows as $row) {
                $db->createCommand()
                    ->insert(
                        $assignmentTable,
                        [
                            'item_name' => $newName,
                            'user_id' => $row['user_id'],
                            'created_at' => $row['created_at'],
                        ],
                    )->execute();
            }
        };
    }

    public function updateRule(Connection $db, string $oldName, string $newName, string $itemTable): callable|null
    {
        $affectedItems = (new Query())
            ->select(['name'])
            ->from($itemTable)
            ->where(['rule_name' => $oldName])
            ->column($db);

        $db->createCommand()
            ->update(
                $itemTable,
                ['rule_name' => null],
                ['rule_name' => $oldName],
            )
            ->execute();

        if ($affectedItems === []) {
            return null;
        }

        return $db->createCommand()
            ->update(
                $itemTable,
                ['rule_name' => $newName],
                ['name' => $affectedItems],
            )
            ->execute(...);
    }
}
