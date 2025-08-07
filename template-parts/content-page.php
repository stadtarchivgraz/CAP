<article id="post-<?php the_ID(); ?>" <?php post_class( 'sip', ); ?>>
	<div class="container">
		<div class="entry-content">
			<?php the_content(); ?>
			<?php
			$current_locale   = strtolower( get_locale() );
			$sip_archive_role = ( carbon_get_theme_option( 'sip_archive_role' ) ) ? esc_attr( carbon_get_theme_option( 'sip_archive_role' ) ) : 'edit_others_posts';
			?>

			<?php
			if ( ! is_user_logged_in() ) :
				wp_login_form( array( 'echo' => true, 'form_id' => 'archival_loginform', ) );
			elseif ( is_page_template( 'sip-upload.php' ) ) :
				if ( ! get_user_meta( get_current_user_id(), 'user_privacy_policy_approval', true ) ) {
					echo wpautop( wp_kses_post( get_option( '_sip_update_profile_text_' . $current_locale ) ) );
				} else {
					echo starg_get_sip_form( 'uploadform.php' );
				}
			elseif ( is_page_template('sip-profile.php') ) :
				echo starg_get_sip_form( 'profileform.php' );
			elseif ( is_page_template( 'sip-archive.php' ) && current_user_can( $sip_archive_role ) ) :
				echo starg_get_sip_form( 'filterform.php' );
			endif;
			?>

		</div><!-- .entry-content -->
	</div><!-- .container -->

</article><!-- #post-<?php the_ID(); ?> -->
