<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db;

use yii\base\InvalidCallException;
use yii\db\DataReader;

use function count;

/**
 * Base test for {@see \yii\db\DataReader} across all database drivers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
abstract class BaseDataReader extends BaseDatabase
{
    public function testQueryReturnsDataReader(): void
    {
        $db = $this->getConnection(false);

        $reader = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} ORDER BY [[id]]
            SQL,
        )->query();

        self::assertInstanceOf(
            DataReader::class,
            $reader,
            'Should return a DataReader instance.',
        );
    }

    public function testRead(): void
    {
        $db = $this->getConnection(false);

        $reader = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} ORDER BY [[id]]
            SQL,
        )->query();
        $row1 = $reader->read();

        self::assertIsArray(
            $row1,
            "First 'read()' should return an array.",
        );
        self::assertEquals(
            1,
            $row1['id'],
            "First row id should be '1'.",
        );
        self::assertSame(
            'user1',
            $row1['name'],
            "First row name should be 'user1'."
        );

        $row2 = $reader->read();

        self::assertIsArray(
            $row2,
            "Second 'read()' should return an array."
        );
        self::assertEquals(
            2,
            $row2['id'],
            "Second row id should be '2'."
        );

        $row3 = $reader->read();

        self::assertIsArray(
            $row3,
            "Third 'read()' should return an array."
        );
        self::assertEquals(
            3,
            $row3['id'],
            "Third row id should be '3'."
        );
        self::assertFalse(
            $reader->read(),
            "Should return 'false' when no more rows are available."
        );
    }

    public function testClose(): void
    {
        $db = $this->getConnection(false);

        $reader = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL,
        )->query();
        $reader->read();
        $reader->close();

        self::expectNotToPerformAssertions();
    }

    public function testCountReturnsInt(): void
    {
        $db = $this->getConnection(false);

        $reader = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL,
        )->query();

        self::assertIsInt(
            $reader->count(),
            "Method should return an 'int'.",
        );
        self::assertIsInt(
            count($reader),
            "'count()' via Countable interface should return an 'int'.",
        );
    }

    public function testForeachIteration(): void
    {
        $db = $this->getConnection(false);

        $reader = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} ORDER BY [[id]]
            SQL,
        )->query();

        $rows = [];

        foreach ($reader as $index => $row) {
            $rows[$index] = $row;
        }

        self::assertCount(
            3,
            $rows,
            "foreach should iterate over all '3' customer rows.",
        );
        self::assertEquals(
            0,
            array_key_first($rows),
            "First key should be '0'.",
        );
        self::assertEquals(
            2,
            array_key_last($rows),
            "Last key should be '2'.",
        );
        self::assertSame(
            'user1',
            $rows[0]['name'],
            "First row name should be 'user1'.",
        );
        self::assertSame(
            'user2',
            $rows[1]['name'],
            "Second row name should be 'user2'.",
        );
        self::assertSame(
            'user3',
            $rows[2]['name'],
            "Third row name should be 'user3'.",
        );
    }

    public function testRewindThrowsOnSecondTraversal(): void
    {
        $db = $this->getConnection(false);

        $reader = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL,
        )->query();

        foreach ($reader as $row) {
            // consume first traversal
        }

        $this->expectException(InvalidCallException::class);
        $this->expectExceptionMessage('DataReader cannot rewind. It is a forward-only reader.');

        $reader->rewind();
    }

    public function testKeyReturnsSequentialIndices(): void
    {
        $db = $this->getConnection(false);

        $reader = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} ORDER BY [[id]]
            SQL,
        )->query();

        $keys = [];

        foreach ($reader as $key => $row) {
            $keys[] = $key;
        }

        self::assertSame(
            [0, 1, 2],
            $keys,
            'Should return sequential zero-based indices.',
        );
    }

    public function testValidReturnsFalseWhenExhausted(): void
    {
        $db = $this->getConnection(false);

        $reader = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL,
        )->query();
        $reader = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL,
        )->query();

        foreach ($reader as $row) {
            // consume all rows
        }

        self::assertFalse(
            $reader->valid(),
            "Should return 'false' after all rows are consumed.",
        );
    }

    public function testEmptyResultSet(): void
    {
        $db = $this->getConnection(false);

        $reader = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} WHERE [[id]] = 9999
            SQL,
        )->query();

        self::assertFalse(
            $reader->read(),
            "Should return 'false' for an empty result set.",
        );

        $reader2 = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} WHERE [[id]] = 9999
            SQL,
        )->query();

        $rows = [];

        foreach ($reader2 as $row) {
            $rows[] = $row;
        }

        self::assertEmpty(
            $rows,
            'foreach on empty result set should produce no rows.',
        );
    }
}
