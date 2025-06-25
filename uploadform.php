<?php
$sip_time = time();
$sip_max_size = (carbon_get_theme_option( 'sip_max_size') )?:50000000;

function human_filesize($bytes, $decimals = 2) {
	$sz = array('B','KB','MB','GB','TB','PB');
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1000, $factor)) . $sz[$factor];
}
?>

<link rel="stylesheet" id="dropzone-css" href="<?= plugin_dir_url(__FILE__ ).'vendor/enyo/dropzone/dist/min/basic.min.css'; ?>" media="all" />
<link rel="stylesheet" id="dropzone-css" href="<?= plugin_dir_url(__FILE__ ).'vendor/enyo/dropzone/dist/min/dropzone.min.css'; ?>" media="all" />
<script src="<?= plugin_dir_url(__FILE__ ) . 'vendor/enyo/dropzone/dist/min/dropzone.min.js'; ?>" id="dropzone-js"></script>

<script>
    Dropzone.options.archivalUploadForm = { // The camelized version of the ID of the form element
        dictDefaultMessage: "<?php _e('Drag and drop the files here to upload', 'sip'); ?>",
        // The configuration we've talked about above
        //autoProcessQueue: false,
        //uploadMultiple: true,
        //parallelUploads: 100,
        //maxFiles: 100,
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
                if(file.fullPath){
                    data.append("fullPath", file.fullPath);
                }
            });

            sipDropzone.on("removedfile", function(file ) {
                let xhr = new XMLHttpRequest();
                xhr.open("POST", "<?= plugin_dir_url(__FILE__ ).'archival-remove.php'; ?>");
                xhr.setRequestHeader("Accept", "application/json");
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                let removepath = (file.fullPath)?file.fullPath:file.upload.filename;

                let data = 'deletaPath=' + removepath + '&sipUserID=<?= get_current_user_id(); ?>&sipFolder=SIP-<?= $sip_time; ?>';

                xhr.send(data);

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4) {
                        const response = JSON.parse(xhr.responseText);
                        if(response.sip_size > <?= $sip_max_size; ?>) {
                            alert('<?= sprintf(__('The maximum size of %s for the SIP has been exceeded. Files need to be removed.','sip'), human_filesize($sip_max_size)); ?>');
                            submitButton.disabled = true;
                        } else submitButton.disabled = false;

                        if(sipDropzone.files.length === 0) {
                            submitButton.disabled = true;
                        }
                    }
                };
            });

            sipDropzone.on("success", function(file, response) {
                if(response.infected) {
                    sipDropzone.removeFile(file);
                    alert("<?php _e('Virus detected on:','sip'); ?>" + response.infected);
                }
                if(response.not_supported) {
                    sipDropzone.removeFile(file);
                    alert("<?php _e('Not supported file type:','sip'); ?>" + response.not_supported);
                }
                if(response.sip_full) {
                    sipDropzone.removeFile(file);
                    alert("<?php _e('Further uploads are not possible. Files must be deleted first.','sip'); ?>");
                }
                if(sipDropzone.files.length != 0) {
                    submitButton.disabled = false;
                } else submitButton.disabled = true;

                if(response.sip_size > <?= $sip_max_size; ?>) {
                    alert("<?= sprintf(__('The maximum size of %s for the SIP has been exceeded. Files need to be removed.','sip'), human_filesize($sip_max_size)); ?>");
                    submitButton.disabled = true;
                }
            });
        }
    }
</script>
<div class="container sip">

    <form action="<?= plugin_dir_url(__FILE__ ).'archival-upload.php'; ?>" class="dropzone" id="archivalUploadForm">
        <input type="hidden" name="sipUserID" value="<?= get_current_user_id(); ?>">
        <input type="hidden" name="sipFolder" value="SIP-<?= $sip_time; ?>">
    </form>

    <form action="" method="get">
        <input type="hidden" name="sipFolder" value="SIP-<?= $sip_time; ?>">
        <input id="submit_sip" type="submit" value="<?php _e('Complete upload and insert metadata', 'sip'); ?>" disabled>
    </form>

</div>
