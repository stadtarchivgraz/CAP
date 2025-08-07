<?php
$sip_time     = time();
$sip_max_size = (carbon_get_theme_option('sip_max_size')) ? (int) sanitize_text_field( carbon_get_theme_option('sip_max_size') ) : 50000000;
?>

<script>
	document.addEventListener( 'DOMContentLoaded', () => {
		Dropzone.options.archivalUploadForm = { // The camelized version of the ID of the form element
			dictDefaultMessage: "<?php _e('Drag and drop the files here to upload', 'sip'); ?>",
			// The configuration we've talked about above
			//autoProcessQueue: false,
			//uploadMultiple: true,
			//parallelUploads: 100,
			//maxFiles: 100,
			// withCredentials: true,
			// ignoreHiddenFiles: false,
			addRemoveLinks: true,
			//acceptedFiles:  'application/pdf,application/xml,text/plain,text/xml,text/csv,image/jpeg,image/png,image/tiff,image/svg+xml,audio/wav,audio/flac,audio/mpeg,video/x-msvideo,video/avi,video/x-matroska',
			// The setting up of the dropzone
			init: function() {
				const sipDropzone  = this;
				const submitButton = document.getElementById('submit_sip');

				// Listen to the sendingmultiple event. In this case, it's the sendingmultiple event instead
				// of the sending event because uploadMultiple is set to true.
				sipDropzone.on("sending", function(file, xhr, data) {
					// xhr.setRequestHeader(  );
					// if file is actually a folder
					if (file.fullPath) {
						data.append("fullPath", file.fullPath);
					}
				});

				sipDropzone.on("removedfile", function(file) {
					let xhr = new XMLHttpRequest();
					xhr.open("POST", "<?php echo STARG_SIP_PLUGIN_BASE_URL . 'archival-remove.php'; ?>");
					xhr.setRequestHeader("Accept", "application/json");
					xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

					let removepath = (file.fullPath) ? file.fullPath : file.upload.filename;

					let data = 'deletePath=' + removepath + '&sipUserID=<?php echo get_current_user_id(); ?>&sipFolder=SIP-<?php echo $sip_time; ?>';

					xhr.send(data);

					xhr.onreadystatechange = function() {
						if (xhr.readyState === 4) {
							const response = JSON.parse(xhr.responseText);
							if (response.sip_size > <?php echo $sip_max_size; ?>) {
								alert('<?php echo sprintf(__('The maximum size of %s for the SIP has been exceeded. Files need to be removed.', 'sip'), starg_human_filesize($sip_max_size)); ?>');
								submitButton.disabled = true;
							} else {
								submitButton.disabled = false;
							}

							if (sipDropzone.files.length === 0) {
								submitButton.disabled = true;
							}
						}
					};
				});

				sipDropzone.on("success", function(file, response) {
					if (response.infected) {
						sipDropzone.removeFile(file);
						alert("<?php _e('Virus detected on:', 'sip'); ?>" + response.infected);
					}
					if (response.not_supported) {
						sipDropzone.removeFile(file);
						alert("<?php _e('Not supported file type:', 'sip'); ?>" + response.not_supported);
					}
					if (response.sip_full) {
						sipDropzone.removeFile(file);
						alert("<?php _e('Further uploads are not possible. Files must be deleted first.', 'sip'); ?>");
					}
					if (sipDropzone.files.length != 0) {
						submitButton.disabled = false;
					} else {
						submitButton.disabled = true;
					}

					if (response.sip_size > <?php echo $sip_max_size; ?>) {
						alert("<?php echo sprintf(__('The maximum size of %s for the SIP has been exceeded. Files need to be removed.', 'sip'), starg_human_filesize($sip_max_size)); ?>");
						submitButton.disabled = true;
					}
				});
			}
		}
	});
</script>

<form action="<?php echo STARG_SIP_PLUGIN_BASE_URL . 'archival-upload.php'; ?>" class="dropzone" id="archivalUploadForm">
	<input type="hidden" name="starg_form_name" value="add_files_to_sip_form" aria-hidden="true" />
	<input type="hidden" name="starg_form_post_id" value="<?php the_ID(); ?>" aria-hidden="true" />
	<?php wp_nonce_field('starg_add_archival_files_nonce_action', 'starg_add_archival_files_nonce'); ?>

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
