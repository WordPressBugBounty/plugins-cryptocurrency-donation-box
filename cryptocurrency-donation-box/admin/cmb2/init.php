<?php

if (!defined('ABSPATH')) {
    exit;
}

if ( ! class_exists( 'CMB2_Bootstrap_260_Develop', false ) ) {

	/**
	 * Handles checking for and loading the newest version of CMB2
	 *
	 * @since  2.0.0
	 *
	 * @category  WordPress_Plugin
	 * @package   CMB2
	 * @author    CMB2 team
	 * @license   GPL-2.0+
	 * @link      https://cmb2.io
	 */
	class CMB2_Bootstrap_260_Develop {

		/**
		 * Current version number
		 *
		 * @var   string
		 * @since 1.0.0
		 */
		const VERSION = '2.9.0';

		/**
		 * Current version hook priority.
		 * Will decrement with each release
		 *
		 * @var   int
		 * @since 2.0.0
		 */
		const PRIORITY = 9959;

		/**
		 * Single instance of the CMB2_Bootstrap_260_Develop object
		 *
		 * @var CMB2_Bootstrap_260_Develop
		 */
		public static $single_instance = null;

		/**
		 * Creates/returns the single instance CMB2_Bootstrap_260_Develop object
		 *
		 * @since  2.0.0
		 * @return CMB2_Bootstrap_260_Develop Single instance object
		 */
		public static function initiate() {
			if ( null === self::$single_instance ) {
				self::$single_instance = new self();
			}
			return self::$single_instance;
		}

		/**
		 * Starts the version checking process.
		 * Creates CMB2_LOADED definition for early detection by other scripts
		 *
		 * Hooks CMB2 inclusion to the init hook on a high priority which decrements
		 * (increasing the priority) with each version release.
		 *
		 * @since 2.0.0
		 */
		private function __construct() {
			/**
			 * A constant you can use to check if CMB2 is loaded
			 * for your plugins/themes with CMB2 dependency
			 */
			if ( ! defined( 'CMB2_LOADED' ) ) {
				define( 'CMB2_LOADED', self::PRIORITY );
			}

			if ( ! function_exists( 'add_action' ) ) {
				// We are running outside of the context of WordPress.
				return;
			}
			

			add_action( 'init', array( $this, 'include_cmb' ), self::PRIORITY );
		}

		/**
		 * A final check if CMB2 exists before kicking off our CMB2 loading.
		 * CMB2_VERSION and CMB2_DIR constants are set at this point.
		 *
		 * @since  2.0.0
		 */
		public function include_cmb() {
			if ( class_exists( 'CMB2', false ) ) {
				return;
			}

			if ( ! defined( 'CMB2_VERSION' ) ) {
				define( 'CMB2_VERSION', self::VERSION );
			}

			if ( ! defined( 'CMB2_DIR' ) ) {
				define( 'CMB2_DIR', trailingslashit( dirname( __FILE__ ) ) );
			}
		

			// Include helper functions.
			require_once CMB2_DIR . 'includes/CMB2_Base.php';
			require_once CMB2_DIR . 'includes/CMB2.php';
			require_once CMB2_DIR . 'includes/helper-functions.php';
			//if(cmc_get_post_type_page()=="cmc" || cmc_get_post_type_page()=="cmc-description" ){
			//require_once CMB2_DIR . 'cmb-field-select2.php';
			//}
				

			// Now kick off the class autoloader.
			spl_autoload_register( 'cmb2_autoload_classes' );

			// Kick the whole thing off.
			require_once( cmb2_dir( 'bootstrap.php' ) );
			cmb2_bootstrap();
		}
	

	}

	// Make it so...
	CMB2_Bootstrap_260_Develop::initiate();

}// End if().
