<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci\providers;

use yii\db\ColumnSchemaBuilder;
use yii\db\oci\Schema;

/**
 * Data provider for {@see \yiiunit\framework\db\oci\ColumnTypeTest} column type test cases.
 *
 * Provides Oracle-specific input/output pairs for column type operations.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class ColumnTypeProvider extends \yiiunit\base\db\providers\ColumnTypeProvider
{
    public static function columnTypes(): array
    {
        return [
            ...parent::columnTypes(),
            'boolean default 1 not null' => [
                Schema::TYPE_BOOLEAN . ' DEFAULT 1 NOT NULL',
                static fn($t): ColumnSchemaBuilder => $t->boolean()->notNull()->defaultValue(1),
                ['oci' => 'NUMBER(1) DEFAULT 1 NOT NULL'],
            ]];
    }
}
