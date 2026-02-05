<?php
/**
 * Plugin Name:       Nested Blog Posts
 * Plugin URI:        https://github.com/yaverabbas/nested-blog-posts
 * Description:       Enables parent/child hierarchy for standard Posts and generates hierarchical permalinks like /parent/child/ (supports unlimited depth).
 * Version:           1.0.0
 * Requires at least: 6.3
 * Tested up to:      6.9.1
 * Requires PHP:      7.4
 * Author:            Yaver Abbas
 * Author URI:        https://github.com/yaverabbas
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nested-blog-posts
 * Domain Path:       /languages
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NBP_VERSION', '1.0.0' );
define( 'NBP_FILE', __FILE__ );
define( 'NBP_DIR', plugin_dir_path( __FILE__ ) );
define( 'NBP_URL', plugin_dir_url( __FILE__ ) );

require_once NBP_DIR . 'includes/class-nbp-plugin.php';

add_action( 'plugins_loaded', array( 'NBP_Plugin', 'init' ) );

register_activation_hook( __FILE__, array( 'NBP_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'NBP_Plugin', 'deactivate' ) );
