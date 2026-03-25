<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db;

use function is_string;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Provides static helper methods for quoting and unquoting SQL identifiers.
 *
 * Handles symmetric quote characters (`` ` ``, `"`) and asymmetric quote characters (`[`/`]`).
 *
 * Properly unescapes doubled end-quote characters embedded within identifiers
 * (for example, `` `a``b` `` becomes `` a`b ``).
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class Quoter
{
    /**
     * Known SQL identifier quote pairs: [startQuote, endQuote].
     */
    private const QUOTE_PAIRS = [
        ['`', '`'],
        ['"', '"'],
        ["'", "'"],
        ['[', ']'],
    ];

    /**
     * Resolves a quote character property into a start/end pair.
     *
     * @param string|string[] $quoteCharacter A single character or an array of [start, end].
     *
     * @return string[] Array with two elements: [startCharacter, endCharacter].
     */
    public static function resolveQuoteCharacter(array|string $quoteCharacter): array
    {
        if (is_string($quoteCharacter)) {
            return [$quoteCharacter, $quoteCharacter];
        }

        return [$quoteCharacter[0], $quoteCharacter[1]];
    }

    /**
     * Strips outer quote characters and unescapes doubled end-quote characters.
     *
     * Returns the name unchanged if it is not wrapped in the given quote characters.
     *
     * @param string $name The possibly quoted identifier.
     * @param string $startQuote The opening quote character.
     * @param string $endQuote The closing quote character.
     *
     * @return string The unquoted identifier.
     */
    public static function unquoteIdentifier(string $name, string $startQuote, string $endQuote): string
    {
        if (strlen($name) <= 1 || !str_starts_with($name, $startQuote) || !str_ends_with($name, $endQuote)) {
            return $name;
        }

        return str_replace($endQuote . $endQuote, $endQuote, substr($name, 1, -1));
    }

    /**
     * Splits a qualified identifier on `.` only outside quoted segments, then unescapes each part.
     *
     * @param string $name The qualified identifier (for example, `` `schema`.`table` ``).
     * @param string $startQuote The opening quote character.
     * @param string $endQuote The closing quote character.
     *
     * @return string[] The unquoted parts.
     */
    public static function splitQuotedName(string $name, string $startQuote, string $endQuote): array
    {
        $parts = [];
        $current = '';
        $length = strlen($name);
        $inQuote = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $name[$i];

            if ($inQuote) {
                if ($char === $endQuote) {
                    if ($i + 1 < $length && $name[$i + 1] === $endQuote) {
                        $current .= $endQuote;
                        $i++;
                    } else {
                        $inQuote = false;
                    }
                } else {
                    $current .= $char;
                }
            } elseif ($char === '.') {
                $parts[] = $current;
                $current = '';
            } elseif ($char === $startQuote) {
                $inQuote = true;
            } else {
                $current .= $char;
            }
        }

        $parts[] = $current;

        return $parts;
    }

    /**
     * Strips outer quotes from an identifier using any known SQL quote pair, then unescapes doubled end-quotes.
     *
     * Returns the name unchanged if it is not wrapped in a recognized quote pair.
     *
     * @param string $name The possibly quoted identifier.
     *
     * @return string The unquoted identifier.
     */
    public static function unquoteAny(string $name): string
    {
        foreach (self::QUOTE_PAIRS as [$open, $close]) {
            $unquoted = self::unquoteIdentifier($name, $open, $close);

            if ($unquoted !== $name) {
                return $unquoted;
            }
        }

        return $name;
    }
}
