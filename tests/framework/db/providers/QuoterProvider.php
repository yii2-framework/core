<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\providers;

/**
 * Data provider for {@see \yiiunit\framework\db\QuoterTest} test cases.
 *
 * Provides representative input/output pairs for SQL identifier quoting and unquoting.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class QuoterProvider
{
    /**
     * @phpstan-return array<string, array{string|string[], string[]}>
     */
    public static function resolveQuoteCharacter(): array
    {
        return [
            'array asymmetric' => [['[', ']'], ['[', ']']],
            'array symmetric' => [['`', '`'], ['`', '`']],
            'string double quote' => ['"', ['"', '"']],
            'string single quote' => ["'", ["'", "'"]],
            'string symmetric' => ['`', ['`', '`']],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string, string, string}>
     */
    public static function unquoteIdentifier(): array
    {
        return [
            // Asymmetric bracket quoting (MSSQL).
            'bracket embedded closing' => ['[a]]b]', '[', ']', 'a]b'],
            'bracket multiple embedded' => ['[a]]b]]c]', '[', ']', 'a]b]c'],
            'bracket simple' => ['[myTable]', '[', ']', 'myTable'],

            // Quote char in middle but not wrapping — returned as-is.
            'mid-string backtick' => ['foo`bar', '`', '`', 'foo`bar'],
            'mid-string double quote' => ['foo"bar', '"', '"', 'foo"bar'],

            // Mismatched quotes — returned as-is.
            'bracket open without close' => ['[myTable', '[', ']', '[myTable'],
            'end without start' => ['myTable`', '`', '`', 'myTable`'],
            'start without end' => ['`myTable', '`', '`', '`myTable'],

            // Single char — too short to be properly quoted, returned as-is.
            'single backtick' => ['`', '`', '`', '`'],

            // Symmetric backtick quoting.
            'backtick embedded' => ['`a``b`', '`', '`', 'a`b'],
            'backtick multiple embedded' => ['`a``b``c`', '`', '`', 'a`b`c'],
            'backtick only escaped' => ['````', '`', '`', '`'],
            'backtick simple' => ['`myTable`', '`', '`', 'myTable'],

            // Symmetric double-quote quoting.
            'double quote embedded' => ['"a""b"', '"', '"', 'a"b'],
            'double quote multiple embedded' => ['"a""b""c"', '"', '"', 'a"b"c'],
            'double quote simple' => ['"myTable"', '"', '"', 'myTable'],

            // Unquoted names — returned as-is.
            'empty string' => ['', '`', '`', ''],
            'single char unquoted' => ['a', '`', '`', 'a'],
            'unquoted name' => ['myTable', '`', '`', 'myTable'],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string, string, string[]}>
     */
    public static function splitQuotedName(): array
    {
        return [
            // Backtick-quoted (MySQL/SQLite).
            'backtick dot inside quotes' => ['`a.b`', '`', '`', ['a.b']],
            'backtick embedded quote' => ['`a``b`', '`', '`', ['a`b']],
            'backtick schema with embedded' => ['`s``ch`.`ta``b`', '`', '`', ['s`ch', 'ta`b']],
            'backtick schema.table' => ['`schema`.`table`', '`', '`', ['schema', 'table']],
            'backtick single' => ['`myTable`', '`', '`', ['myTable']],

            // Bracket-quoted (MSSQL).
            'bracket dot inside quotes' => ['[a.b]', '[', ']', ['a.b']],
            'bracket embedded closing' => ['[a]]b]', '[', ']', ['a]b']],
            'bracket schema.table' => ['[schema].[table]', '[', ']', ['schema', 'table']],
            'bracket single' => ['[myTable]', '[', ']', ['myTable']],
            'bracket three parts' => ['[cat].[schema].[table]', '[', ']', ['cat', 'schema', 'table']],

            // Double-quote (PostgreSQL/Oracle).
            'dquote dot inside quotes' => ['"a.b"', '"', '"', ['a.b']],
            'dquote embedded quote' => ['"a""b"', '"', '"', ['a"b']],
            'dquote schema.table' => ['"schema"."table"', '"', '"', ['schema', 'table']],
            'dquote single' => ['"myTable"', '"', '"', ['myTable']],

            // Mixed quoted and unquoted parts.
            'mixed unquoted schema' => ['schema.`table`', '`', '`', ['schema', 'table']],

            // Simple unquoted names.
            'empty string' => ['', '`', '`', ['']],
            'unquoted dotted' => ['schema.table', '`', '`', ['schema', 'table']],
            'unquoted single' => ['myTable', '`', '`', ['myTable']],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function unquoteAny(): array
    {
        return [
            // Already template-wrapped.
            'template wrapped' => ['{{myTable}}', '{{myTable}}'],

            // Backtick.
            'backtick embedded' => ['`a``b`', 'a`b'],
            'backtick simple' => ['`myTable`', 'myTable'],

            // Bracket.
            'bracket embedded' => ['[a]]b]', 'a]b'],
            'bracket simple' => ['[myTable]', 'myTable'],

            // Double quote.
            'double quote embedded' => ['"a""b"', 'a"b'],
            'double quote simple' => ['"myTable"', 'myTable'],

            // Not a recognized pair — returned as-is.
            'mismatched quotes' => ['`myTable"', '`myTable"'],
            'single char' => ['`', '`'],

            // Single quote.
            'single quote embedded' => ["'a''b'", "a'b"],
            'single quote simple' => ["'myTable'", 'myTable'],

            // Unquoted — returned as-is.
            'empty string' => ['', ''],
            'unquoted' => ['myTable', 'myTable'],
        ];
    }
}
