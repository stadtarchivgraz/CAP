<?php
/**
 * Template part to display an interactive map with leaflet and MapTiler.
 */

$markers   = array();
$area      = '';
$count     = 0;
$map_posts = array();

$key          = esc_attr(carbon_get_theme_option('sip_map_default_google_api_key'));
$default_lng  = esc_attr(carbon_get_theme_option('sip_map_default_lng'));
$default_lat  = esc_attr(carbon_get_theme_option('sip_map_default_lat'));
$default_zoom = esc_attr(carbon_get_theme_option('sip_map_default_zoom'));

if (is_singular('archival') || is_page_template('sip-archival.php')) {
	global $post;
	$map_posts[] = $post;
} elseif ($archival) {
	// @var array $archival is set in files like "template-parts/content-sip-form.php" and should contain a single WP_Post object.
	$map_posts[] = $archival;
}

$is_sip_upload_template = is_page_template('sip-upload.php');
$search_position        = ( $is_sip_upload_template ) ? 'topright' : 'topleft';
$search_collapsed       = ( $is_sip_upload_template ) ? 'false'    : 'true';

// todo: refactor! create a function in /inc/sip-functions.php
foreach ($map_posts as $key => $map_post) :
	$markers[ $key ] = starg_get_map_coordinates_by_post_id( $map_post->ID );

	// todo: maybe escape. should be json.
	$area = get_post_meta( $map_post->ID, '_archival_area', true );

endforeach; // End of the loop.
wp_reset_postdata(); // todo: should not be needed here. we're not in a custom loop!

if ( isset( $markers[0] ) && isset( $markers[0]['lat'] ) && isset( $markers[0]['lng'] ) ) {
	$default_lat = $markers[0]['lat'];
	$default_lng = $markers[0]['lng'];
}

?>
<div id="map" class="container sip"></div>
<?php if ( $is_sip_upload_template ) : ?>
	<p><a class="has-text-danger is-small-text" href="#" id="mapClear"><?php _e('Reset map', 'sip'); ?></a></p>
