<?php
/**
 * Plugin Name
 *
 * @package           Users Role Shortcode
 * @author            mahowell
 * @copyright         2019
 * @license           GPL-2.0-or-later
 *
 * Plugin Name:       Users Role Shortcode
 * Plugin URI:        https://developer.wordpress.org/plugins/
 * Description:       A shortcode that displays a sortable table of users filtered by role. This is a sample project.
 * Version:           1.0.0
 * Author:            mahowell
 * Author URI:        https://developer.wordpress.org/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       users-role-shortcode
 * Domain Path:       /languages
 */

// If this file is called directly, exit.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'USERS_ROLE_SHORTCODE__VERSION', '1.0.0' );
define( 'USERS_ROLE_SHORTCODE__PLUGIN_FILE', __FILE__ );
define( 'USERS_ROLE_SHORTCODE__PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'USERS_ROLE_SHORTCODE__PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Require class file.
require_once USERS_ROLE_SHORTCODE__PLUGIN_PATH . 'includes/class-users-role-shortcode.php';

// // Register plugin activations hooks.
register_activation_hook( USERS_ROLE_SHORTCODE__PLUGIN_FILE, array( 'UsersRoleShortcode', 'register_activation_hook' ) );
register_deactivation_hook( USERS_ROLE_SHORTCODE__PLUGIN_FILE, array( 'UsersRoleShortcode', 'register_deactivation_hook' ) );

// Begins execution of the plugin.
UsersRoleShortcode::get_instance();
