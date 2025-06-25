<?php
/**
 * Template functions
 *
 * @package micropackage/templates
 *
 * @license GPL-3.0-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace BracketSpace\Notification\Dependencies\Micropackage\Templates;

/**
 * Prints the template
 * Wrapper for Template class
 *
 * @since  1.1.0
 * @param  string $storage Storage name.
 * @param  string $name    Template name.
 * @param  array  $vars    Tempalte variables.
 *                         Default: empty.
 * @return void
 */
function template( $storage, $name, $vars = [] ) {
	( new Template( $storage, $name, $vars ) )->render();
}

/**
 * Outputs the template
 * Wrapper for Template class
 *
 * @since  1.1.0
 * @param  string $storage Storage name.
 * @param  string $name    Template name.
 * @param  array  $vars    Tempalte variables.
 *                         Default: empty.
 * @return string
 */
function get_template( $storage, $name, $vars = [] ) {
	return ( new Template( $storage, $name, $vars ) )->output();
}
