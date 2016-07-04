<?php

// No direct access allowed.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tiny class just for internationalization purposes.
 */
abstract class WPIBreadcrumbCore extends WPIMCore {

	const LANG = 'wpinventory_breadcrumb';

	/**
	 * Abstraction of the WP language function.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function __( $text ) {
		return __( $text, self::LANG );
	}

	/**
	 * Abstraction of the WP language function (echo)
	 *
	 * @param string $text
	 */
	public static function _e( $text ) {
		echo self::__( $text );
	}
}

/**
 * Class WPIMBreadCrumbIntegration
 */
Class WPIMBreadCrumbIntegration extends WPIBreadcrumbCore {

	private static $config_key_name_field = 'breadcrumb_name_field';
	private static $name_field = 'inventory_name';

	private static $is_single = NULL;
	private static $item_url = NULL;
	private static $item_name = NULL;

	/**
	 * Set everything up.
	 */
	public static function start() {
		add_filter( 'wpim_default_config', array( __CLASS__, 'wpim_default_config' ) );
		add_action( 'wpim_edit_settings_general', array( __CLASS__, 'wpim_edit_settings' ) );

		add_action( 'init', array( __CLASS__, 'init' ) );
		// Only set up the front-end hooks if we're not in the admin dashboard
		if ( ! is_admin() ) {
			add_action( 'init', array( __CLASS__, 'non_admin_init' ) );
		}
	}

	/**
	 * WordPress init action.
	 * Sets up internationalization
	 */
	public static function init() {
		// Enable internationalization
		if ( ! load_plugin_textdomain( 'wpinventory_breadcrumb', FALSE, '/wp-content/languages/' ) ) {
			load_plugin_textdomain( 'wpinventory_breadcrumb', FALSE, basename( dirname( __FILE__ ) ) . "/languages/" );
		}
	}

	public static function non_admin_init() {

		self::load_settings();

		add_action( 'bcn_before_fill', array( __CLASS__, 'navxt' ) );
		add_filter( 'wpseo_breadcrumb_links', array( __CLASS__, 'yoast' ) );
	}

	/**
	 * Adds the breadcrumb name field to the default config
	 *
	 * @param array $default
	 *
	 * @return array
	 */
	public static function wpim_default_config( $default ) {
		$default[ self::$config_key_name_field ] = 'inventory_name';

		return $default;
	}

	/**
	 * Displays the WPIM Admin Settings (selecting the breadcrumb name field)
	 */
	public static function wpim_edit_settings() {
		self::load_settings();

		$fields          = WPIMAdmin::getDisplay( 'detail' );
		$dropdown_fields = array();
		foreach ( $fields AS $field ) {
			$dropdown_fields[ $field ] = WPIMLabel::get_label( $field );
		}

		echo '<tr class="subtab"><th colspan="2"><h4>' . self::__('Breadcrumb Integration') . '</h4></th></tr>';
		echo '<tr><th>' . self::__( 'Breadcrumb Name Field' ) . '</th>';
		echo '<td>';
		echo WPIMAdmin::dropdown_array( self::$config_key_name_field, self::$name_field, $dropdown_fields );
		if ( ! class_exists( 'WPIMAIM' ) ) {
			echo '<p class="description">' . self::__( 'Is this list missing fields? These are only the fields you have set to show in the Display settings under "Detail Display"' ) . '</p>';
		} else {
			echo '<p class="description">' . self::__( 'Note that this list does not support selecting the "Display Field" for all Advanced Inventory Types that you have set up. The system will automatically pick up the field that is the default "Name"' ) . '</p>';
		}

		echo '<p><strong>';
		if ( class_exists( 'bcn_breadcrumb' ) ) {
			self::_e( 'For help displaying breadcrumbs, please see NavXT Breadcrumb support.' );
		} else if ( function_exists( 'yoast_breadcrumb' ) ) {
			self::_e( 'For help displaying breadcrumbs, please see Yoast SEO Breadcrumb support' );
		} else {
			self::_e( 'Note - The WP Inventory Breadcrumbs Inegration plugin is designed to work with either NavXT or Yoast SEO breadcrumb plugins, which are not currently installed.' );
		}
		echo '</td>';
	}

	/**
	 * Breadcrumb NavXT integration.
	 * Adds the single item title to the end of the breadcrumbs.
	 *
	 * @param object $navxt
	 */
	public static function navxt( $navxt ) {
		if ( ! self::is_single() ) {
			return;
		}

		$navxt->add( new bcn_breadcrumb( self::$item_name, NULL, array( 'inventory_item' ), self::$item_url ) );
	}

	/**
	 * Yoast / WordPress SEO integration.
	 * Adds the single item title to the end of the breadcrumbs.
	 *
	 * @param array $crumbs
	 *
	 * @return array
	 */
	public static function yoast( $crumbs ) {
		if ( self::is_single() ) {
			$crumbs[] = array(
				'text'       => self::$item_name,
				'url'        => self::$item_url,
				'allow_html' => TRUE
			);
		}

		return $crumbs;
	}

	/**
	 * Detect if we are on a single inventory item display.  If so, sets up the relevant variables.
	 *
	 * @return bool
	 */
	private static function is_single() {
		if ( self::$is_single === NULL ) {
			self::$is_single = wpinventory_is_single();
		}

		if ( self::$is_single && ! self::$item_url ) {
			wpinventory_get_items();
			wpinventory_the_item();
			self::$item_url  = wpinventory_get_permalink();
			self::$item_name = strip_tags( wpinventory_get_field( self::$name_field ) );
			if ( ! self::$item_url || ! self::$item_name ) {
				self::$is_single = FALSE;
				echo '<!-- WPIMBreadCrumbIntegration failed to load item name / url, even though is single! -->' . PHP_EOL;
			}
		}


		return self::$is_single;
	}

	/**
	 * Load the configuration settings (field to display in breadcrumb trail)
	 */
	private static function load_settings() {
		self::$name_field = wpinventory_get_config( self::$config_key_name_field );
		if ( ! self::$name_field ) {
			self::$name_field = 'inventory_name';
		}
	}
}

WPIMBreadCrumbIntegration::start();
