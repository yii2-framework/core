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
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
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

    protected function findForeignKeyName($table, $column, $referenceTable, $referenceColumn): string
    {
        $fkName = (new Query())
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

        if (!is_string($fkName) || $fkName === '') {
            throw new InvalidConfigException(
                "Unable to resolve foreign key for {$table}.{$column} -> {$referenceTable}.{$referenceColumn}.",
            );
        }

        return $fkName;
    }

    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $authManager = $this->getAuthManager();

        $this->db = $authManager->db;

        if (!$this->isMSSQL()) {
            return;
        }

        $schema = $this->db->getSchema()->defaultSchema;
        $itemChildSuffix = $this->db->schema->getRawTableName($authManager->itemChildTable);

        $this->dropTriggerIfExists(
            $schema,
            "trigger_delete_{$itemChildSuffix}",
        );
        $this->dropTriggerIfExists(
            $schema,
            "trigger_update_{$itemChildSuffix}",
        );

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
        $this->createAuthItemDeleteTrigger(
            $schema,
            $authManager,
        );
        $this->createAuthItemUpdateTrigger(
            $schema,
            $authManager,
            $childFk,
            $parentFk,
            $assignmentFk,
        );
        $this->createAuthRuleDeleteTrigger(
            $schema,
            $authManager,
        );
        $this->createAuthRuleUpdateTrigger(
            $schema,
            $authManager,
            $ruleFk,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $authManager = $this->getAuthManager();

        $this->db = $authManager->db;

        if (!$this->isMSSQL()) {
            return;
        }

        $schema = $this->db->getSchema()->defaultSchema;

        $itemChildSuffix = $this->db->schema->getRawTableName($authManager->itemChildTable);
        $ruleSuffix = $this->db->schema->getRawTableName($authManager->ruleTable);

        $this->dropTriggerIfExists(
            $schema,
            "trigger_delete_{$ruleSuffix}",
        );
        $this->dropTriggerIfExists(
            $schema,
            "trigger_update_{$ruleSuffix}",
        );
        $this->dropTriggerIfExists(
            $schema,
            "trigger_delete_{$itemChildSuffix}",
        );
        $this->dropTriggerIfExists(
            $schema,
            "trigger_update_{$itemChildSuffix}",
        );

        $childFk = $this->findForeignKeyName(
            $authManager->itemChildTable,
            'child',
            $authManager->itemTable,
            'name',
        );

        $this->restorePreviousAuthItemDeleteTrigger(
            $schema,
            $authManager,
        );
        $this->restorePreviousAuthItemUpdateTrigger(
            $schema,
            $authManager,
            $childFk
        );
    }

    /**
     * Drops a trigger if it exists.
     */
    protected function dropTriggerIfExists(string $schema, string $triggerName): void
    {
        $this->execute(
            <<<SQL
            IF (OBJECT_ID(N'{$schema}.{$triggerName}') IS NOT NULL) DROP TRIGGER {$schema}.{$triggerName}
            SQL,
        );
    }

    /**
     * Creates auth_item INSTEAD OF DELETE trigger.
     *
     * Cascades to auth_assignment and auth_item_child before deleting items.
     */
    protected function createAuthItemDeleteTrigger(string $schema, DbManager $authManager): void
    {
        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_delete_{$this->db->schema->getRawTableName($authManager->itemChildTable)}
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
    }

    /**
     * Creates auth_item INSTEAD OF UPDATE trigger with two modes.
     *
     * 1. Single-row name change: NOCHECK FKs, cascade to child/parent/assignment.
     * 2. Multi-row column update: JOIN-based update (used by auth_rule triggers).
     */
    protected function createAuthItemUpdateTrigger(
        string $schema,
        DbManager $authManager,
        string $childFk,
        string $parentFk,
        string $assignmentFk,
    ): void {
        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_update_{$this->db->schema->getRawTableName($authManager->itemChildTable)}
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
                    ALTER TABLE {$schema}.{$authManager->itemChildTable} NOCHECK CONSTRAINT {$childFk};
                    ALTER TABLE {$schema}.{$authManager->itemChildTable} NOCHECK CONSTRAINT {$parentFk};
                    ALTER TABLE {$schema}.{$authManager->assignmentTable} NOCHECK CONSTRAINT {$assignmentFk};
                    UPDATE {$schema}.{$authManager->itemChildTable} SET [child] = @new_name WHERE [child] = @old_name;
                    UPDATE {$schema}.{$authManager->itemChildTable} SET [parent] = @new_name WHERE [parent] = @old_name;
                    UPDATE {$schema}.{$authManager->assignmentTable} SET [item_name] = @new_name WHERE [item_name] = @old_name;
                    UPDATE {$schema}.{$authManager->itemTable}
                    SET [name] = @new_name,
                        [type] = (SELECT [type] FROM inserted),
                        [description] = (SELECT [description] FROM inserted),
                        [rule_name] = (SELECT [rule_name] FROM inserted),
                        [data] = (SELECT [data] FROM inserted),
                        [created_at] = (SELECT [created_at] FROM inserted),
                        [updated_at] = (SELECT [updated_at] FROM inserted)
                    WHERE [name] = @old_name
                    ALTER TABLE {$schema}.{$authManager->itemChildTable} CHECK CONSTRAINT {$childFk};
                    ALTER TABLE {$schema}.{$authManager->itemChildTable} CHECK CONSTRAINT {$parentFk};
                    ALTER TABLE {$schema}.{$authManager->assignmentTable} CHECK CONSTRAINT {$assignmentFk};
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
                    FROM {$schema}.{$authManager->itemTable} t
                    INNER JOIN deleted d ON t.[name] = d.[name]
                    INNER JOIN inserted i ON i.[name] = d.[name]
                END
            END
            SQL,
        );
    }

    /**
     * Creates auth_rule INSTEAD OF DELETE trigger.
     *
     * Sets auth_item.rule_name to NULL before deleting rules.
     */
    protected function createAuthRuleDeleteTrigger(string $schema, DbManager $authManager): void
    {
        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_delete_{$this->db->schema->getRawTableName($authManager->ruleTable)}
            ON {$schema}.{$authManager->ruleTable}
            INSTEAD OF DELETE
            AS
            BEGIN
                UPDATE {$schema}.{$authManager->itemTable} SET [rule_name] = NULL WHERE [rule_name] IN (SELECT [name] FROM deleted);
                DELETE FROM {$schema}.{$authManager->ruleTable} WHERE [name] IN (SELECT [name] FROM deleted);
            END
            SQL,
        );
    }

    /**
     * Creates auth_rule INSTEAD OF UPDATE trigger.
     *
     * Cascades rule name changes to auth_item.rule_name.
     */
    protected function createAuthRuleUpdateTrigger(string $schema, DbManager $authManager, string $ruleFk): void
    {
        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_update_{$this->db->schema->getRawTableName($authManager->ruleTable)}
            ON {$schema}.{$authManager->ruleTable}
            INSTEAD OF UPDATE
            AS
                DECLARE @old_name NVARCHAR(64) = (SELECT [name] FROM deleted)
                DECLARE @new_name NVARCHAR(64) = (SELECT [name] FROM inserted)
            BEGIN
                IF (SELECT COUNT(*) FROM deleted) > 1
                BEGIN
                    RAISERROR('Multi-row UPDATE on auth_rule is not supported by this trigger.', 16, 1);
                    RETURN;
                END
                IF @old_name <> @new_name
                BEGIN
                    ALTER TABLE {$schema}.{$authManager->itemTable} NOCHECK CONSTRAINT {$ruleFk};
                    UPDATE {$schema}.{$authManager->itemTable} SET [rule_name] = @new_name WHERE [rule_name] = @old_name;
                END
                UPDATE {$schema}.{$authManager->ruleTable}
                SET [name] = (SELECT [name] FROM inserted),
                    [data] = (SELECT [data] FROM inserted),
                    [created_at] = (SELECT [created_at] FROM inserted),
                    [updated_at] = (SELECT [updated_at] FROM inserted)
                WHERE [name] IN (SELECT [name] FROM deleted)
                IF @old_name <> @new_name
                BEGIN
                    ALTER TABLE {$schema}.{$authManager->itemTable} CHECK CONSTRAINT {$ruleFk};
                END
            END
            SQL,
        );
    }

    /**
     * Restores the previous auth_item INSTEAD OF DELETE trigger from m200409_110543_rbac_update_mssql_trigger.
     */
    protected function restorePreviousAuthItemDeleteTrigger(string $schema, DbManager $authManager): void
    {
        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_delete_{$this->db->schema->getRawTableName($authManager->itemChildTable)}
            ON {$schema}.{$authManager->itemTable}
            INSTEAD OF DELETE
            AS
            BEGIN
                DELETE FROM {$schema}.{$authManager->itemChildTable} WHERE parent IN (SELECT name FROM deleted) OR child IN (SELECT name FROM deleted);
                DELETE FROM {$schema}.{$authManager->itemTable} WHERE name IN (SELECT name FROM deleted);
            END;
            SQL,
        );
    }

    /**
     * Restores the previous auth_item INSTEAD OF UPDATE trigger from m200409_110543_rbac_update_mssql_trigger.
     */
    protected function restorePreviousAuthItemUpdateTrigger(string $schema, DbManager $authManager, string $childFk): void
    {
        $this->execute(
            <<<SQL
            CREATE TRIGGER {$schema}.trigger_update_{$this->db->schema->getRawTableName($authManager->itemChildTable)}
            ON {$schema}.{$authManager->itemTable}
            INSTEAD OF UPDATE
            AS
                DECLARE @old_name NVARCHAR(64) = (SELECT name FROM deleted)
                DECLARE @new_name NVARCHAR(64) = (SELECT name FROM inserted)
            BEGIN
                IF @old_name <> @new_name
                BEGIN
                    ALTER TABLE {$schema}.{$authManager->itemChildTable} NOCHECK CONSTRAINT {$childFk};
                    UPDATE {$schema}.{$authManager->itemChildTable} SET child = @new_name WHERE child = @old_name;
                END
                UPDATE {$schema}.{$authManager->itemTable}
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
                    ALTER TABLE {$schema}.{$authManager->itemChildTable} CHECK CONSTRAINT {$childFk};
                END
            END;
            SQL,
        );
    }
}
