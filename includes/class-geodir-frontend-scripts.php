<?php
/**
 * Handle frontend scripts
 *
 * @class       GeoDir_Frontend_Scripts
 * @version     2.0.0
 * @package     GeoDirectory
 * @category    Class
 * @author      GeoDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoDir_Frontend_Scripts Class.
 */
class GeoDir_Frontend_Scripts {

	/**
	 * Contains an array of script handles registered by GeoDir.
	 * @var array
	 */
	private static $scripts = array();

	/**
	 * Contains an array of script handles registered by GeoDir.
	 * @var array
	 */
	private static $styles = array();

	/**
	 * Contains an array of script handles localized by GeoDir.
	 * @var array
	 */
	private static $wp_localize_scripts = array();

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
		add_action( 'wp_print_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );

		// locations scripts
		add_action('wp_footer', array( __CLASS__, 'js_location_functions' )); //@todo this script needs overhalled

		// fix script conflicts, eg flexslider being added twice
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'fix_script_conflicts'), 100 );

		// allow async tags
		add_filter('clean_url', array( __CLASS__, 'js_async'), 11, 1);

	}


	/**
	 * Adds async tag to javascript for faster page loading.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory
	 * @param string $url The javascript file url.
	 * @return string The modified javascript string.
	 */
	public static function js_async($url)
	{
		if (strpos($url, '#asyncload')===false)
			return $url;
		else
			return str_replace('#asyncload', '', $url)."' defer async='async";
	}

	/**
	 * Dequeue scripts to fix JS conflicts.
	 *
	 * @since 1.6.22
	 */
	public static function fix_script_conflicts() {
		if(geodir_is_page('single')) {
			if ( wp_script_is( 'flexslider', 'enqueued' ) && wp_script_is( 'jquery-flexslider', 'enqueued' ) ) {
				wp_dequeue_script( 'flexslider' );
			}
		}
	}

	/**
	 * Prints location related javascript.
	 *
	 * @since 1.0.0
	 * @since 1.6.16 Fix: Single quote in default city name causes problem in add new city.
	 * @package GeoDirectory
	 */
	public static function js_location_functions() {
		global $geodirectory;
		$default_search_for_text = geodir_get_option( 'search_default_text' );
		if ( ! $default_search_for_text ) {
			$default_search_for_text = geodir_get_search_default_text();
		}

		$default_near_text = geodir_get_option( 'search_default_near_text' );
		if ( ! $default_near_text ) {
			$default_near_text = geodir_get_search_default_near_text();
		}

		$search_location = $geodirectory->location->get_default_location();

		$default_search_for_text = addslashes(stripslashes($default_search_for_text));
		$default_near_text = addslashes(stripslashes($default_near_text));
		$city = !empty($search_location) ? addslashes(stripslashes($search_location->city)) : '';
		?>
		<script type="text/javascript">
			var default_location = '<?php echo $city ;?>';
			var latlng;
			var address;
			var dist = 0;
			var Sgeocoder = (typeof google!=='undefined' && typeof google.maps!=='undefined') ? new google.maps.Geocoder() : {};

			function geodir_setup_submit_search() {
				jQuery('.geodir_submit_search').unbind('click');// unbind any other click events
				jQuery('.geodir_submit_search').click(function(e) {

					e.preventDefault();

					var s = ' ';
					var $form = jQuery(this).closest('form');

					if (jQuery("#sdist input[type='radio']:checked").length != 0) dist = jQuery("#sdist input[type='radio']:checked").val();
					if (jQuery('.search_text', $form).val() == '' || jQuery('.search_text', $form).val() == '<?php echo $default_search_for_text;?>') jQuery('.search_text', $form).val(s);

					// Disable location based search for disabled location post type.
					if (jQuery('.search_by_post', $form).val() != '' && typeof gd_cpt_no_location == 'function') {
						if (gd_cpt_no_location(jQuery('.search_by_post', $form).val())) {
							jQuery('.snear', $form).remove();
							jQuery('.sgeo_lat', $form).remove();
							jQuery('.sgeo_lon', $form).remove();
							jQuery('select[name="sort_by"]', $form).remove();
							jQuery($form).submit();
							return;
						}
					}

					if (dist > 0 || (jQuery('select[name="sort_by"]').val() == 'nearest' || jQuery('select[name="sort_by"]', $form).val() == 'farthest') || (jQuery(".snear", $form).val() != '' && jQuery(".snear", $form).val() != '<?php echo $default_near_text;?>')) {
						geodir_setsearch($form);
					} else {
						jQuery(".snear", $form).val('');
						jQuery($form).submit();
					}
				});
			}

			jQuery(document).ready(function() {
				geodir_setup_submit_search();
				//setup advanced search form on form ajax load
				jQuery("body").on("geodir_setup_search_form", function(){
					geodir_setup_submit_search();
				});
			});

			function geodir_setsearch($form) {
				if ((dist > 0 || (jQuery('select[name="sort_by"]', $form).val() == 'nearest' || jQuery('select[name="sort_by"]', $form).val() == 'farthest')) && (jQuery(".snear", $form).val() == '' || jQuery(".snear", $form).val() == '<?php echo $default_near_text;?>')) jQuery(".snear", $form).val(default_location);
				geocodeAddress($form);
			}

			function updateSearchPosition(latLng, $form) {
				if (window.gdMaps === 'google') {
					jQuery('.sgeo_lat').val(latLng.lat());
					jQuery('.sgeo_lon').val(latLng.lng());
				} else if (window.gdMaps === 'osm') {
					jQuery('.sgeo_lat').val(latLng.lat);
					jQuery('.sgeo_lon').val(latLng.lon);
				}
				jQuery($form).submit(); // submit form after insering the lat long positions
			}

			function geocodeAddress($form) {
				// Call the geocode function
				Sgeocoder = window.gdMaps == 'google' ? new google.maps.Geocoder() : null;

				if (jQuery('.snear', $form).val() == '' || ( jQuery('.sgeo_lat').val() != '' && jQuery('.sgeo_lon').val() != ''  ) || jQuery('.snear', $form).val().match("^<?php _e('In:','geodirectory');?>")) {
					if (jQuery('.snear', $form).val().match("^<?php _e('In:','geodirectory');?>")) {
						jQuery(".snear", $form).val('');
					}
					jQuery($form).submit();
				} else {
					var address = jQuery(".snear", $form).val();

					if (jQuery('.snear', $form).val() == '<?php echo $default_near_text;?>') {
						initialise2();
					} else {
						<?php
						$near_add = geodir_get_option('search_near_addition');
						/**
						 * Adds any extra info to the near search box query when trying to geolocate it via google api.
						 *
						 * @since 1.0.0
						 */
						$near_add2 = apply_filters('geodir_search_near_addition', '');
						?>
						if (window.gdMaps === 'google') {
							Sgeocoder.geocode({'address': address<?php echo ($near_add ? '+", ' . $near_add . '"' : '') . $near_add2;?>},
								function (results, status) {
									if (status == google.maps.GeocoderStatus.OK) {
										updateSearchPosition(results[0].geometry.location, $form);
									} else {
										alert("<?php esc_attr_e('Search was not successful for the following reason :', 'geodirectory');?>" + status);
									}
								});
						} else if (window.gdMaps === 'osm') {
							geocodePositionOSM(false, address, false, false,
								function(geo) {
									if (typeof geo !== 'undefined' && geo.lat && geo.lon) {
										updateSearchPosition(geo, $form);
									} else {
										alert("<?php esc_attr_e('Search was not successful for the requested address.', 'geodirectory');?>");
									}
								});
						} else {
							jQuery($form).submit();
						}
					}
				}
			}

			function initialise2() {
				if (!window.gdMaps) {
					return;
				}

				if (window.gdMaps === 'google') {
					var latlng = new google.maps.LatLng(56.494343, -4.205446);
					var myOptions = {
						zoom: 4,
						mapTypeId: google.maps.MapTypeId.TERRAIN,
						disableDefaultUI: true
					}
				} else if (window.gdMaps === 'osm') {
					var latlng = new L.LatLng(56.494343, -4.205446);
					var myOptions = {
						zoom: 4,
						mapTypeId: 'TERRAIN',
						disableDefaultUI: true
					}
				}
				try { prepareGeolocation(); } catch (e) {}
				doGeolocation();
			}

			function doGeolocation() {
				if (navigator.geolocation) {
					navigator.geolocation.getCurrentPosition(positionSuccess, positionError);
				} else {
					positionError(-1);
				}
			}

			function positionError(err) {
				var msg;
				switch (err.code) {
					case err.UNKNOWN_ERROR:
						msg = "<?php _e('Unable to find your location','geodirectory');?>";
						break;
					case err.PERMISSION_DENINED:
						msg = "<?php _e('Permission denied in finding your location','geodirectory');?>";
						break;
					case err.POSITION_UNAVAILABLE:
						msg = "<?php _e('Your location is currently unknown','geodirectory');?>";
						break;
					case err.BREAK:
						msg = "<?php _e('Attempt to find location took too long','geodirectory');?>";
						break;
					default:
						msg = "<?php _e('Location detection not supported in browser','geodirectory');?>";
				}
				jQuery('#info').html(msg);
			}

			function positionSuccess(position) {
				var coords = position.coords || position.coordinate || position;
				jQuery('.sgeo_lat').val(coords.latitude);
				jQuery('.sgeo_lon').val(coords.longitude);

				jQuery('.geodir-listing-search').submit();
			}

			/**
			 * On unload page do some cleaning so back button cache does not store these values.
			 */
			window.onunload = function(){
				if(jQuery('.sgeo_lat').length ){
					jQuery('.sgeo_lat').val('');
					jQuery('.sgeo_lon').val('');
				}
			};

		</script>
		<?php
	}

    /**
     * Return protocol relative asset URL.
     *
     * @since 2.0.0
     *
     * @param string $path URL Path.
     * @return string
     */
	private static function get_asset_url( $path ) {
		return str_replace( array( 'http:', 'https:' ), '', plugins_url( $path, geodir_plugin_url() ) );
	}

	/**
	 * Register a script for use.
     *
     * @since 2.0.0
	 *
	 * @uses   wp_register_script()
	 * @access private
	 * @param  string   $handle Handle.
	 * @param  string   $path Path.
	 * @param  array $deps Optional.  Deps. Default jquery.
	 * @param  string   $version Optional. Version Default GEODIRECTORY_VERSION.
	 * @param  boolean  $in_footer Optional. In footer. Default true.
	 */
	private static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = GEODIRECTORY_VERSION, $in_footer = true ) {
		self::$scripts[] = $handle;
		wp_register_script( $handle, $path, $deps, $version, $in_footer );
	}

	/**
	 * Register and enqueue a script for use.
     *
     * @since 2.0.0
	 *
	 * @uses   wp_enqueue_script()
	 * @access private
	 * @param  string   $handle Handle.
	 * @param  string   $path Optional. Script path. Default null.
	 * @param  array $deps Optional. Deps. Default jquery.
	 * @param  string   $version Optional. Version. Default GEODIRECTORY_VERSION.
	 * @param  boolean  $in_footer Optional. In footer. Default true.
	 */
	public static function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = GEODIRECTORY_VERSION, $in_footer = true ) {
		if ( ! in_array( $handle, self::$scripts ) && $path ) {
			self::register_script( $handle, $path, $deps, $version, $in_footer );
		}
		wp_enqueue_script( $handle );
	}

	/**
	 * Register a style for use.
     *
     * @since 2.0.0
	 *
	 * @uses   wp_register_style()
	 * @access private
	 * @param  string   $handle Handle.
	 * @param  string   $path Style path.
	 * @param  array  $deps Optional. Deps. Default array.
	 * @param  string   $version Optional. Version. Default GEODIRECTORY_VERSION.
	 * @param  string   $media Optional. Media. Default all.
	 * @param  boolean  $has_rtl Optional. Has rtl. Default false.
	 */
	private static function register_style( $handle, $path, $deps = array(), $version = GEODIRECTORY_VERSION, $media = 'all', $has_rtl = false ) {
		self::$styles[] = $handle;
		wp_register_style( $handle, $path, $deps, $version, $media );

		if ( $has_rtl ) {
			wp_style_add_data( $handle, 'rtl', 'replace' );
		}
	}

	/**
	 * Register and enqueue a styles for use.
     *
     * @since 2.0.0
	 *
	 * @uses   wp_enqueue_style()
	 * @access private
	 * @param  string   $handle Handle.
	 * @param  string   $path Optional. Style path. Default null.
	 * @param  array $deps Optional. Deps. Default array.
	 * @param  string   $version Optional. Version. Default GEODIRECTORY_VERSION.
	 * @param  string   $media Optional. Media. Default all.
	 * @param  boolean  $has_rtl Optional. Has rtl. Default false.
	 */
	private static function enqueue_style( $handle, $path = '', $deps = array(), $version = GEODIRECTORY_VERSION, $media = 'all', $has_rtl = false ) {
		if ( ! in_array( $handle, self::$styles ) && $path ) {
			self::register_style( $handle, $path, $deps, $version, $media, $has_rtl );
		}
		wp_enqueue_style( $handle );
	}

	/**
	 * Register all GeoDir scripts.
     *
     * @since 2.0.0
	 */
	private static function register_scripts() {

		$map_lang = "&language=" . GeoDir_Maps::map_language();
		$map_key = GeoDir_Maps::google_api_key(true);
		/** This filter is documented in geodirectory_template_tags.php */
		$map_extra = apply_filters('geodir_googlemap_script_extra', '');

		$suffix           = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$register_scripts = array(
			'select2' => array(
				'src'     => geodir_plugin_url() . '/assets/js/select2/select2.full' . $suffix . '.js',
				'deps'    => array( 'jquery' ),
				'version' => '4.0.4',
			),
			'geodir-select2' => array(
				'src'     => geodir_plugin_url() . '/assets/js/geodir-select2' . $suffix . '.js',
				'deps'    => array( 'jquery', 'select2' ),
				'version' => GEODIRECTORY_VERSION,
			),
			'geodir-jquery-ui-timepicker' => array(
				'src'     => geodir_plugin_url() . '/assets/js/jquery.ui.timepicker' . $suffix . '.js',
				'deps'    => array('jquery-ui-datepicker', 'jquery-ui-slider'),
				'version' => GEODIRECTORY_VERSION,
			),
			'geodir-google-maps' => array(
				'src'     => 'https://maps.google.com/maps/api/js?' . $map_lang . $map_key . $map_extra,
				'deps'    => array(),
				'version' => '',
			),
			'geodir-g-overlappingmarker' => array(
				'src'     => geodir_plugin_url() . '/assets/jawj/oms' . $suffix . '.js',
				'deps'    => array( 'jquery' ),
				'version' => GEODIRECTORY_VERSION,
			),
			'geodir-leaflet' => array(
				'src'     => geodir_plugin_url() . '/assets/leaflet/leaflet' . $suffix . '.js',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
			),
			'leaflet-routing-machine' => array(
				'src'     =>  geodir_plugin_url() . '/assets/leaflet/routing/leaflet-routing-machine' . $suffix . '.js',
				'deps'    => array('geodir-leaflet'),
				'version' => GEODIRECTORY_VERSION,
			),
			'geodir-leaflet-geo' => array(
				'src'     => geodir_plugin_url() . '/assets/leaflet/osm.geocode' . $suffix . '.js',
				'deps'    => array('geodir-leaflet'),
				'version' => GEODIRECTORY_VERSION,
			),
			'geodir-o-overlappingmarker' => array(
				'src'     =>  geodir_plugin_url() . '/assets/jawj/oms-leaflet' . $suffix . '.js',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
			),
			'geodir-goMap' => array(
				'src'     => geodir_plugin_url() . '/assets/js/goMap' . $suffix . '.js',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
			),
			'geodir-map-widget' => array(
				'src'     => geodir_plugin_url() . '/assets/js/map' . $suffix . '.js',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
			),
			'geodir-plupload' => array(
				'src'     => geodir_plugin_url() . '/assets/js/geodirectory-plupload' . $suffix . '.js',
				'deps'    => array('plupload','jquery','jquery-ui-sortable'),
				'version' => GEODIRECTORY_VERSION,
			),
			'geodir' => array(
				'src'     =>  geodir_plugin_url() . '/assets/js/geodirectory' . $suffix . '.js',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
			),
			'jquery-flexslider' => array(
				'src'     => geodir_plugin_url() . '/assets/js/jquery.flexslider' . $suffix . '.js',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
			),
			'geodir-add-listing' => array(
				'src'     => geodir_plugin_url() . '/assets/js/add-listing' . $suffix . '.js',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
			),
			'geodir_lity' => array(
				'src'     => geodir_plugin_url() . '/assets/js/libraries/gd_lity' . $suffix . '.js',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
			),
			'font-awesome' => array(
				'src'     => 'https://use.fontawesome.com/releases/v5.1.0/js/all.js#asyncload',
				'deps'    => array('font-awesome-shim'),
				'version' => GEODIRECTORY_VERSION,
			),
			'font-awesome-shim' => array(
				'src'     => 'https://use.fontawesome.com/releases/v5.1.0/js/v4-shims.js#asyncload',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
			),
		);
		foreach ( $register_scripts as $name => $props ) {
			self::register_script( $name, $props['src'], $props['deps'], $props['version'] );
		}
	}

	/**
	 * Register all GeoDir styles.
     *
     * @since 2.0.0
	 */
	private static function register_styles() {
		$register_styles = array(
			'select2' => array(
				'src'     =>  geodir_plugin_url() . '/assets/css/select2/select2.css',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
				'has_rtl' => false,
			),
			'geodir-core' => array(
				'src'     => geodir_plugin_url() . '/assets/css/gd_core_frontend.css',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
				'has_rtl' => false,
			),
			'geodir-rtl' => array(
				'src'     => geodir_plugin_url() . '/assets/css/rtl-frontend.css',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
				'has_rtl' => true,
			),
			'leaflet' => array(
				'src'     => geodir_plugin_url() . '/assets/leaflet/leaflet.css',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
				'has_rtl' => false,
			),
			'leaflet-routing-machine' => array(
				'src'     => geodir_plugin_url() . '/assets/leaflet/routing/leaflet-routing-machine.css',
				'deps'    => array(),
				'version' => GEODIRECTORY_VERSION,
				'has_rtl' => false,
			),
		);
		foreach ( $register_styles as $name => $props ) {
			self::register_style( $name, $props['src'], $props['deps'], $props['version'], 'all', $props['has_rtl'] );
		}
	}

	/**
	 * Register/queue frontend scripts.
     *
     * @since 2.0.0
	 */
	public static function load_scripts() {
		global $post;

		// register scripts/styles
		self::register_scripts();
		self::register_styles();

		// global enqueues
		// css
		self::enqueue_style( 'select2' );
		self::enqueue_style( 'geodir-core' );
		// js
		self::enqueue_script( 'font-awesome' );
		self::enqueue_script( 'select2' );
		self::enqueue_script( 'geodir-select2' );
		self::enqueue_script( 'geodir' );
		self::enqueue_script( 'geodir_lity' );


		//rtl
		if(is_rtl()){
			self::enqueue_style( 'geodir-rtl' );
		}


		// add-listing
		if(geodir_is_page('add-listing')){
			self::enqueue_script( 'geodir-plupload' );
			self::enqueue_script( 'geodir-add-listing' );
			self::enqueue_script( 'geodir-jquery-ui-timepicker' );

			wp_enqueue_script( 'jquery-ui-autocomplete' ); // add listing only?

		}

		// details page
		if(geodir_is_page('single')){
			//self::enqueue_script( 'jquery-flexslider' ); // moved to widget
		}


		// Maps
		$geodir_map_name = GeoDir_Maps::active_map();
		if (in_array($geodir_map_name, array('auto', 'google'))) {
			self::enqueue_script('geodir-google-maps');
			self::enqueue_script('geodir-g-overlappingmarker');
		}elseif($geodir_map_name == 'osm'){
			self::enqueue_style('leaflet');
			self::enqueue_style('leaflet-routing-machine');

			self::enqueue_script('geodir-leaflet');
			self::enqueue_script('geodir-leaflet-geo');
			self::enqueue_script('leaflet-routing-machine');
			self::enqueue_script('geodir-o-overlappingmarker');
		}
		if($geodir_map_name!='none'){
			wp_add_inline_script( 'geodir-goMap', "window.gdSetMap = window.gdSetMap || '".GeoDir_Maps::active_map()."';", 'before' );
			wp_enqueue_script( 'geodir-goMap' );
		}

	}

	/**
	 * Localize a GeoDir script once.
	 * @access private
	 * @since  2.3.0 this needs less wp_script_is() calls due to https://core.trac.wordpress.org/ticket/28404 being added in WP 4.0.
	 * @param  string $handle
	 */
	private static function localize_script( $handle ) {
		if ( ! in_array( $handle, self::$wp_localize_scripts ) && wp_script_is( $handle ) && ( $data = self::get_script_data( $handle ) ) ) {
			$name                        = str_replace( '-', '_', $handle ) . '_params';
			self::$wp_localize_scripts[] = $handle;
			wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
		}
	}

	/**
	 * Return data for script handles.
     *
     * @since 2.0.0
     *
	 * @access private
	 * @param  string $handle
	 * @return array|bool
	 */
	private static function get_script_data( $handle ) {
		global $wp;

		switch ( $handle ) {
			case 'geodir' :
				/**
				 * Filter the `geodir_var` data array that outputs the  wp_localize_script() translations and variables.
				 *
				 * This is used by addons to add JS translatable variables.
				 *
				 * @since 1.4.4
				 * @param array $geodir_vars_data {
				 *    geodir var data used by addons to add JS translatable variables.
				 *
				 *    @type string $siteurl Site url.
				 *    @type string $geodir_plugin_url Geodirectory core plugin url.
				 *    @type string $geodir_ajax_url Geodirectory plugin ajax url.
				 *    @type int $geodir_gd_modal Disable GD modal that displays slideshow images in popup?.
				 *    @type int $is_rtl Checks if current locale is RTL.
				 *
				 * }
				 */
				return apply_filters('geodir_vars_data',
					array(
						'siteurl' => get_option('siteurl'),
						'plugin_url' => geodir_plugin_url(),
						'lazy_load' => geodir_get_option('geodir_lazy_load',1),
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'gd_modal' => (int)geodir_get_option('geodir_disable_gb_modal'),
						'is_rtl' => is_rtl() ? 1 : 0, // fix rtl issue
						'basic_nonce' => wp_create_nonce( 'geodir_basic_nonce'),// fix rtl issue
						'text_add_fav'      => apply_filters('geodir_add_favourite_text', ADD_FAVOURITE_TEXT),
						'text_fav'          => apply_filters('geodir_favourite_text', FAVOURITE_TEXT),
						'text_remove_fav'   => apply_filters('geodir_remove_favourite_text', REMOVE_FAVOURITE_TEXT),
						'text_unfav'        => apply_filters('geodir_unfavourite_text', UNFAVOURITE_TEXT),
						'icon_fav'          => apply_filters('geodir_favourite_icon', 'fas fa-heart'),
						'icon_unfav'        => apply_filters('geodir_unfavourite_icon', 'fas fa-heart'),
					) + geodir_params()
				);
			break;
			case 'geodir-select2' :
				return array(
					//'countries'                 => json_encode( array_merge( WC()->countries->get_allowed_country_states(), WC()->countries->get_shipping_country_states() ) ),
					'i18n_select_state_text'    => esc_attr__( 'Select an option&hellip;', 'geodirectory' ),
					'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'geodirectory' ),
					'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'geodirectory' ),
					'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'geodirectory' ),
					'i18n_input_too_short_n'    => _x( 'Please enter %item% or more characters', 'enhanced select', 'geodirectory' ),
					'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'geodirectory' ),
					'i18n_input_too_long_n'     => _x( 'Please delete %item% characters', 'enhanced select', 'geodirectory' ),
					'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'geodirectory' ),
					'i18n_selection_too_long_n' => _x( 'You can only select %item% items', 'enhanced select', 'geodirectory' ),
					'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'geodirectory' ),
					'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'geodirectory' ),
				);
			break;
			case 'geodir-plupload' :
				// place js config array for plupload
				$plupload_init = array(
					'runtimes' => 'html5,silverlight,html4',
					'browse_button' => 'plupload-browse-button', // will be adjusted per uploader
					'container' => 'plupload-upload-ui', // will be adjusted per uploader
					//'drop_element' => 'dropbox', // will be adjusted per uploader
					'file_data_name' => 'async-upload', // will be adjusted per uploader
					'multiple_queues' => true,
					'max_file_size' => geodir_max_upload_size(),
					'url' => admin_url('admin-ajax.php'),
					'flash_swf_url' => includes_url('js/plupload/plupload.flash.swf'),
					'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
					'filters' => array(array('title' => __('Allowed Files', 'geodirectory'), 'extensions' => '*')),
					'multipart' => true,
					'urlstream_upload' => true,
					'multi_selection' => false, // will be added per uploader
					// additional post data to send to our ajax hook
					'multipart_params' => array(
						'_ajax_nonce' => wp_create_nonce( "geodir_attachment_upload" ), // will be added per uploader
						'action' => 'geodir_post_attachment_upload', // the ajax action name
						'imgid' => 0, // will be added per uploader
						'post_id' => 0 // will be added per uploader
					)
				);
				$thumb_img_arr = array();

				if (isset($_REQUEST['pid']) && $_REQUEST['pid'] != '')
					$thumb_img_arr = geodir_get_images($_REQUEST['pid']);

				$totImg = '';
				$image_limit = '';
				if (!empty($thumb_img_arr)) {
					$totImg = count($thumb_img_arr);
				}
				$base_plupload_config = json_encode($plupload_init);

				return array('base_plupload_config' => $base_plupload_config,
				             'totalImg' => $totImg,
				             'image_limit' => $image_limit,
				             'upload_img_size' => geodir_max_upload_size()
				);
			break;

		}
		return false;
	}

	/**
	 * Localize scripts only when enqueued.
     *
     * @since 2.0.0
	 */
	public static function localize_printed_scripts() {
		foreach ( self::$scripts as $handle ) {
			self::localize_script( $handle );
		}
	}
}

GeoDir_Frontend_Scripts::init();
