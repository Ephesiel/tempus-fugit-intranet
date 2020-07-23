<?php
/**
 * Plugin Name: TFI Echo
 * Plugin URI: http://www.tempusfugit-thegame.com
 * Description: Create all echo functionnality to be add in the intranet and then send on the server
 * Version: 1.0.0
 * Author: Huftier Benoît
 * Author URI: http://www.tempusfugit-thegame.com
 */

add_action( 'tfi_plugins_activate_echo', 'echo_activate' );
add_action( 'tfi_plugins_deactivate_echo', 'echo_deactivate' );

function echo_activate() {
    error_log( 'echo activate' );
}

function echo_deactivate() {
    error_log( 'echo deactivate' );
}