<?php
/**
 * SSL Checker class
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
 * SSL Checker class
 */
class SSL extends Abstracts\Checker {

	/**
	 * Checker name
	 *
	 * @var string
	 */
	protected $name = 'ssl';

	/**
	 * Checks if the requirement is met
	 *
	 * @since  1.1.0
	 * @throws \Exception When provided value is not a string or numeric.
	 * @param  string $enabled If SSL should be enabled or disabled.
	 * @return void
	 */
	public function check( $enabled ) {

		if ( ! is_bool( $enabled ) ) {
			throw new \Exception( 'SSL Check requires bool parameter' );
		}

		if ( $enabled && ! is_ssl() ) {
			$this->add_error( __( 'SSL is required', Requirements::$textdomain ) );
		}

		if ( ! $enabled && is_ssl() ) {
			$this->add_error( __( 'SSL is superfluous', Requirements::$textdomain ) );
		}

	}

}
