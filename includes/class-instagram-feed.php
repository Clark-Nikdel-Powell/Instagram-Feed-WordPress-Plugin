<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/Clark-Nikdel-Powell/instagram-feed
 * @since      1.0.0
 *
 * @package    Instagram_Feed
 * @subpackage Instagram_Feed/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Instagram_Feed
 * @subpackage Instagram_Feed/includes
 * @author     Your Name <email@example.com>
 */
class Instagram_Feed {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Instagram_Feed_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	protected $access_token_fieldname = 'instagram_feed_access_token';
	protected $hashtag_fieldname = 'instagram_feed_hashtag';
	protected $images_check_cache_key = 'check_for_new_instagram_images';
	protected $images_cache_key = 'instagram_images';

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'INSTAGRAM_FEED_VERSION' ) ) {
			$this->version = INSTAGRAM_FEED_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'instagram-feed';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Instagram_Feed_Loader. Orchestrates the hooks of the plugin.
	 * - Instagram_Feed_i18n. Defines internationalization functionality.
	 * - Instagram_Feed_Admin. Defines all hooks for the admin area.
	 * - Instagram_Feed_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-instagram-feed-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-instagram-feed-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-instagram-feed-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-instagram-feed-public.php';

		$this->loader = new Instagram_Feed_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Instagram_Feed_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Instagram_Feed_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Instagram_Feed_Admin( $this->get_plugin_name(), $this->get_version(), $this->get_access_token_field_name(), $this->get_hashtag_field_name(), $this->get_images_check_cache_key(), $this->get_images_cache_key() );

		$this->loader->add_action( 'admin_post_instagram_feed_clear_cache', $plugin_admin, 'clear_cache' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'cache_cleared_notice' );
		$this->loader->add_action( 'removable_query_args', $plugin_admin, 'add_removable_arg' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'add_settings_field' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_submenu_page' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Instagram_Feed_Public( $this->get_plugin_name(), $this->get_version(), $this->get_access_token_field_name(), $this->get_hashtag_field_name(), $this->get_images_check_cache_key(), $this->get_images_cache_key() );

		$this->loader->add_action( 'init', $plugin_public, 'add_shortcode' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Instagram_Feed_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}

	public function get_access_token_field_name() {
		return $this->access_token_fieldname;
	}

	public function get_hashtag_field_name() {
		return $this->hashtag_fieldname;
	}

	/**
	 * Retrieve the cache key for whether it's time to check for new images or not.
	 *
	 * @return    string
	 * @since    1.0.0
	 */
	public function get_images_check_cache_key() {
		return $this->images_check_cache_key;
	}

	/**
	 * Retrieve the cache of image data from Instagram
	 *
	 * @return    string
	 * @since    1.0.0
	 */
	public function get_images_cache_key() {
		return $this->images_cache_key;
	}
}
