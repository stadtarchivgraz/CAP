<?php
$sip_time     = time();
$sip_max_size = (carbon_get_theme_option('sip_max_size')) ? (int) esc_attr(carbon_get_theme_option('sip_max_size')) : 50000000;

$sip_archival_remove_url_endpoint = '';
$sip_archival_remove_form_name    = '';
$sip_archival_remove_nonce_action = '';
$sip_archival_remove_nonce_key    = '';
$sip_archival_remove = apply_filters('starg/sip_archival_remove', null);
if ($sip_archival_remove instanceof Sip_Archival_Remove) {
	$sip_archival_remove->process_archival_remove();

	$sip_archival_remove_url_endpoint = add_query_arg(array($sip_archival_remove->url_endpoint => true,));
	$sip_archival_remove_form_name    = $sip_archival_remove->form_name;
	$sip_archival_remove_nonce_action = wp_create_nonce($sip_archival_remove->nonce_action);
	$sip_archival_remove_nonce_key    = $sip_archival_remove->nonce_key;
} else {
	$logging = apply_filters( 'starg/logging', null );
	if ( $logging instanceof Starg_Logging && $logging->error_logging_enabled ) {
		$logging->create_log_entry( esc_attr__( 'Problems loading the Class "Sip_Archival_Remove".', 'sip' ) );
	}
}
?>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		const archivalUploadRemoveEnabled = '<?php echo ( $sip_archival_remove ) ? true : false; ?>';
		Dropzone.options.archivalUploadForm = { // The camelized version of the ID of the form element
			dictDefaultMessage: "<?php esc_html_e('Drag and drop the files here to upload', 'sip'); ?>",
			// The configuration we've talked about above
			//autoProcessQueue: false,
			//uploadMultiple: true,
			//parallelUploads: 100,
			//maxFiles: 100,
			// withCredentials: true,
			ignoreHiddenFiles: true,
			addRemoveLinks: true,
			//acceptedFiles:  'application/pdf,application/xml,text/plain,text/xml,text/csv,image/jpeg,image/png,image/tiff,image/svg+xml,audio/wav,audio/flac,audio/mpeg,video/x-msvideo,video/avi,video/x-matroska',
			// The setting up of the dropzone
			init: function() {
				const sipDropzone = this;
				const submitButton = document.getElementById('submit_sip');

				// Listen to the sendingmultiple event. In this case, it's the sendingmultiple event instead
				// of the sending event because uploadMultiple is set to true.
				sipDropzone.on("sending", function(file, xhr, data) {
					// if file is actually a folder
					if (file.fullPath) {
						data.append("fullPath", file.fullPath);
					}
				});

				<?php
				/**
				 * At this point, the file has already been removed from the Dropzone. But not from the server!
				 * We're removing the uploaded File in @see Sip_Archival_Remove::process_archival_remove()
				 */
				?>
				sipDropzone.on("removedfile", function(file) {
					if ( ! archivalUploadRemoveEnabled ) { return; }

					const currentUserID                = <?php echo get_current_user_id(); ?>;
					const sipFolderTime                = '<?php echo $sip_time; ?>';
					const sipArchivalRemoveUrlEndpoint = '<?php echo $sip_archival_remove_url_endpoint; ?>';
					const sipArchivalRemoveFormName    = '<?php echo $sip_archival_remove_form_name; ?>';
					const sipArchivalRemoveNonceAction = '<?php echo $sip_archival_remove_nonce_action; ?>';
					const sipArchivalRemoveNonceKey    = '<?php echo $sip_archival_remove_nonce_key; ?>';

					const removedFileXhr = new XMLHttpRequest();
					removedFileXhr.open("POST", sipArchivalRemoveUrlEndpoint );
					removedFileXhr.setRequestHeader("Accept", "application/json");
					removedFileXhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

					let removepath = (file.fullPath) ? file.fullPath : file.upload.filename;

					// todo: change the way we pass the form data to the php-script
					const formData = 'deletePath=' + removepath + '&sipUserID=' + currentUserID + '&sipFolder=SIP-' + sipFolderTime + '&' + sipArchivalRemoveNonceKey + '=' + sipArchivalRemoveNonceAction + '&starg_form_name=' + sipArchivalRemoveFormName;

					removedFileXhr.send(formData);

					// possible states are: 0 = unsent, 1 = opened, 2 = headers_received, 3 = loading, 4 = done.
					removedFileXhr.onreadystatechange = function() {
						if (removedFileXhr.readyState === 4) {
							try {
								const response = JSON.parse(removedFileXhr.responseText);
								if (response.sip_size > <?php echo $sip_max_size; ?>) {
									<?php // translators: %s: Maximum supported filesize for uploads - for example: "42MB". ?>
									alert('<?php echo sprintf(esc_attr__('The maximum size of %s for the SIP has been exceeded. Files need to be removed.', 'sip'), starg_human_filesize($sip_max_size)); ?>');
									submitButton.disabled = true;
								} else {
									submitButton.disabled = false;
								}

								if (sipDropzone.files.length === 0) {
									submitButton.disabled = true;
								}
							} catch (e) {
								console.warn( "<?php esc_attr_e( 'We are facing problems parsing the JSON response while attempting to delete an uploaded file.', 'sip' ); ?>" );
							}
						}
					};
				});

				// todo: maybe change all the alerts into modals?!
				sipDropzone.on("success", function(file, response) {
					if (response.infected) {
						sipDropzone.removeFile(file);
						alert("<?php esc_attr_e('Virus detected in:', 'sip'); ?>" + ' ' + response.infected);
					}
					if (response.not_supported) {
						sipDropzone.removeFile(file);
						alert("<?php esc_attr_e('Unsupported file type:', 'sip'); ?>" + ' ' + response.not_supported);
					}
					if (response.sip_full) {
						sipDropzone.removeFile(file);
						alert("<?php esc_attr_e('Further uploads are not possible. Files must be deleted first.', 'sip'); ?>");
					}
					if (sipDropzone.files.length != 0) {
						submitButton.disabled = false;
					} else {
						submitButton.disabled = true;
					}

					if (response.sip_size > <?php echo $sip_max_size; ?>) {
						alert("<?php echo sprintf(esc_attr__('The maximum size of %s for the SIP has been exceeded. Files need to be removed.', 'sip'), starg_human_filesize($sip_max_size)); ?>");
						submitButton.disabled = true;
					}
				});
			}
		}
	});
