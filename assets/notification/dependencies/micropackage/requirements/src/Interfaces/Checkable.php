<?php
/**
 * Checkable interface
 *
 * @package micropackage/requirements
 *
 * @license GPL-3.0-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace BracketSpace\Notification\Dependencies\Micropackage\Requirements\Interfaces;

/**
 * Checkable interface
 */
interface Checkable {

	/**
	 * Gets checker name
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_name();

	/**
	 * Checks if the requirement is met
	 *
	 * @since  1.0.0
	 * @param  mixed $value Value to check against.
	 * @return void
	 */
	public function check( $value );

}
