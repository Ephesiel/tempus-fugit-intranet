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
     * Access_intranet_roles.
     * 
     * All roles which have an access to intranet.
     * If you're using this plugin and you want that a special role has access to intranet, please add it to the list.
     * By default only administrator and tfi_user roles have this access
     * 
     * @since 1.1.0
     * @access public
     * @static
     * 
     * @var array
     */
    public static $access_intranet_roles = array( 'administrator', 'tfi_user' );

    /**
     * Plugin_activation.
     * 
     * Call every methods needed at activation.
     * It adds options, create table and add every roles and capabilities.
     * Sub plugins are activated after options, because they need options updated
     * 
     * @since 1.0.0
     * @access public
     * @static
     */
    public static function plugin_activation() {
        self::add_roles();
        self::create_table();
        self::activate_plugins();
        self::add_options();
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
     * Sub plugins are deactivated first, they will be able to use tfi datas and functions.
     * 
     * @since 1.0.0
     * @access public
     * @static
     */
    public static function plugin_deactivation() {
        self::deactivate_plugins();
        self::remove_roles();
    }

    /**
     * Add_options.
     * 
     * Add every options required for the plugin, with their default value.
     * If the option is already set, it won't be changed.
     * Add roles before add options, because tfi_users verification needs roles sets
     * 
     * @since 1.0.0
     * @access private
     * @static
     */
    private static function add_options() {
        require_once TFI_PATH . 'includes/options.php';

        $option_manager = new OptionsManager( true );
        $option_manager->update_options();
    }

    /**
     * Add_roles.
     * 
     * Create a new role and a new capability to allow certain users to access intranet.
     * You can add access_intranet capability to anyone here by adding a piece of code.
     * (Remember to remove it in the InstallManager::remove_roles method).
     * 
     * @since 1.0.0
     * @access private
     * @static
     * @global WP_Roles $wp_roles   Role manager of wordpress
     */
    private static function add_roles() {
        global $wp_roles;

        add_role( 'tfi_user', __( 'Intranet user' ), array( 'access_intranet' => true ) );
        foreach ( self::$access_intranet_roles as $role_name ) {
            $wp_roles->add_cap( $role_name, 'access_intranet' );
        }
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

    /**
     * Activate_plugins
     * 
     * Activate all sub plugins which need to be activate.
     * 
     * @since 1.3.0
     * @access private
     * @static
     */
    private static function activate_plugins() {
        foreach ( tfi_get_option( 'tfi_plugins' ) as $plugin_name => $enable ) {
            if ( $enable ) {
				$plugin = PluginsManager::get_plugin( $plugin_name );
				
				if ( $plugin !== false ) {
					$plugin->activate( true );
                }
            }
        }
    }

    /**
     * Deactivate_plugins
     * 
     * Deactivate all sub plugins which need to be deactivate.
     * 
     * @since 1.3.0
     * @access private
     * @static
     */
    private static function deactivate_plugins() {
        foreach ( tfi_get_option( 'tfi_plugins' ) as $plugin_name => $enable ) {
            if ( $enable ) {
				$plugin = PluginsManager::get_plugin( $plugin_name );
				
				if ( $plugin !== false ) {
					$plugin->deactivate( true );
				}
            }
        }
    }
}

register_activation_hook( TFI__FILE__, array( 'TFI\\InstallManager', 'plugin_activation' ) );
register_deactivation_hook( TFI__FILE__, array( 'TFI\\InstallManager', 'plugin_deactivation' ) );