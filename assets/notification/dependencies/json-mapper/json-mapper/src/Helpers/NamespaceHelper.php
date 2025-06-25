<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\JsonMapper\Helpers;

use BracketSpace\Notification\Dependencies\JsonMapper\Enums\ScalarType;
use BracketSpace\Notification\Dependencies\JsonMapper\Parser\Import;

class NamespaceHelper
{
    /** @param Import[] $imports */
    public static function resolveNamespace(string $type, string $contextNamespace, array $imports): string
    {
        if (ScalarType::isValid($type)) {
            return $type;
        }

        $matches = \array_filter(
            $imports,
            static function (Import $import) use ($type) {
                $nameSpacedType = "\\{$type}";
                if ($import->hasAlias() && $import->getAlias() === $type) {
                    return true;
                }

                return $nameSpacedType === \substr($import->getImport(), -strlen($nameSpacedType));
            }
        );

        $firstMatch = array_shift($matches);
        if (! \is_null($firstMatch)) {
            return $firstMatch->getImport();
        }

        if (class_exists($contextNamespace . '\\' . $type)) {
            return $contextNamespace . '\\' . $type;
        }

        return $type;
    }
}
