<?php
/**
 * Plugin Name: TFI Parallax
 * Plugin URI: http://www.tempusfugit-thegame.com
 * Description: Add parallax to options and a page with a shortcode to display parallax
 * Version: 1.0.0
 * Author: Huftier Benoît
 * Author URI: http://www.tempusfugit-thegame.com
 */

add_action( 'tfi_plugins_activate_parallax', 'parallax_activate' );
add_action( 'tfi_plugins_deactivate_parallax', 'parallax_deactivate' );

function parallax_activate() {
    error_log( 'parallax activate' );
}

function parallax_deactivate() {
    error_log( 'parallax deactivate' );
}