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

        try {
            foreach( tfi_options_properties() as $option_name => $option_properties ) {
                $option = get_option( $option_name );

                if ( $option === false && update_option( $option_name, false ) === false ) {
                    if ( isset( $option_properties['mandatory'] ) ) {
                        add_option( $option_name, self::return_default_value( $option_properties ) );
                    }
                    else {
                        add_option( $option_name, $option_properties['default'] );
                    }
                }
                else {
                    $result = self::verify_option( $option, $option_properties );
                    if ( $result !== null ) {
                        update_option( $option_name, $result );
                    }
                }
            }
        } catch ( Exception $e ) {
            add_action( 'admin_notices', function() use( $e ) {
                echo '<div class="notice notice-error">';
                echo    '<p>';
                echo        '<b>' . esc_html__( 'An option failed its verification and give the following error:' ) . '</b>';
                echo        esc_html( $e->getMessage() );
                echo    '</p>';
                echo '</div>';
            } );
        }
    }

    /**
     * Verify_option.
     * 
     * This method verify an option field.
     * It will do it recursively if an option is an array
     * 
     * @since 1.0.1
     * @access private
     * @static
     * 
     * @param mixed     $option_value       the actual value of the option which need verification
     * @param array     $option_properties  the option array
     * @param bool      $mandatory          if the option is mandatory, if not, the value will be deleted when there is an error, not the default value
     * 
     * @return mixed        the value to modify for the option
     * @return null         the option is okay and don't need change
     * @return 'FALSE'      the option is not mandatory and is value is wrong
     * @throws Exception    the option is not okay and it is impossible to modify the option
     */
    private static function verify_option( $option_value, $option_properties, $mandatory = true ) {
        /**
         * When an option has a mandatory key, it's override everything else
         */
        if ( isset( $option_properties['mandatory'] ) ) {
            if ( $option_value === $option_properties['mandatory'] ) {
                return null;
            }
            return $option_properties['mandatory'];
        }

        /**
         * Type verification
         */
        if ( ! isset( $option_properties['type'] ) ) {
            throw new Exception( __( 'An option don\'t have a type key' ) );
        }
        
        $type = $option_properties['type'];

        if ( ! in_array( $type, array( 'bool', 'int', 'string', 'array' ) ) ) {
            throw new Exception( __( 'An option don\'t have a good type, it can only have one of this values\n : \'bool\', \'int\', \'string\' and \'array\'' ) );
        }

        if ( ! call_user_func( 'is_' . $type, $option_value ) ) {
            return $mandatory ? self::return_default_value( $option_properties ) : 'FALSE';
        }

        /**
         * When type = bool, there is no more verification to do
         */
        if ( $type == 'bool' ) {
            return null;
        }

        /**
         * We can have a value array.
         * The option value needs to be one of this values
         */
        if ( isset( $option_properties['values'] ) && is_array( $option_properties['values'] ) ) {
            foreach( $option_properties['values'] as $value ) {
                if ( $value === $option_value ) {
                    return null;
                }
            }
            return $mandatory ? self::return_default_value( $option_properties ) : 'FALSE';
        }

        /**
         * When type = int, we can have a max and mon value
         */
        if ( $type ==  'int' ) {
            if ( ( isset( $option_properties['max'] ) && $option_properties['max'] > $option_value ) ||
                 ( isset( $option_properties['min'] ) && $option_properties['min'] < $option_value ) ) {
                    return $mandatory ? self::return_default_value( $option_properties ) : 'FALSE';
            }
            return null;
        }

        /**
         * When type = string, we can have a special length value
         */
        if ( $type == 'string' ) {
            if ( ( isset( $option_properties['length'] ) && $option_properties['length'] != strlen( $option_value ) ) ||
                 ( isset( $option_properties['min-length'] ) && $option_properties['min-length'] < strlen( $option_value ) ) ||
                 ( isset( $option_properties['max-length'] ) && $option_properties['max-length'] > strlen( $option_value ) ) ) {
                    return $mandatory ? self::return_default_value( $option_properties ) : 'FALSE';
            }
            return null;
        }

        /**
         * When type is array we need to verify the length + all the keys and custom_keys
         */
        if ( $type == 'array' ) {
            $keys_length = 0;
            $copy = $option_value;

            /**
             * Verification of all mandatory keys
             */
            if ( isset( $option_properties['keys'] ) && is_array( $option_properties['keys'] ) ) {
                $keys = $option_properties['keys'];
                $keys_length = count( $keys );

                /**
                 * If the key is well set, we verify its value recursively
                 * If the key don't exist, we take the default value
                 */
                foreach ( $keys as $option_key => $key_properties ) {
                    if ( isset( $option_value[$option_key] ) ) {
                        $result = self::verify_option( $option_value[$option_key], $key_properties );

                        if ( $result !== null ) {
                            $option_value[$option_key] = $result;
                        }
                    } else {
                        $option_value[$option_key] = self::return_default_value( $key_properties );
                    }

                    /**
                     * Unset the key from the copy
                     * Allow to keep all custom keys
                     */
                    unset( $copy[$option_key] );
                }
            }

            if ( isset( $option_properties['custom_keys'] ) ) {
                /**
                 * Verify each custom keys
                 * A custom keys isn't mandatory
                 */
                foreach( $copy as $option_key => $sub_option_value ) {
                    $result = self::verify_option( $sub_option_value, $option_properties['custom_keys'], false );

                    /**
                     * If the result is 'FALSE' an error occured
                     */
                    if ( $result === 'FALSE' ) {
                        unset( $option_value[$option_key] );
                    }
                    else if ( $result !== null ) {
                        $option_value[$option_key] = $result;
                    }
                }
            }
            else if ( ! empty( $copy ) ) {
                /**
                 * Custom keys are not allowed, so we delete every unwanted keys
                 */
                foreach ( $copy as $key => $value ) {
                    unset( $option_value[$key] );
                }
            }

            /**
             * The keys length is add to lengths properties because mandatory keys don't count like a row
             */
            if ( ( isset( $option_properties['length'] ) && $option_properties['length'] + $keys_length != count( $option_value ) ) ||
                 ( isset( $option_properties['min-length'] ) && $option_properties['min-length'] + $keys_length < count( $option_value ) ) ||
                 ( isset( $option_properties['max-length'] ) && $option_properties['max-length'] + $keys_length > count( $option_value ) ) ) {
                    return $mandatory ? self::return_default_value( $option_properties ) : 'FALSE';
            }
            return null;
        }
    }

    /**
     * Return_default_value.
     * 
     * This method is for factorization of the InstallManager::verify_option method.
     * Return the default value or throw an error if there is no default key 
     * 
     * @since 1.0.1
     * @access private
     * @static
     * 
     * @param array     $option_properties  the option array
     * 
     * @return mixed        the value to modify for the option
     * @throws Exception    the option is not okay and it is impossible to modify the option
     */
    private static function return_default_value( $option_properties ) {
        if ( isset( $option_properties['default'] ) && isset( $option_properties['type'] ) ) {
            $default_value = $option_properties['default'];
            $type = $option_properties['type'];
            if ( call_user_func( 'is_' . $type, $default_value ) ) {
                return $option_properties['default'];
            }
            throw new Exception( sprintf( __( 'The default value of this option should be a value of type %s' ), $type ) );
        }
        throw new Exception( __( 'This option don\'t have a default value and/or a type' ) );
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

        /**
         * Insert a first value to the database.
         * Because we create a default user, we need to insert it into the database.
         * The IGNORE is because the plugin can be deactivate and reactivate, if the default users changed some things, it will keep them
         */
        $options = tfi_get_default_options();
        $user_id = array_key_first( $options['tfi_users'] );
        $user_datas = array();
        foreach ( $options['tfi_fields'] as $field_slug => $field_datas ) {
            $user_datas[$field_slug] = $field_datas['default'];
        }
        $user_datas = maybe_serialize( $user_datas );
        $wpdb->query( "INSERT IGNORE INTO " . $wpdb->prefix . TFI_TABLE . " (user_id, datas) VALUES ( $user_id, '$user_datas')" );
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