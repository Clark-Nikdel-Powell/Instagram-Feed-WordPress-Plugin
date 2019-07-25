<?php

/**
 * @link              https://github.com/Clark-Nikdel-Powell/instagram-feed-uri/
 * @since             1.0.0
 * @package           Instagram_Feed
 *
 * @wordpress-plugin
 * Plugin Name:       Instagram Feed
 * Plugin URI:        https://github.com/Clark-Nikdel-Powell/instagram-feed-uri/
 * Description:       Delivers HTML for displaying a feed of images from Instagram. Includes hooks for outputting additional markup.
 * Version:           1.0.0
 * Author:            Josh Nederveld
 * Author URI:        https://cnpagency.com/people/josh/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       instagram-feed
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'INSTAGRAM_FEED_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-instagram-feed-activator.php
 */
function activate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-instagram-feed-activator.php';
	Instagram_Feed_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-instagram-feed-deactivator.php
 */
function deactivate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-instagram-feed-deactivator.php';
	Instagram_Feed_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_plugin_name' );
register_deactivation_hook( __FILE__, 'deactivate_plugin_name' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-instagram-feed.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_plugin_name() {

	$plugin = new Instagram_Feed();
	$plugin->run();

}
run_plugin_name();
