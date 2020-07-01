<?php
namespace TFI;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manager of the uninstallation
 * This file is automatically include when the plugin is uninstall
 *
 * @since 1.0.0
 */
class UninstallManager {

    /**
     * UninstallManager constructor.
     * 
     * It calls every methods needed on uninstallation.
     * Delete all databases tables and options
     * Delete all files used by the plugin
     * 
     * /!\ On unistallation, every datas stored and used by the plugin will be DELETED  /!\
     * /!\ Be carefull before unistalling                                               /!\
     * 
     * @since 1.0.0
     * @access public 
     */
    public function __construct() {
        $this->delete_options();
        $this->drop_table();
        $this->delete_upload_dir();
    }

    /**
     * Delete_options.
     * 
     * Just delete all options.
     * 
     * @since 1.0.0
     * @access private
     */
    private function delete_options() {
        require_once TFI_PATH . 'includes/options.php';

        $option_manager = new OptionsManager;
        $option_manager->delete_options();
    }
    
    /**
     * Drop_table.
     * 
     * Drop the table with all user datas.
     * 
     * @since 1.0.0
     * @access private
     * @global wpdb     $wpdb           The database object to drop the table
     */
    private function drop_table() {
        global $wpdb;

        $wpdb->query(
            "DROP TABLE IF EXISTS " . $wpdb->prefix . TFI_TABLE
        );
    }
    
    /**
     * Delete_upload_dir.
     * 
     * Delete the upload folder and all files inside this one.
     * 
     * @since 1.0.0
     * @access private
     */
    private function delete_upload_dir() {
        if ( defined( 'TFI_UPLOAD_FOLDER_DIR' ) ) {
            if ( file_exists( TFI_UPLOAD_FOLDER_DIR ) ) {
                tfi_delete_files( TFI_UPLOAD_FOLDER_DIR );
            }
        }
    }
}

require 'constants.php';

/**
 * We used some functions and tempus-fugit-intranet.php file isn't load when uninstall
 */
require TFI_PATH . 'utilities/functions.php';

new UninstallManager();