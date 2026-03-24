<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\mssql;

/**
 * Transaction represents a DB transaction for Microsoft SQL Server.
 *
 * Overrides savepoint operations to use MSSQL-specific `SAVE TRANSACTION` / `ROLLBACK TRANSACTION` syntax, and provides
 * a no-op `releaseSavepoint()` since SQL Server does not support explicit savepoint release.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class Transaction extends \yii\db\Transaction
{
    /**
     * {@inheritdoc}
     */
    public function createSavepoint(string $name): void
    {
        $this->validateSavepointName($name);

        $this->db->createCommand(
            <<<SQL
            SAVE TRANSACTION $name
            SQL
        )->execute();
    }

    /**
     * {@inheritdoc}
     *
     * No-op: SQL Server does not support explicit savepoint release.
     */
    public function releaseSavepoint(string $name): void
    {
        $this->validateSavepointName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function rollBackSavepoint(string $name): void
    {
        $this->validateSavepointName($name);

        $this->db->createCommand(
            <<<SQL
            ROLLBACK TRANSACTION $name
            SQL
        )->execute();
    }
}
