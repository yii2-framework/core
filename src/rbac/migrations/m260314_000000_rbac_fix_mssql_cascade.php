<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

use yii\base\InvalidConfigException;
use yii\db\Migration;
use yii\db\Query;
use yii\rbac\DbManager;

/**
 * Fix MSSQL RBAC cascade triggers.
 *
 * - auth_item DELETE trigger: also cascade to auth_assignment.
 * - auth_item UPDATE trigger: also cascade to auth_assignment and auth_item_child.parent;
 *   multi-row safe for non-name-change updates (needed by auth_rule triggers).
 * - auth_rule DELETE/UPDATE triggers (new): cascade to auth_item.rule_name.
 *
 * @see https://github.com/yiisoft/yii2/pull/15098
 *
 * @since 2.0.55
 */
class m260314_000000_rbac_fix_mssql_cascade extends Migration
{
    /**
     * @throws yii\base\InvalidConfigException
     * @return DbManager
     */
    protected function getAuthManager()
    {
        $authManager = Yii::$app->getAuthManager();
        if (!$authManager instanceof DbManager) {
            throw new InvalidConfigException(
                'You should configure "authManager" component to use database before executing this migration.',
            );
        }

        return $authManager;
    }

    /**
     * @return bool
     */
    protected function isMSSQL()
    {
        return $this->db->driverName === 'mssql'
            || $this->db->driverName === 'sqlsrv'
            || $this->db->driverName === 'dblib';
    }

    protected function findForeignKeyName($table, $column, $referenceTable, $referenceColumn): bool|int|string|null
    {
        return (new Query())
            ->select(['OBJECT_NAME(fkc.constraint_object_id)'])
            ->from(['fkc' => 'sys.foreign_key_columns'])
            ->innerJoin(
                ['c' => 'sys.columns'],
                'fkc.parent_object_id = c.object_id AND fkc.parent_column_id = c.column_id',
            )
            ->innerJoin(
                ['r' => 'sys.columns'],
                'fkc.referenced_object_id = r.object_id AND fkc.referenced_column_id = r.column_id',
            )
            ->andWhere(
                'fkc.parent_object_id=OBJECT_ID(:fkc_parent_object_id)',
                [':fkc_parent_object_id' => $this->db->schema->getRawTableName($table)],
            )
            ->andWhere(
                'fkc.referenced_object_id=OBJECT_ID(:fkc_referenced_object_id)',
                [':fkc_referenced_object_id' => $this->db->schema->getRawTableName($referenceTable)],
            )
            ->andWhere(['c.name' => $column])
            ->andWhere(['r.name' => $referenceColumn])
            ->scalar($this->db);
    }

    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        if (!$this->isMSSQL()) {
            return;
        }

        $authManager = $this->getAuthManager();

        $this->db = $authManager->db;

        $schema = $this->db->getSchema()->defaultSchema;

        $itemChildSuffix = $this->db->schema->getRawTableName($authManager->itemChildTable);
        $ruleSuffix = $this->db->schema->getRawTableName($authManager->ruleTable);

        // Drop existing auth_item triggers
        $this->execute(
            <<<SQL
            IF (OBJECT_ID(N'{$schema}.trigger_delete_{$itemChildSuffix}') IS NOT NULL) DROP TRIGGER {$schema}.trigger_delete_{$itemChildSuffix};
            SQL,
        );
        $this->execute(
            <<<SQL
            IF (OBJECT_ID(N'{$schema}.trigger_update_{$itemChildSuffix}') IS NOT NULL) DROP TRIGGER {$schema}.trigger_update_{$itemChildSuffix}
            SQL,
        );

        // Find FK constraint names
        $childFk = $this->findForeignKeyName(
            $authManager->itemChildTable,
            'child',
            $authManager->itemTable,
            'name',
        );
        $parentFk = $this->findForeignKeyName(
            $authManager->itemChildTable,
            'parent',
            $authManager->itemTable,
            'name',
        );
        $assignmentFk = $this->findForeignKeyName(
            $authManager->assignmentTable,
            'item_name',
            $authManager->itemTable,
            'name',
        );
        $ruleFk = $this->findForeignKeyName(
            $authManager->itemTable,
            'rule_name',
            $authManager->ruleTable,
            'name',
        );

