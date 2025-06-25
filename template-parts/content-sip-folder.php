<?php if(!isset($pdf)) : ?>
<script src="https://cdn.jsdelivr.net/npm/bigger-picture@1.1.11/dist/bigger-picture.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bigger-picture@1.1.11/dist/bigger-picture.min.css" rel="stylesheet">
<?php endif;

$exif_dates = array();
function HandleArray(&$value, $key, $file_key): void {
	global $exif_dates;
	if(is_array($value)){
		if(array_key_exists('DateTimeOriginal', $value)) {
			$exif_dates[$file_key] = strtotime($value['DateTimeOriginal']);
		}
		array_walk($value,'HandleArray', $file_key);
	}
}

$sip = false;
$file_data = array();
$getID3 = new getID3;
if(isset($_GET['sipFolder']) && $_GET['sipFolder']) {
	$sip = $_GET['sipFolder'];
	if($archival_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_archival_sip_folder' AND meta_value = %s", $_GET['sipFolder']))) {
        $author_id = get_post_field('post_author', $archival_id);
	} else $author_id = get_current_user_id();
} else {
	global $post;
    $sip = get_post_meta($post->ID, '_archival_sip_folder', true);
	$author_id = $post->post_author;
}
if($sip) :
    $sip_folder = carbon_get_theme_option( 'sip_upload_path' ) . $author_id . '/' . $sip . '/';
    $upload_folder = $sip_folder . 'content/';
    $thumbnail_folder = $sip_folder . 'thumb/';
    $it = new RecursiveTreeIterator(new RecursiveDirectoryIterator($upload_folder, RecursiveDirectoryIterator::SKIP_DOTS));
    $ol_close = true;
    $ol_open = false;
    $count = 1;
    $items = 0;
    foreach($it as $path) {
        $items++;
    }
    echo '<div class="container sip">';
	if(!isset($pdf)) echo '<ol class="sip-listing" id="sip-files" type="">';
    foreach($it as $path) {
        $path = trim($path);
        $path_clean = substr($path, strpos($path, '/'));
        $thumb_link = '';
	    $image_size = false;
	    if(!is_dir($path_clean)) {
		    $file_data[basename($path_clean)] = $getID3->analyze($path_clean);
		    array_walk($file_data[basename($path_clean)],'HandleArray', basename($path_clean));
		    $getID3->CopyTagsToComments($file_data[basename($path_clean)]);
		    $file_type = wp_check_filetype($path_clean);
		    if(!array_key_exists('fileformat', $file_data[basename($path_clean)])) {
                $file_data[basename($path_clean)]['fileformat'] = $file_type['ext'];
            }
            //echo '<pre>'.htmlentities(print_r($file_data[basename($path_clean)], true), ENT_SUBSTITUTE).'</pre>';
		    $thumbnail_url = plugin_dir_url(__DIR__ ) . 'assets/img/file-svgrepo-com.svg';
            $file_url = get_bloginfo('url') . substr($path, strrpos($path, '/wp-content'));
	        //print_r($file_type);
	        if(strrpos($file_type['type'], 'application') === 0 ) {
	            if(strtolower($file_type['ext']) === 'pdf') {
	                $thumbnail = $thumbnail_folder.basename($path_clean).'jpg';
	                if(!file_exists($thumbnail)) {
		                if(!create_pdf_thumbnail($path_clean, $thumbnail_folder)) {
		                    $thumbnail_url = plugin_dir_url(__DIR__ ) . 'assets/img/pdf-svgrepo-com.svg';
		                } else {
			                $thumbnail_url = get_bloginfo('url') . substr($thumbnail_folder, strrpos($thumbnail_folder, '/wp-content')) . basename($path) . '.jpg';
                        }
	                }
                } elseif($file_type['ext'] === 'xml') {
	                $thumbnail_url = plugin_dir_url(__DIR__ ) . 'assets/img/xml-svgrepo-com.svg';
                }
		        $thumb_link = '<a data-iframe="' . $file_url . '" href="' . $file_url . '"><img src="' . $thumbnail_url . '" alt="' . basename($path) . '"></a>';
            } elseif(strrpos($file_type['type'], 'image') === 0 ) {
		        $thumbnail_url = plugin_dir_url(__DIR__ ) . 'assets/img/image-file-svgrepo-com.svg';
		        if(strtolower($file_type['ext']) === 'tiff') {
                    $tiff_full_name = str_replace(array('.tiff', '.tif'), '-full.jpg', basename($path));
                    $tiff_thumbnail_name = str_replace(array('.tiff', '.tif'), '.jpg', basename($path));
			        $thumbnail = $thumbnail_folder.$tiff_thumbnail_name;
			        if(!file_exists($thumbnail)) {
				        create_thumbnail($path_clean, $thumbnail_folder);
			        }
                    $thumbnail_url = get_bloginfo('url') . substr($thumbnail_folder, strrpos($thumbnail_folder, '/wp-content')) . $tiff_thumbnail_name;
                    $image_size = getimagesize($thumbnail_folder.$tiff_full_name);
			        $file_url = get_bloginfo('url') . substr($thumbnail_folder.$tiff_full_name, strrpos($thumbnail_folder.$tiff_full_name, '/wp-content'));;
		        } elseif(strtolower($file_type['ext']) === 'svg') {
	                $thumbnail_url = $file_url;
	                preg_match("#viewbox=[\"']\d* \d* (\d*) (\d*)#i", file_get_contents($path_clean), $d);
                    $image_size[0] = ($d[1])?:300;
	                $image_size[1] = ($d[2])?:300;
                } else {
	                $thumbnail = $thumbnail_folder.basename($path_clean);
	                if(!file_exists($thumbnail)) {
		                create_thumbnail($path_clean, $thumbnail_folder);
	                }
	                $thumbnail_url = get_bloginfo('url') . substr($thumbnail_folder, strrpos($thumbnail_folder, '/wp-content')) . basename($path);
			        $image_size = getimagesize($path_clean);
                }
		        $thumb_link = '<a data-width="'.$image_size[0].'" data-height="'.$image_size[1].'" data-thumb="' . $thumbnail_url .'" data-img="' . $file_url . '" href="' . $file_url . '"><img src="' . $thumbnail_url . '" alt="' . basename($path) . '"></a>';
	        } elseif(strrpos($file_type['type'], 'audio') === 0 ) {
		        $thumbnail_url = plugin_dir_url(__DIR__ ) . 'assets/img/audio-file-svgrepo-com.svg';
		        if(file_exists(plugin_dir_path(__DIR__ ) . 'assets/img/' . strtolower($file_type['ext']) . '-svgrepo-com.svg')) {
	                $thumbnail_url = plugin_dir_url(__DIR__ ) . 'assets/img/' . strtolower($file_type['ext']) . '-svgrepo-com.svg';
                }
                $sources = array(
                    array(
                        'src' =>  $file_url,
                        'type' => $file_type['type']
                    )
                );
		        $thumb_link = '<a data-sources=\'' . json_encode($sources, JSON_UNESCAPED_SLASHES) . '\' href="' . $file_url . '"><img src="' . $thumbnail_url . '" alt="' . basename($path) . '"></a>';
	        } elseif(strrpos($file_type['type'], 'video') === 0 ) {
		        $thumbnail_url = plugin_dir_url(__DIR__ ) . 'assets/img/video-file-svgrepo-com.svg';
		        if(file_exists(plugin_dir_path(__DIR__ ) . 'assets/img/' . strtolower($file_type['ext']) . '-svgrepo-com.svg')) {
			        $thumbnail_url = plugin_dir_url(__DIR__ ) . 'assets/img/' . strtolower($file_type['ext']) . '-svgrepo-com.svg';
		        }
		        $sources = array(
			        array(
				        "src" =>  $file_url,
				        "type" => $file_type['type']
			        )
		        );
		        $thumb_link = '<a data-sources=\'' . json_encode($sources,  JSON_UNESCAPED_SLASHES) . '\' href="' . $file_url . '"><img src="' . $thumbnail_url . '" alt="' . basename($path) . '"></a>';
	        } elseif(strrpos($file_type['type'], 'text') === 0 ) {
		        if(file_exists(plugin_dir_path(__DIR__ ) . 'assets/img/' . strtolower($file_type['ext']) . '-svgrepo-com.svg')) {
			        $thumbnail_url = plugin_dir_url(__DIR__ ) . 'assets/img/' . strtolower($file_type['ext']) . '-svgrepo-com.svg';
		        }
		        $thumb_link = '<a data-iframe="' . $file_url . '" href="' . $file_url . '"><img src="' . $thumbnail_url . '" alt="' . basename($path) . '"></a>';
	        }
            if($thumb_link) {
	            echo (!isset($pdf))?'<li>':'<table style="width: 100%"><tr>';
                if(!isset($pdf)) {
                  echo $thumb_link;
                } else {
	                if($image_size && $file_url) {
                        echo '<td style="width: 40%"><img src="' . $file_url . '" alt="' . basename($path) . '"></td>';
                    }
                };
                echo (!isset($pdf))?'<p>':'<td style="width: 60%">';
	            echo (array_key_exists('filename', $file_data[basename($path_clean)]))?$file_data[basename($path_clean)]['filename'] . '<br>':'';
	            echo (array_key_exists('filesize', $file_data[basename($path_clean)]))?formatBytes($file_data[basename($path_clean)]['filesize']) . '<br>':'';
	            echo (array_key_exists('playtime_string', $file_data[basename($path_clean)]))?$file_data[basename($path_clean)]['playtime_string'] . '<br>':'';
	            echo (array_key_exists(basename($path_clean), $exif_dates))?date('c', $exif_dates[basename($path_clean)]) . '<br>':'';
                echo (!isset($pdf))?'</p>':'</td>';
                echo (!isset($pdf))?'</li>':'</tr></table><br>';
            }
		    if(strrpos($path, '\\') !== false && !isset($pdf) ) {
			    echo '</ol>';
		    }
        } elseif(!isset($pdf)) {
		    if(strrpos($path, '\\') !== false && $ol_open === true) {
			    echo '</ol>';
			    $ol_close = true;
			    $ol_open = false;
		    }
            echo '<li class="subfolder">' . basename($path) . '</li><ol type="">';
		    $ol_close = false;
		    $ol_open = true;
	    }
        $count++;
    }
    if(!$ol_close) {
        echo '</ol>';
    }
    //echo '</ol>';
	echo '</div>';
    ?>
<?php if(!isset($pdf)) : ?>
    <script>
        // initialize
        let bp = BiggerPicture({
            target: document.body,
        })

        // grab image links
        let imageLinks = document.querySelectorAll('#sip-files a')

        // add click listener to open BiggerPicture
        for (let link of imageLinks) {
            link.addEventListener("click", openGallery);
        }

        // function to open BiggerPicture
        function openGallery(e) {
            e.preventDefault()
            bp.open({
                items: imageLinks,
                el: e.currentTarget,
            })
        }
    </script>
    <?php endif; ?>
<?php endif; ?>