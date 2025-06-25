<?php
/**
 * @license GPL-3.0-or-later
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

/**
 * CaseHelper
 *
 * @package micropackage/casegnostic
 */

namespace BracketSpace\Notification\Dependencies\Micropackage\Casegnostic\Helpers;

/**
 * Case Helper Class
 *
 * Used to help with identifying and changing cases
 */
class CaseHelper
{
	/**
	 * Indicates whether given string is written in snake case.
	 *
	 * @example CaseHelper::isSnake('snake_case') // true
	 * @example CaseHelper::isSnake('camelCase') // false
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function isSnake(string $name)
	{
		return (bool)preg_match('/^[a-zA-Z0-9]+?(_[a-zA-Z0-9]+)+$/', $name);
	}

	/**
	 * Indicates whether given string is written in snake case.
	 *
	 * @example CaseHelper::isCamel('camelCase') // true
	 * @example CaseHelper::isCamel('snake_case') // false
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function isCamel(string $name)
	{
		return (bool)preg_match('/^[a-z]+([A-Z][a-z]+)+$/', $name);
	}

	/**
	 * Converts camelCase string to snake_case
	 *
	 * @example CaseHelper::toSnake('camelCase') // camel_case
	 * @example CaseHelper::toSnake('snake_case') // exception
	 *
	 * @param string $name
	 * @return string
	 * @throws \Exception
	 */
	public static function toSnake(string $name)
	{
		if (self::isCamel($name)) {
			return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $name) ?? '');
		}

		throw new \InvalidArgumentException("'$name' is not in camelCase format");
	}

	/**
	 * Converts snake_case string to camelCase
	 *
	 * @example CaseHelper::camelCase('snake_case') // snakeCase
	 * @example CaseHelper::camelCase('camelCase') // exception
	 *
	 * @param string $name
	 * @return string
	 * @throws \Exception
	 */
	public static function toCamel(string $name)
	{
		if (self::isSnake($name)) {
			return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));
		}

		throw new \InvalidArgumentException("'$name' is not in snake_case format");
	}
}
