<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db;

use yii\base\InvalidCallException;

/**
 * DataReader represents a forward-only stream of rows from a query result set.
 *
 * To read the current row of data, call [[read()]]. Rows of data can also be read by
 * iterating through the reader. For example,
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post');
 * $reader = $command->query();
 *
 * while ($row = $reader->read()) {
 *     $rows[] = $row;
 * }
 *
 * // equivalent to:
 * foreach ($reader as $row) {
 *     $rows[] = $row;
 * }
 * ```
 *
 * Note that since DataReader is a forward-only stream, you can only traverse it once.
 * Doing it the second time will throw an exception.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 *
 * @implements \Iterator<int, mixed>
 */
class DataReader extends \yii\base\BaseObject implements \Iterator, \Countable
{
    /**
     * @var \PDOStatement the PDOStatement associated with the command
     */
    private \PDOStatement $_statement;
    private bool $_closed = false;
    private mixed $_row = null;
    private int $_index = -1;


    /**
     * @param Command $command the command generating the query result.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct(Command $command, array $config = [])
    {
        $this->_statement = $command->pdoStatement;
        $this->_statement->setFetchMode(\PDO::FETCH_ASSOC);

        parent::__construct($config);
    }

    /**
     * Advances the reader to the next row in a result set.
     *
     * @return array|false the current row, false if no more row available.
     */
    public function read(): array|false
    {
        return $this->_statement->fetch();
    }

    /**
     * Closes the reader.
     * This frees up the resources allocated for executing this SQL statement.
     * Read attempts after this method call are unpredictable.
     */
    public function close(): void
    {
        $this->_statement->closeCursor();
        $this->_closed = true;
    }

    /**
     * Returns the number of rows in the result set.
     * This method is required by the Countable interface.
     * Note, most DBMS may not give a meaningful count.
     * In this case, use "SELECT COUNT(*) FROM tableName" to obtain the number of rows.
     *
     * @return int number of rows contained in the result.
     */
    public function count(): int
    {
        return $this->_statement->rowCount();
    }

    /**
     * Resets the iterator to the initial state.
     * This method is required by the interface [[\Iterator]].
     *
     * @throws InvalidCallException if this method is invoked twice.
     */
    public function rewind(): void
    {
        if ($this->_index < 0) {
            $this->_row = $this->_statement->fetch();
            $this->_index = 0;
        } else {
            throw new InvalidCallException('DataReader cannot rewind. It is a forward-only reader.');
        }
    }

    /**
     * Returns the index of the current row.
     * This method is required by the interface [[\Iterator]].
     *
     * @return int the index of the current row.
     */
    public function key(): int
    {
        return $this->_index;
    }

    /**
     * Returns the current row.
     * This method is required by the interface [[\Iterator]].
     *
     * @return mixed the current row.
     */
    public function current(): mixed
    {
        return $this->_row;
    }

    /**
     * Moves the internal pointer to the next row.
     * This method is required by the interface [[\Iterator]].
     */
    public function next(): void
    {
        $this->_row = $this->_statement->fetch();
        $this->_index++;
    }

    /**
     * Returns whether there is a row of data at current position.
     * This method is required by the interface [[\Iterator]].
     *
     * @return bool whether there is a row of data at current position.
     */
    public function valid(): bool
    {
        return $this->_row !== false;
    }
}
