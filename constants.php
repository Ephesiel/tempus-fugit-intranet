<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define all constants to be used both in the plugin and in uninstall.php
 */
define( 'TFI_URL', plugins_url( '/', __FILE__ ) );
define( 'TFI_PATH', plugin_dir_path( __FILE__ ) );

define( 'TFI_TEMPLATE_PAGE', 'tfi-user-page.php' );
define( 'TFI_TABLE', 'tfi_datas' );
define( 'TFI_UPLOAD_FOLDER', 'tempus_fugit_files' );

define( 'TFI_VERSION', '1.0' );