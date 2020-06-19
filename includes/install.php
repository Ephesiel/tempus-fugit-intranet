<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manager of the installation, activation/deactivation
 *
 * @since 1.0.0
 */
class InstallManager {

    /**
     * Plugin_activation.
     * 
     * Call every methods needed at activation.
     * It adds options, create table and add every roles and capabilities.
     * 
     * @since 1.0.0
     * @access public
     * @static
     */
    public static function plugin_activation() {
        self::add_options();
        self::add_roles();
        self::create_table();
    }

    /**
     * Plugin_deactivation.
     * 
     * Call every methods needed at deactivation.
     * The only thing that it does is to remove the tfi_user role.
     * 
     * Datas from database including options are not deleted.
     * If the plugin is reactivate all datas will still be here.
     * (Note that the role won't need to be add to users again because wordpress keep it in user datas).
     * 
     * @since 1.0.0
     * @access public
     * @static
     */
    public static function plugin_deactivation() {
        self::remove_roles();
    }

    /**
     * Add_options.
     * 
     * Add every options required for the plugin, with their default value.
     * If the option is already set, it won't be changed.
     * 
     * @since 1.0.0
     * @access private
     * @static
     */
    private static function add_options() {
        require_once TFI_PATH . 'includes/options.php';

        $option_manager = new OptionsManager;
        $option_manager->update_options();
    }

    /**
     * Add_roles.
     * 
     * Create a new role and a new capability to allow certain users to access intranet.
     * You can add access_intranet capability to anyone here by adding a piece of code.
     * (Remember to remove it in the InstallManager::remove_roles method)
     * 
     * @since 1.0.0
     * @access private
     * @static
     * @global WP_Roles $wp_roles   Role manager of wordpress
     */
    private static function add_roles() {
        global $wp_roles;

        add_role( 'tfi_user', __( 'Intranet user' ), array( 'access_intranet' => true ) );
        $wp_roles->add_cap( 'administrator', 'access_intranet' );
    }

    /**
     * Create_tables
     * 
     * Create the table required for the plugin.
     * This method can be called multiple times (at activation for example)
     * Because the dbDelta function will check the existence of the table in the database.
     * 
     * @since 1.0.0
     * @access private
     * @static
     * @global wpdb     $wpdb           The database object to add the new table
     */
    private static function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE " . $wpdb->prefix . TFI_TABLE . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL UNIQUE,
            datas longtext NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Remove_roles.
     * 
     * The opposite of the InstallManager::add_roles method
     * Remove every user and capability add for the plugin (because it doesn't make sense to keep them when plugin is deactivated). 
     * 
     * @since 1.0.0
     * @access private
     * @static
     * @global WP_Roles $wp_roles   Role manager of wordpress
     */
    private static function remove_roles() {
        global $wp_roles;

        remove_role( 'tfi_user' );
        $wp_roles->remove_cap( 'administrator', 'access_intranet' );
    }
}

register_activation_hook( TFI__FILE__, array( 'TFI\\InstallManager', 'plugin_activation' ) );
register_deactivation_hook( TFI__FILE__, array( 'TFI\\InstallManager', 'plugin_deactivation' ) );