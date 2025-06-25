<?php
/**
 * @license BSD-3-Clause
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\PhpParser\Lexer\TokenEmulator;

/** @internal */
abstract class TokenEmulator
{
    abstract public function getPhpVersion(): string;

    abstract public function isEmulationNeeded(string $code): bool;

    /**
     * @return array Modified Tokens
     */
    abstract public function emulate(string $code, array $tokens): array;

    /**
     * @return array Modified Tokens
     */
    abstract public function reverseEmulate(string $code, array $tokens): array;

    public function preprocessCode(string $code, array &$patches): string {
        return $code;
    }
}
