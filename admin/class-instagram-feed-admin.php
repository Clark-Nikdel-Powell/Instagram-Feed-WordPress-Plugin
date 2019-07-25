<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/Clark-Nikdel-Powell/instagram-feed
 * @since      1.0.0
 *
 * @package    Instagram_Feed
 * @subpackage Instagram_Feed/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Instagram_Feed
 * @subpackage Instagram_Feed/admin
 * @author     Your Name <email@example.com>
 */
class Instagram_Feed_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	private $access_token_fieldname;
	private $hashtag_fieldname;
	private $images_check_cache_key;
	private $images_cache_key;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 * @param string $access_token_field_name Fieldname for the access token.
	 * @param string $hashtag_field_name Fieldname for the hashtag.
	 * @param string $images_check_cache_key The cache key for whether it's time to check for new images.
	 * @param string $images_cache_key The cache key of image data from Instagram.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version, $access_token_field_name, $hashtag_field_name, $images_check_cache_key, $images_cache_key ) {

		$this->plugin_name            = $plugin_name;
		$this->version                = $version;
		$this->access_token_fieldname = $access_token_field_name;
		$this->hashtag_fieldname      = $hashtag_field_name;
		$this->images_check_cache_key = $images_check_cache_key;
		$this->images_cache_key       = $images_cache_key;
	}

	public function register_settings() {
		register_setting( 'instagram-feed', $this->access_token_fieldname );
		register_setting( 'instagram-feed', $this->hashtag_fieldname );
	}

	public function add_settings_field() {
		add_settings_field( $this->access_token_fieldname, 'Access Token', array(
			&$this,
			'access_token_settings_field'
		), 'instagram-feed', 'default' );
		add_settings_field( $this->hashtag_fieldname, 'Hashtag', array(
			&$this,
			'hashtag_settings_field'
		), 'instagram-feed', 'default' );
	}

	public function access_token_settings_field() {

		ob_start();
		?>
        <input name="<?php echo esc_attr( $this->access_token_fieldname ); ?>" type="text" id="<?php echo esc_attr( $this->access_token_fieldname ); ?>" value="<?php echo get_option( $this->access_token_fieldname ); ?>" class="regular-text"/>
		<?php
		$buffer = ob_get_clean();
		echo $buffer;
	}

	public function hashtag_settings_field() {

		ob_start();
		?>
        <input name="<?php echo esc_attr( $this->hashtag_fieldname ); ?>" type="text" id="<?php echo esc_attr( $this->hashtag_fieldname ); ?>" value="<?php echo get_option( $this->hashtag_fieldname ); ?>" class="regular-text"/>
		<?php
		$buffer = ob_get_clean();
		echo $buffer;
	}

	public function add_submenu_page() {


		add_submenu_page( 'options-general.php', 'Instagram Feed Settings', 'Instagram Feed', 'activate_plugins', 'instagram-feed', array(
			&$this,
			'display_submenu_page'
		) );
	}

	public function clear_cache() {
		delete_transient( $this->images_check_cache_key );
		delete_transient( $this->images_cache_key );
		exit( wp_redirect( admin_url( 'options-general.php?page=instagram-feed&ig-feed-cache-cleared=true' ) ) );
	}

	public function add_removable_arg( $args ) {
		array_push( $args, 'ig-feed-cache-cleared' );

		return $args;
	}

	public function cache_cleared_notice() {
		if ( ! isset( $_GET['ig-feed-cache-cleared'] ) ) {
			return;
		}

		$class   = 'notice notice-info is-dismissible';
		$message = __( 'Instagram feed cache cleared.', 'instagram-feed' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public function display_submenu_page() {

		$title = __( 'Instagram Feed Settings' );

		ob_start();
		?>
        <div class="wrap">
            <h1><?php echo esc_html( $title ); ?></h1>
            <form method="post" action="options.php" novalidate="novalidate">
				<?php settings_fields( 'instagram-feed' ); ?>
                <table class="form-table">
					<?php do_settings_fields( 'instagram-feed', 'default' ); ?>
                </table>
				<?php do_settings_sections( 'instagram-feed' ); ?>
				<?php submit_button(); ?>
            </form>
            <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
                <input type="hidden" name="action" value="instagram_feed_clear_cache">
				<?php submit_button( 'Clear cache', 'delete', 'clear_cache' ); ?>
            </form>
        </div>
		<?php
		$buffer = ob_get_clean();
		echo $buffer;
	}
}
