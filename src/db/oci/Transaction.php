<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\oci;

/**
 * Transaction represents a DB transaction for Oracle.
 *
 * Overrides `releaseSavepoint()` as a no-op since Oracle does not support explicit savepoint release.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class Transaction extends \yii\db\Transaction
{
    /**
     * {@inheritdoc}
     */
    public function releaseSavepoint(string $name): void
    {
        $this->validateSavepointName($name);
    }
}