<?php endif; ?>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		L.Control.prototype._refocusOnMap = function _refocusOnMap() {};

		window.map = new L.Map('map', {
				scrollWheelZoom: false,
				maxZoom: 23
			})
			.setView(new L.LatLng(<?php echo $default_lat; ?>, <?php echo $default_lng; ?>), <?php echo $default_zoom; ?>);

		// todo: Check the key and consider adding an option for it in the plugin.
		// One needs to create an (free) account at https://www.maptiler.com/cloud/pricing/ to create the key at https://cloud.maptiler.com/account/keys/ see https://docs.maptiler.com/cloud/api/authentication-key/.
		const key = '4iSHuzysTSEXKZ9TxnfO';
		L.tileLayer(`https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=${key}`, { //style URL
			tileSize: 512,
			zoomOffset: -1,
			minZoom: 1,
			attribution: "\u003ca href=\"https://www.maptiler.com/copyright/\" target=\"_blank\"\u003e\u0026copy; MapTiler\u003c/a\u003e \u003ca href=\"https://www.openstreetmap.org/copyright\" target=\"_blank\"\u003e\u0026copy; OpenStreetMap contributors\u003c/a\u003e",
			crossOrigin: true
		}).addTo(map);

		let inputMarker, clickMarker;

		<?php
		if ($key) echo 'const MapGeoCoderProvider = L.Control.Geocoder.google(\'' . $key . '\');';
		?>

		const MapGeoCoder = L.Control.geocoder({
				position: '<?php echo $search_position; ?>',
				collapsed: <?php echo $search_collapsed; ?>,
				placeholder: '<?php esc_html_e( "Place/Address...", "sip" ); ?>',
				errorMessage: '<?php esc_html_e( "Nothing found.", "sip" ); ?>',
				<?php if ($key) echo 'geocoder: MapGeoCoderProvider,'; ?>
				defaultMarkGeocode: false
			})
			.on('markgeocode', function(e) {
				const place_address_input = document.getElementById('archival-address'),
					place_lat_input = document.getElementById('archival-lat'),
					place_lng_input = document.getElementById('archival-lng'),
					place_area = document.getElementById('archival-area');
				const dsaIcon = L.divIcon({
					className: 'dsa-custom-pin',
					iconAnchor: [0, 15],
					popupAnchor: [0, -30],
					html: '<div style="background-color: <?php echo ( $is_sip_upload_template ) ? 'cornflowerblue' : '#BDC4E0'; ?>"><i class="fas fa-map-pin"></i></div>'
				});
				if (areaSelection) {
					areaSelection.clearMarkers();
					areaSelection.clearGhostMarkers();
					areaSelection.clearPolygon();
				}
				if (inputMarker) {
					map.removeLayer(inputMarker);
				}
				if (clickMarker) {
					map.removeLayer(clickMarker);
				}
				<?php if ( $is_sip_upload_template ) : ?>
					if (markers) {
						map.removeLayer(markers);
					}
				<?php endif; ?>
				if (place_address_input) {
					place_address_input.value = e.geocode.name
				}
				if (place_lat_input) {
					place_lat_input.value = e.geocode.center['lat'];
				}
				if (place_lng_input) {
					place_lng_input.value = e.geocode.center['lng'];
				}
				if (place_area) {
					place_area.value = '';
				}
				const latlng = e.geocode.center;
				inputMarker = L.marker(latlng, {
					icon: dsaIcon
				}).bindPopup("<p>" + e.geocode.name + "</p>").addTo(map).openPopup();
				map.fitBounds(e.geocode.bbox);
			})
			.addTo(map);

		const markers = L.markerClusterGroup({
			iconCreateFunction: function(cluster) {
				return L.divIcon({
					className: 'dsa-custom-pin',
					iconAnchor: [0, 15],
					popupAnchor: [0, -30],
					html: '<div class="cluster" style=\"background-color: cornflowerblue\"><div>' + cluster.getChildCount() + '</div></div>'
				});
			},
			showCoverageOnHover: false,
			maxClusterRadius: 50
		});

		const customControl = L.Control.extend({

			options: {
				position: 'topleft'
			},

			onAdd: function(map) {
				const container = L.DomUtil.create('input');
				container.type = "button";
				container.title = '<?php esc_html_e( "My position", "sip" ); ?>';
				container.value = "";

				container.style.backgroundColor = 'white';
				container.style.backgroundImage = "url('<?php echo STARG_SIP_PLUGIN_BASE_URL; ?>assets/img/street-view.svg')";
				container.style.backgroundSize = "60%";
				container.style.backgroundRepeat = "no-repeat";
				container.style.backgroundPosition = "center";
				container.style.backgroundClip = "padding-box";
				container.style.boxShadow = "0 1px 5px rgb(0 0 0 / 65%)";
				container.style.border = "none";
				container.style.borderRadius = "4px";
				container.style.width = '28px';
				container.style.height = '28px';

				container.onmouseover = function() {
					container.style.cursor = 'pointer';
				}

				container.onclick = function() {
					getPosition();
				}

				return container;
			}
		});
		map.addControl(new customControl());

		<?php // The selected area on the viewing page should not be changed... ?>
		<?php if ( $is_sip_upload_template ) : ?>
			map.on('click', function(e) {
				if (areaSelection.phase === 'inactive') {
					const place_address_input = document.getElementById('archival-address'),
						place_lat_input = document.getElementById('archival-lat'),
						place_lng_input = document.getElementById('archival-lng'),
						place_area = document.getElementById('archival-area');
					const dsaIcon = L.divIcon({
						className: 'dsa-custom-pin',
						iconAnchor: [0, 15],
						popupAnchor: [0, -30],
						html: '<div style="background-color: cornflowerblue"><i class="fas fa-map-pin"></i></div>'
					});

					document.querySelector('.leaflet-control-geocoder-form input').value = '';
					MapGeoCoder.options.geocoder.reverse(e.latlng, map.options.crs.scale(map.getZoom()), function(results) {
						const r = results[0];
						if (r) {
							if (inputMarker) {
								map.removeLayer(inputMarker);
							}
							if (clickMarker) {
								map.removeLayer(clickMarker);
							}
							if (markers) {
								map.removeLayer(markers);
							}
							if (areaSelection) {
								areaSelection.clearMarkers();
								areaSelection.clearGhostMarkers();
								areaSelection.clearPolygon();
							}
							if (place_address_input) {
								place_address_input.value = r.name;
							}
							if (place_lat_input) {
								place_lat_input.value = r.center['lat'];
							}
							if (place_lng_input) {
								place_lng_input.value = r.center['lng'];
							}
							if (place_area) {
								place_area.value = '';
							}
							console.log(r);
							clickMarker = L.marker(r.center, {
								icon: dsaIcon
							}).bindPopup(r.html || r.name).addTo(map).openPopup();
						}
					})
				}
			});
		<?php endif; // end $is_sip_upload_template ?>

		<?php // but we do want to display the selected area. ?>
		const areaSelection = new window.leafletAreaSelection.DrawAreaSelection({
			onPolygonReady: (polygon) => {
				const place_address_input = document.getElementById('archival-address'),
					place_lat_input = document.getElementById('archival-lat'),
					place_lng_input = document.getElementById('archival-lng'),
					place_area = document.getElementById('archival-area');

				if (place_area) {
					place_area.value = JSON.stringify(polygon.toGeoJSON());
				}
				if (place_address_input) {
					place_address_input.value = '';
				}
				if (place_lat_input) {
					place_lat_input.value = '';
				}
				if (place_lng_input) {
					place_lng_input.value = '';
				}
			},
			onButtonActivate: () => {
				if (inputMarker) {
					map.removeLayer(inputMarker);
				}
				if (clickMarker) {
					map.removeLayer(clickMarker);
				}
				if (markers) {
					map.removeLayer(markers);
				}
			},
			position: 'topleft'
		});
		map.addControl(areaSelection);

		<?php
		if ( json_decode($area) ) :
			if ( is_singular( 'archival' ) ) :
				?>
				L.geoJSON(<?php echo $area; ?>).addTo(map);
			<?php
			else:
				$coordinates = json_decode($area)->geometry->coordinates[0];
				?>
				const brect = map.getContainer().getBoundingClientRect();
				console.log(brect);
				<?php foreach ($coordinates as $i => $coordinate) : ?>
					let point_<?php echo $i + 1; ?> = map.latLngToContainerPoint([<?php echo $coordinate[1]; ?>, <?php echo $coordinate[0]; ?>]);
					map.fire("as:point-add",
						new MouseEvent("click", {
							clientX: point_<?php echo $i + 1; ?>.x + brect.left,
							clientY: point_<?php echo $i + 1; ?>.y + +brect.top
						})
					);
				<?php endforeach; ?>
				map.fire("as:point-add",
					new MouseEvent("click", {
						clientX: point_1.x + brect.left,
						clientY: point_1.y + +brect.top
					})
				);
			<?php endif; ?>
		<?php endif; ?>

		<?php
		foreach ($markers as $marker) {
			if ( isset( $marker['lat'] ) && isset( $marker['lng'] ) ) {

				echo "const dsaIcon = L.divIcon({
					className: 'dsa-custom-pin',
					iconAnchor: [0, 15],
					popupAnchor: [0, -30],
					html: '<div style=\"background-color: cornflowerblue\"><i class=\"marker-icon\"></i></div>'
				});";
				echo 'const marker = L.marker([' . $marker["lat"] . ', ' . $marker["lng"] . '], {icon: dsaIcon}).bindPopup("<div class=\"popup-header\"><p class=\"popup-title\"><a href=\"' . $marker["permalink"] . '\">' . addslashes($marker["title"]) . '</a></p><p class=\"popup-subtitle\">' . addslashes($marker["place_address"]) . '</p></div>");';
				echo 'markers.addLayer(marker);';
			}
		}
		?>
		map.addLayer(markers);

		let id, options;

		options = {
			enableHighAccuracy: true,
			timeout: 5000,
			maximumAge: 0
		};

		function getPosition() {
			if (navigator.geolocation) {
				navigator.geolocation.watchPosition(setPositionMarker, error, options);
			} else {

			}
		}

		let positionMarker = false;

		function setPositionMarker(position) {
			const dsaIcon = L.divIcon({
				className: 'dsa-custom-pin',
				iconAnchor: [0, 15],
				popupAnchor: [0, -30],
				html: '<div class="position" style=\"background-color: #17C387\"><i class=\"fas fa-street-view\"></i></div>'
			});
			if (!positionMarker) {
				positionMarker = L.marker([position.coords.latitude, position.coords.longitude], {
					icon: dsaIcon
				}).addTo(map);
				map.flyTo(new L.LatLng(position.coords.latitude, position.coords.longitude), map.getZoom(), {
					animate: true,
					duration: 0.5
				});
			} else positionMarker.setLatLng(new L.LatLng(position.coords.latitude, position.coords.longitude));
		}

		function error(err) {
			console.warn('ERROR(' + err.code + '): ' + err.message);
		}

		<?php if ( $is_sip_upload_template ) : ?>
			const mapClear = document.getElementById('mapClear');
			mapClear.addEventListener("click", (e) => {

				e.preventDefault();

				const place_address_input = document.getElementById('archival-address'),
					place_lat_input = document.getElementById('archival-lat'),
					place_lng_input = document.getElementById('archival-lng'),
					place_area = document.getElementById('archival-area');
				if (inputMarker) {
					map.removeLayer(inputMarker);
				}
				if (clickMarker) {
					map.removeLayer(clickMarker);
				}
				if (markers) {
					map.removeLayer(markers);
				}
				if (areaSelection) {
					areaSelection.clearMarkers();
					areaSelection.clearGhostMarkers();
					areaSelection.clearPolygon();
				}
				if (place_address_input) {
					place_address_input.value = ''; //todo: maybe restore previously saved data on post-update!
				}
				if (place_lat_input) {
					place_lat_input.value = ''; //todo: maybe restore previously saved data on post-update!
				}
				if (place_lng_input) {
					place_lng_input.value = ''; //todo: maybe restore previously saved data on post-update!
				}
				if (place_area) {
					place_area.value = ''; //todo: maybe restore previously saved data on post-update!
				}
			});
		<?php endif; ?>
	});
</script>
