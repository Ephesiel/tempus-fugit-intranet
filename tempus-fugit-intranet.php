<?php
/**
 * Plugin Name: Tempus Fugit Intranet
 * Plugin URI: http://www.tempusfugit-thegame.com
 * Description: Intranet to allow every students to add their own datas at home whitout passing by the wordpress admin page.
 * Version: 1.2.4
 * Author: Huftier Benoît
 * Author URI: http://www.tempusfugit-thegame.com
 * 
 * Text Domain: tempus-fugit-intranet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'TFI__FILE__', __FILE__ );

require 'constants.php';

add_action( 'plugins_loaded', 'tfi_load_plugin_textdomain' );

if ( ! version_compare( PHP_VERSION, '7.3', '>=' ) ) {
	add_action( 'admin_notices', 'tfi_fail_php_version' );
}
else {

	/**
	 * File with some utilities functions
	 */
	require TFI_PATH . 'utilities/functions.php';
	
	/**
	 * Plugin which allows to add a new page template
	 */
	require TFI_PATH . 'plugins/page-template.php';
	
	/**
	 * The main file of the tfi plugin
	 */
	require TFI_PATH . 'includes/plugin.php';
	
	/**
	 * The installation class to manage activation and deactivation of the plugin
	 */
	require TFI_PATH . 'includes/install.php';
}

/**
 * Tfi_load_plugin_textdomain.
 *
 * Load gettext translate for TFI text domain.
 *
 * @since 1.0.0
 */
function tfi_load_plugin_textdomain() {
	load_plugin_textdomain( 'tempus-fugit-intranet' );
}

/**
 * Tfi_fail_php_version.
 *
 * Warning when the site doesn't have the minimum required PHP version.
 *
 * @since 1.0.0
 */
function tfi_fail_php_version() {
	?>
	<div class="notice notice-error"><p><?php esc_html_e( 'Tempus Fugit Intranet requires PHP version 7.3, plugin is currently NOT RUNNING.' ); ?></p></div>
	<?php
}