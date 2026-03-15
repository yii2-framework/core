<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\type;

use PDO;
use PHPUnit\Framework\Attributes\Group;
use yii\db\oci\ColumnSchema;
use yii\db\oci\Schema;
use yii\db\PdoValue;
use yii\db\Query;
use yiiunit\base\db\BaseDatabase;

/**
 * Unit test for {@see \yii\db\oci\ColumnSchema} with Oracle driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('oci')]
class BlobTest extends BaseDatabase
{
    protected $driverName = 'oci';

    public function testBlob(): void
    {
        $db = $this->getConnection();

        $db->createCommand()->delete('type')->execute();
        $db->createCommand()->insert(
            'type',
            [
                'int_col' => $key = 1,
                'char_col' => 'test',
                'char_col2' => '6a3ce1a0bffe8eeb6fa986caf443e24c',
                'float_col' => 0.0,
                'blob_col' => 'a:1:{s:13:"template";s:1:"1";}',
                'bool_col' => 1,
            ],
        )->execute();

        $result = (new Query())
            ->select(['blob_col'])
            ->from('type')
            ->where(['int_col' => $key])
            ->createCommand($db)
            ->queryScalar();

        self::assertSame(
            'a:1:{s:13:"template";s:1:"1";}',
            $result,
            'BLOB column should return the exact serialized string that was inserted.',
        );

        $updatedBlob = 'a:2:{s:13:"template";s:1:"2";s:4:"name";s:4:"test";}';

        $db->createCommand()->update(
            'type',
            ['blob_col' => $updatedBlob],
            ['int_col' => $key],
        )->execute();

        $result = (new Query())
            ->select(['blob_col'])
            ->from('type')
            ->where(['int_col' => $key])
            ->createCommand($db)
            ->queryScalar();

        self::assertSame(
            $updatedBlob,
            $result,
            'BLOB column should return the exact serialized string after update.',
        );
    }

    public function testDbTypecastPreservesPdoValue(): void
    {
        $columnSchema = new ColumnSchema();

        $columnSchema->type = Schema::TYPE_BINARY;
        $columnSchema->dbType = 'BLOB';

        $pdoValue = new PdoValue('binary content', PDO::PARAM_LOB);

        $result = $columnSchema->dbTypecast($pdoValue);

        self::assertInstanceOf(
            PdoValue::class,
            $result,
            'PdoValue should be preserved and delegated to parent without unwrapping.',
        );
    }
}
