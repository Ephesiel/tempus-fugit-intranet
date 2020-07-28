<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define all constants to be used both in the plugin and in uninstall.php
 */
define( 'TFI_URL', plugins_url( '/', __FILE__ ) );
define( 'TFI_PATH', plugin_dir_path( __FILE__ ) );

define( 'TFI_TEMP_PATH', TFI_PATH . 'tmp/');
if ( ! file_exists( TFI_TEMP_PATH ) ) {
	wp_mkdir_p( TFI_TEMP_PATH );
}

define( 'TFI_TEMPLATE_PAGE', 'tfi-user-page.php' );
define( 'TFI_TABLE', 'tfi_datas' );

$upload_dir = wp_upload_dir();

if ( $upload_dir['error'] === false ) {
	define( 'TFI_UPLOAD_FOLDER_DIR', $upload_dir['basedir'] . '/tempus_fugit_files' );
	define( 'TFI_UPLOAD_FOLDER_URL', $upload_dir['baseurl'] . '/tempus_fugit_files' );
}

define( 'TFI_PLUGINS_FOLDER_PATH', TFI_PATH . 'plugins/tfi/' );

define( 'TFI_VERSION', '1.3.0' );