        // auth_item INSTEAD OF DELETE: cascade to item_child + assignment
        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_delete_{$itemChildSuffix}
            ON {$schema}.{$authManager->itemTable}
            INSTEAD OF DELETE
            AS
            BEGIN
                DELETE FROM {$schema}.{$authManager->assignmentTable} WHERE [item_name] IN (SELECT [name] FROM deleted);
                DELETE FROM {$schema}.{$authManager->itemChildTable} WHERE [parent] IN (SELECT [name] FROM deleted) OR [child] IN (SELECT [name] FROM deleted);
                DELETE FROM {$schema}.{$authManager->itemTable} WHERE [name] IN (SELECT [name] FROM deleted);
            END
            SQL,
        );

        // auth_item INSTEAD OF UPDATE: two modes
        //   1. Single-row name change  → NOCHECK FKs, cascade to child/parent/assignment
        //   2. Multi-row column update  → JOIN-based update (used by auth_rule triggers)
        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_update_{$itemChildSuffix}
            ON {$schema}.{$authManager->itemTable}
            INSTEAD OF UPDATE
            AS
            BEGIN
                DECLARE @name_changed BIT = 0
                DECLARE @old_name NVARCHAR(64)
                DECLARE @new_name NVARCHAR(64)

                IF (SELECT COUNT(*) FROM deleted) = 1
                BEGIN
                    SELECT @old_name = d.[name], @new_name = i.[name]
                    FROM deleted d CROSS JOIN inserted i
                    IF @old_name <> @new_name SET @name_changed = 1
                END

                IF @name_changed = 1
                BEGIN
                    ALTER TABLE {$authManager->itemChildTable} NOCHECK CONSTRAINT {$childFk};
                    ALTER TABLE {$authManager->itemChildTable} NOCHECK CONSTRAINT {$parentFk};
                    ALTER TABLE {$authManager->assignmentTable} NOCHECK CONSTRAINT {$assignmentFk};
                    UPDATE {$authManager->itemChildTable} SET [child] = @new_name WHERE [child] = @old_name;
                    UPDATE {$authManager->itemChildTable} SET [parent] = @new_name WHERE [parent] = @old_name;
                    UPDATE {$authManager->assignmentTable} SET [item_name] = @new_name WHERE [item_name] = @old_name;
                    UPDATE {$authManager->itemTable}
                    SET [name] = @new_name,
                        [type] = (SELECT [type] FROM inserted),
                        [description] = (SELECT [description] FROM inserted),
                        [rule_name] = (SELECT [rule_name] FROM inserted),
                        [data] = (SELECT [data] FROM inserted),
                        [created_at] = (SELECT [created_at] FROM inserted),
                        [updated_at] = (SELECT [updated_at] FROM inserted)
                    WHERE [name] = @old_name
                    ALTER TABLE {$authManager->itemChildTable} CHECK CONSTRAINT {$childFk};
                    ALTER TABLE {$authManager->itemChildTable} CHECK CONSTRAINT {$parentFk};
                    ALTER TABLE {$authManager->assignmentTable} CHECK CONSTRAINT {$assignmentFk};
                END
                ELSE
                BEGIN
                    UPDATE t
                    SET t.[type] = i.[type],
                        t.[description] = i.[description],
                        t.[rule_name] = i.[rule_name],
                        t.[data] = i.[data],
                        t.[created_at] = i.[created_at],
                        t.[updated_at] = i.[updated_at]
                    FROM {$authManager->itemTable} t
                    INNER JOIN deleted d ON t.[name] = d.[name]
                    INNER JOIN inserted i ON i.[name] = d.[name]
                END
            END
            SQL,
        );

        // auth_rule INSTEAD OF DELETE: SET NULL auth_item.rule_name, then delete rule
        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_delete_{$ruleSuffix}
            ON {$schema}.{$authManager->ruleTable}
            INSTEAD OF DELETE
            AS
            BEGIN
                UPDATE {$schema}.{$authManager->itemTable} SET [rule_name] = NULL WHERE [rule_name] IN (SELECT [name] FROM deleted);
                DELETE FROM {$schema}.{$authManager->ruleTable} WHERE [name] IN (SELECT [name] FROM deleted);
            END
            SQL,
        );

        // auth_rule INSTEAD OF UPDATE: cascade name change to auth_item.rule_name
        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_update_{$ruleSuffix}
            ON {$schema}.{$authManager->ruleTable}
            INSTEAD OF UPDATE
            AS
                DECLARE @old_name NVARCHAR(64) = (SELECT [name] FROM deleted)
                DECLARE @new_name NVARCHAR(64) = (SELECT [name] FROM inserted)
            BEGIN
                IF @old_name <> @new_name
                BEGIN
                    ALTER TABLE {$authManager->itemTable} NOCHECK CONSTRAINT {$ruleFk};
                    UPDATE {$authManager->itemTable} SET [rule_name] = @new_name WHERE [rule_name] = @old_name;
                END
                UPDATE {$authManager->ruleTable}
                SET [name] = (SELECT [name] FROM inserted),
                    [data] = (SELECT [data] FROM inserted),
                    [created_at] = (SELECT [created_at] FROM inserted),
                    [updated_at] = (SELECT [updated_at] FROM inserted)
                WHERE [name] IN (SELECT [name] FROM deleted)
                IF @old_name <> @new_name
                BEGIN
                    ALTER TABLE {$authManager->itemTable} CHECK CONSTRAINT {$ruleFk};
                END
            END
            SQL,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        if (!$this->isMSSQL()) {
            return;
        }

        $authManager = $this->getAuthManager();

        $this->db = $authManager->db;

        $schema = $this->db->getSchema()->defaultSchema;

        $itemChildSuffix = $this->db->schema->getRawTableName($authManager->itemChildTable);
        $ruleSuffix = $this->db->schema->getRawTableName($authManager->ruleTable);

        // Drop all triggers
        $this->execute(
            <<<SQL
            IF (OBJECT_ID(N'{$schema}.trigger_delete_{$ruleSuffix}') IS NOT NULL) DROP TRIGGER {$schema}.trigger_delete_{$ruleSuffix}
            SQL,
        );
        $this->execute(
            <<<SQL
            IF (OBJECT_ID(N'{$schema}.trigger_update_{$ruleSuffix}') IS NOT NULL) DROP TRIGGER {$schema}.trigger_update_{$ruleSuffix}
            SQL,
        );
        $this->execute(
            <<<SQL
            IF (OBJECT_ID(N'{$schema}.trigger_delete_{$itemChildSuffix}') IS NOT NULL) DROP TRIGGER {$schema}.trigger_delete_{$itemChildSuffix}
            SQL,
        );
        $this->execute(
            <<<SQL
            IF (OBJECT_ID(N'{$schema}.trigger_update_{$itemChildSuffix}') IS NOT NULL) DROP TRIGGER {$schema}.trigger_update_{$itemChildSuffix}
            SQL,
        );

        // Restore previous auth_item triggers (from m200409_110543_rbac_update_mssql_trigger)
        $childFk = $this->findForeignKeyName(
            $authManager->itemChildTable,
            'child',
            $authManager->itemTable,
            'name',
        );

        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_delete_{$itemChildSuffix}
            ON {$schema}.{$authManager->itemTable}
            INSTEAD OF DELETE
            AS
            BEGIN
                DELETE FROM {$schema}.{$authManager->itemChildTable} WHERE parent IN (SELECT name FROM deleted) OR child IN (SELECT name FROM deleted);
                DELETE FROM {$schema}.{$authManager->itemTable} WHERE name IN (SELECT name FROM deleted);
            END;
            SQL
        );

        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_update_{$itemChildSuffix}
            ON {$schema}.{$authManager->itemTable}
            INSTEAD OF UPDATE
            AS
                DECLARE @old_name NVARCHAR(64) = (SELECT name FROM deleted)
                DECLARE @new_name NVARCHAR(64) = (SELECT name FROM inserted)
            BEGIN
                IF @old_name <> @new_name
                BEGIN
                    ALTER TABLE {$authManager->itemChildTable} NOCHECK CONSTRAINT {$childFk};
                    UPDATE {$authManager->itemChildTable} SET child = @new_name WHERE child = @old_name;
                END
                UPDATE {$authManager->itemTable}
                SET name = (SELECT name FROM inserted),
                    type = (SELECT type FROM inserted),
                    description = (SELECT description FROM inserted),
                    rule_name = (SELECT rule_name FROM inserted),
                    data = (SELECT data FROM inserted),
                    created_at = (SELECT created_at FROM inserted),
                    updated_at = (SELECT updated_at FROM inserted)
                WHERE name IN (SELECT name FROM deleted)
                IF @old_name <> @new_name
                BEGIN
                    ALTER TABLE {$authManager->itemChildTable} CHECK CONSTRAINT {$childFk};
                END
            END;
            SQL
        );
    }
}
