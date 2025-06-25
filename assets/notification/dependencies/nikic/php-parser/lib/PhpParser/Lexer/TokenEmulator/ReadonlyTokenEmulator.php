<?php
/**
 * @license BSD-3-Clause
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\PhpParser\Lexer\TokenEmulator;

use BracketSpace\Notification\Dependencies\PhpParser\Lexer\Emulative;

final class ReadonlyTokenEmulator extends KeywordEmulator
{
    public function getPhpVersion(): string
    {
        return Emulative::PHP_8_1;
    }

    public function getKeywordString(): string
    {
        return 'readonly';
    }

    public function getKeywordToken(): int
    {
        return \T_READONLY;
    }

    protected function isKeywordContext(array $tokens, int $pos): bool
    {
        if (!parent::isKeywordContext($tokens, $pos)) {
            return false;
        }
        // Support "function readonly("
        return !(isset($tokens[$pos + 1]) &&
                 ($tokens[$pos + 1][0] === '(' ||
                  ($tokens[$pos + 1][0] === \T_WHITESPACE &&
                   isset($tokens[$pos + 2]) &&
                   $tokens[$pos + 2][0] === '(')));
    }
}
