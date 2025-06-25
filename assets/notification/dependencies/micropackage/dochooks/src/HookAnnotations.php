<?php
/**
 * Activates the docblock hooks for the class.
 *
 * Use one of the following in method docblock to
 * register an action, filter or shortcode:
 *
 * @action hook_name priority
 * @filter filter_name priority
 * @shortcode shortcode_name
 *
 * @package micropackage/dochooks
 *
 * @license GPL-3.0-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace BracketSpace\Notification\Dependencies\Micropackage\DocHooks;

/**
 * HookAnnotations class
 */
class HookAnnotations {

	use HookTrait;

}
