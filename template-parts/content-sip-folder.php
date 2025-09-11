<?php
/**
 * Template part to display the single files form ones SIP folder.
 */

/* if true, we're creating a PDF file of an archival page. */
$is_pdf = isset($pdf);

echo '<div class="container sip">';

	echo (! $is_pdf) ? '<ol class="sip-listing" id="sip-files" type="">' : '';

		require_once(STARG_SIP_PLUGIN_BASE_DIR . 'inc/render-sip-content-folder.php');
		Render_Sip_Content_Folder::render_sip_folder_content( $is_pdf );

	echo (! $is_pdf) ? '</ol>' : '';

echo '</div>';

if ( $is_pdf ) { return; }
?>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		const sipContainer = document.querySelector('#sip-files');
		if (!sipContainer) {
			return;
		}

		const bp = BiggerPicture({
			target: document.body,
		});

		const imageLinks = sipContainer.querySelectorAll('a');
		imageLinks.forEach(link => {
			link.addEventListener('click', openGallery);
		});

		function openGallery(event) {
			event.preventDefault();
			bp.open({
				items: imageLinks,
				el: event.currentTarget,
			});
		}
	});
</script>
