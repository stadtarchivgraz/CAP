<?php
if (! defined('WPINC')) { die; }

class Starg_User_Helper {
	public const STARG_TEST_USER_ROLE = 'test_user';
	public const STARG_USER_ROLES     = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber', Starg_User_Helper::STARG_TEST_USER_ROLE, );

	/**
	 * Sets new capabilities for the custom post type for archival records and its custom taxonomies.
	 * Subscribers should be able create drafts and pending posts for the custom post type archival!
	 * Run this function only on plugin activation! this is not supposed to run all the time!
	 */
	public static function starg_set_capabilities_for_archival_records() {
		if ( ! isset( Starg_User_Helper::STARG_USER_ROLES[0] ) ) { return; }

		foreach ( Starg_User_Helper::STARG_USER_ROLES as $single_user_role ) {
			$role = get_role( $single_user_role );
			if ( ! $role ) { return; }

			// Basic rules for our custom post type. Everyone can create/edit/delete their own archival record.
			$role->add_cap( 'edit_archival' );
			$role->add_cap( 'read_archival' );
			$role->add_cap( 'delete_archival' );// subject to change!

			// capabilities for the taxonomies.
			$role->add_cap( 'edit_archival_terms' );
			$role->add_cap( 'delete_archival_terms' );// subject to change!
			$role->add_cap( 'edit_archival_terms' );

			// admins and editors get extra capabilities.
			if ( 'administrator' === $single_user_role || 'editor' === $single_user_role ) {
				$role->add_cap( 'edit_archival_records' );
				$role->add_cap( 'edit_others_archival_records' );
				$role->add_cap( 'delete_archival_records' );
				$role->add_cap( 'publish_archival_records' );
				$role->add_cap( 'delete_published_archival_records' );
				$role->add_cap( 'delete_others_archival_records' );
				$role->add_cap( 'edit_published_archival_records' );

				$role->add_cap( 'manage_archival_terms' );
			}

			if ( 'administrator' === $single_user_role ) {
				$role->add_cap( 'read_private_archival_records' );
				$role->add_cap( 'delete_private_archival_records' );
				$role->add_cap( 'edit_private_archival_records' );
			}
		}
	}

	/**
	 * Removes the capabilities for our custom post type and its custom taxonomies.
	 */
	public static function starg_remove_capabilities_for_archival_records() {
		if ( ! isset( Starg_User_Helper::STARG_USER_ROLES[0] ) ) { return; }
	
		foreach ( Starg_User_Helper::STARG_USER_ROLES as $single_user_role ) {
			$role = get_role( $single_user_role );
			if ( ! $role ) { return; }

			// Basic rules for our custom post type. Everyone can create/edit/delete their own archival record.
			$role->remove_cap( 'edit_archival' );
			$role->remove_cap( 'read_archival' );
			$role->remove_cap( 'delete_archival' );// subject to change!

			// capabilities for the taxonomies.
			$role->add_cap( 'edit_archival_terms' );
			$role->add_cap( 'delete_archival_terms' );// subject to change!
			$role->add_cap( 'edit_archival_terms' );

			// admins and editors get extra capabilities.
			if ( 'administrator' === $single_user_role || 'editor' === $single_user_role ) {
				$role->remove_cap( 'edit_archival_records' );
				$role->remove_cap( 'edit_others_archival_records' );
				$role->remove_cap( 'delete_archival_records' );
				$role->remove_cap( 'publish_archival_records' );
				$role->remove_cap( 'delete_published_archival_records' );
				$role->remove_cap( 'delete_others_archival_records' );
				$role->remove_cap( 'edit_published_archival_records' );

				$role->add_cap( 'manage_archival_terms' );
			}

			if ( 'administrator' === $single_user_role ) {
				$role->remove_cap( 'read_private_archival_records' );
				$role->remove_cap( 'delete_private_archival_records' );
				$role->remove_cap( 'edit_private_archival_records' );
			}
		}
	}

	/**
	 * Create a user role for test user.
	 */
	public static function starg_create_user_role() {
		add_role(
			Starg_User_Helper::STARG_TEST_USER_ROLE,
			esc_html__( 'Test User', 'sip' ),
			array(
				'read' => true,
			)
		);
	}

	/**
	 * Remove a user role for test user and reset the role of our test users to subscriber.
	 */
	public static function starg_remove_user_role() {
		$test_users = get_users( array( 'role' => Starg_User_Helper::STARG_TEST_USER_ROLE, ) );
		foreach ( $test_users as $single_test_user ) {
			$single_test_user->set_role( 'subscriber' );
		}
		remove_role( Starg_User_Helper::STARG_TEST_USER_ROLE );
	}

}
