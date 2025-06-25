<?php
/**
 * Checker abstract
 *
 * @package micropackage/requirements
 *
 * @license GPL-3.0-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace BracketSpace\Notification\Dependencies\Micropackage\Requirements\Abstracts;

use BracketSpace\Notification\Dependencies\Micropackage\Requirements\Interfaces;

/**
 * Checker abstract
 */
abstract class Checker implements Interfaces\Checkable {

	/**
	 * Checker name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Error messages
	 *
	 * @var array
	 */
	protected $errors = [];

	/**
	 * Checks if the requirement is met
	 *
	 * @since  1.0.0
	 * @param  mixed $value Value to check against.
	 * @return void
	 */
	abstract public function check( $value );

	/**
	 * Gets checker name
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Adds error message
	 *
	 * @since  1.0.0
	 * @param  string $message Error message.
	 * @return $this
	 */
	public function add_error( $message ) {
		$this->errors[] = $message;
		return $this;
	}

	/**
	 * Gets all errors
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_errors() {
		return $this->errors;
	}

}