</script>

<?php
$sip_archival_upload_url_endpoint = '';
$sip_archival_upload_form_name    = '';
$sip_archival_upload_nonce        = '';
$sip_archival_upload              = apply_filters('starg/sip_archival_upload', null);
if ($sip_archival_upload instanceof Sip_Archival_Upload) {
	$sip_archival_upload->process_archival_upload();

	$sip_archival_upload_url_endpoint = add_query_arg(array($sip_archival_upload->url_endpoint => true,));
	$sip_archival_upload_form_name    = esc_attr( $sip_archival_upload->form_name );
	$sip_archival_upload_nonce        = wp_nonce_field( esc_attr( $sip_archival_upload->nonce_action ), esc_attr( $sip_archival_upload->nonce_key ), false, false );
} else {
	$logging = apply_filters( 'starg/logging', null );
	if ( $logging instanceof Starg_Logging && $logging->error_logging_enabled ) {
		$logging->create_log_entry( esc_attr__( 'Problems loading the Class "Sip_Archival_Upload".', 'sip' ) );
	}
}
?>
<form action="<?php echo $sip_archival_upload_url_endpoint; ?>" class="dropzone" id="archivalUploadForm">
	<input type="hidden" name="starg_form_name" value="<?php echo $sip_archival_upload_form_name ?>" aria-hidden="true" />
	<?php echo $sip_archival_upload_nonce; ?>

	<input type="hidden" name="sipUserID" value="<?php echo get_current_user_id(); ?>">
	<input type="hidden" name="sipFolder" value="SIP-<?php echo $sip_time; ?>">
</form>

<form action="" method="get">
	<?php
	// todo: think of a different solution. with this one we have 3 query-args in the url. that is not that fancy...
	// But in order to be able to get to the next step we need to verify, that the actual user is allowed to view the next page by not just adding the sipFolder to the url.
	?>
	<input type="hidden" name="starg" value="1" aria-hidden="true" />
	<?php wp_nonce_field( 'starg_add_archival_meta_data_nonce_action', 'starg_amd', false ); ?>
	<input type="hidden" name="sipFolder" value="SIP-<?php echo $sip_time; ?>">
	<input id="submit_sip" type="submit" value="<?php _e('Complete upload and insert metadata', 'sip'); ?>" disabled>
</form>
