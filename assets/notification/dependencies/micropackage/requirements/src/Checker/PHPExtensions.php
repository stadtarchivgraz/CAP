<?php
/**
 * PHP Extensions Checker class
 *
 * @package micropackage/requirements
 *
 * @license GPL-3.0-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace BracketSpace\Notification\Dependencies\Micropackage\Requirements\Checker;

use BracketSpace\Notification\Dependencies\Micropackage\Requirements\Abstracts;
use BracketSpace\Notification\Dependencies\Micropackage\Requirements\Requirements;

/**
 * PHP Extensions Checker class
 */
class PHPExtensions extends Abstracts\Checker {

	/**
	 * Checker name
	 *
	 * @var string
	 */
	protected $name = 'php_extensions';

	/**
	 * Checks if the requirement is met
	 *
	 * @since  1.0.0
	 * @throws \Exception When provided value is not an array.
	 * @param  array $value Array of extensions.
	 * @return void
	 */
	public function check( $value ) {

		if ( ! is_array( $value ) ) {
			throw new \Exception( 'PHP Extensions Check requires array parameter' );
		}

		$missing_extensions = array();

		foreach ( $value as $extension ) {
			if ( ! extension_loaded( $extension ) ) {
				$missing_extensions[] = $extension;
			}
		}

		if ( ! empty( $missing_extensions ) ) {
			$this->add_error( sprintf(
				// Translators: PHP extensions.
				_n( 'Missing PHP extension: %s', 'Missing PHP extensions: %s', count( $missing_extensions ), Requirements::$textdomain ),
				implode( ', ', $missing_extensions )
			) );
		}

	}

}
