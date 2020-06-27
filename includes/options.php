<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Everything concerning options are here 
 *
 * @since 1.1.0
 */
class OptionsManager {

    /**
     * Default_options.
     * 
     * All options and their default values
     * 
     * @since 1.1.0
     * @since 1.3.0     Add tfi_file_folders option
     * 
     * @static
     * @access private
     * 
     * @var array
     */
    private static $default_options = array(
        'tfi_shortcut' => array(
            'ctrl_key_used' => true,
            'alt_key_used' => true,
            'shift_key_used' => false,
            'key' => 76
        ),
        'tfi_user_page_id' => -1,
        'tfi_user_types' => array(
            'default_type' => 'Default type'
        ),
        'tfi_file_folders' => array(
            'user_folder' => array(
                'display_name' => 'User folder',
                'parent' => ''
            )
        ),
        'tfi_field_types' => array(
            'image' => array(
                'display_name' => 'Image',
                'special_params' => array(
                    'height',
                    'width'
                )
            ),
            'link' => array(
                'display_name' => 'Link',
                'special_params' => array()
            ),
            'text' => array(
                'display_name' => 'Text',
                'special_params' => array()
            )
        ),
        'tfi_fields' => array(
            'facebook' => array(
                'real_name' => 'Facebook',
                'type' => 'link',
                'default' => '',
                'users' => array( 'default_type' ),
                'special_params' => array(
                    'mandatory_domains' => array(
                        'facebook'
                    )
                )
            ),
            'twitter' => array(
                'real_name' => 'Twitter',
                'type' => 'link',
                'default' => '',
                'users' => array( 'default_type' ),
                'special_params' => array(
                    'mandatory_domains' => array(
                        'twitter'
                    )
                )
            )
        ),
        'tfi_users' => array()
    );

    /**
     * On_installation.
     * 
     * Are we on the installation step.
     * 
     * @since 1.1.0
     * @access private
     * 
     * @var bool
     */
    private $on_installation;

    /**
     * OptionsManager constructor.
     * 
     * Not verify usefull in most case, but we can set $on_installation to true
     * 
     * @since 1.1.0
     * @access public
     * 
     * @param bool $on_installation If we are on the installation step. Default false.
     */
    public function __construct( $on_installation = false ) {
        $this->on_installation = $on_installation;
    }

    /**
     * Update_options.
     * 
     * Update all options in the database
     * Each option has it's own verification method
     * 
     * @since 1.1.0
     * @access public
     */
    public function update_options() {
        foreach ( self::$default_options as $option_name => $default_value ) {
            $option = get_option( $option_name );

            if ( $option === false && update_option( $option_name, false ) === false ) {
                add_option( $option_name, $default_value );
            }
            else {
                $new_value = $this->verify_option( $option_name, $option );
                if ( $option !== $new_value ) {
                    update_option( $option_name, $new_value );
                }
            }
        }
    }

    /**
     * Verify_option.
     * 
     * Launch the verification method of a specific option.
     * Note that each option must have it's own method verification, at least for update verification.
     * 
     * @since 1.1.0
     * @access public
     * 
     * @return mixed    the sanitize value of an option.
     * @return false    the option doesn't exist
     */
    public function verify_option( $option_name, $value ) {
        if ( array_key_exists( $option_name, self::$default_options ) ) {
            // Remove the 'tfi_'
            $option_name = substr( $option_name, 4 );

            return call_user_func( array( $this, 'verify_' . $option_name ), $value );
        }

        return false;
    }

    /**
     * Verify_shortcut.
     * 
     * @since 1.1.0
     * @access private
     * 
     * @param array $shortcut   the value to verify for the option tfi_shortcut
     * @return array            $shortcut sanitized
     */
    private function verify_shortcut( $shortcut ) {
        if ( ! is_array( $shortcut ) ) {
            return self::$default_options['tfi_shortcut'];
        }

        $new_shortcut = array();
        $new_shortcut['ctrl_key_used']  = isset( $shortcut['ctrl_key_used'] )  ? rest_sanitize_boolean( $shortcut['ctrl_key_used'] )  : false;
        $new_shortcut['alt_key_used']   = isset( $shortcut['alt_key_used'] )   ? rest_sanitize_boolean( $shortcut['alt_key_used'] )   : false;
        $new_shortcut['shift_key_used'] = isset( $shortcut['shift_key_used'] ) ? rest_sanitize_boolean( $shortcut['shift_key_used'] ) : false;

        if ( isset( $shortcut['key'] ) && is_numeric( $shortcut['key'] ) && $shortcut['key'] >= 65 && $shortcut['key'] <= 90 ) {
            $new_shortcut['key'] = $shortcut['key'];
        }
        else {
            $new_shortcut['key'] = self::$default_options['tfi_shortcut']['key'];
        }

        return $new_shortcut;
    }

    /**
     * Verify_user_page_id.
     * 
     * @since 1.1.0
     * @access private
     * 
     * @param int $new_user_page_id     the page id for the option tfi_user_page  
     * @return int                      $new_user_page_id sanitized
     */
    private function verify_user_page_id( $new_user_page_id ) {
        if ( ! is_numeric( $new_user_page_id ) ) {
            return self::$default_options['tfi_user_page_id'];
        }

		$pages = get_pages( array(
			'meta_key' => '_wp_page_template',
			'meta_value' => TFI_TEMPLATE_PAGE
        ) );

        foreach ( $pages as $page ) {
            if ( $page->ID == $new_user_page_id ) {
                return $new_user_page_id;
            }
        }
        
        // The page doesn't exist
        return self::$default_options['tfi_user_page_id'];
    }

    /**
     * Verify_user_types.
     * 
     * @since 1.1.0
     * @access private
     * 
     * @param array $user_types     contains all user types to verify for the option tfi_user_types
     * @return array                $user_types sanitized
     */
    private function verify_user_types( $user_types ) {
        if ( ! is_array( $user_types ) ) {
            return self::$default_options['tfi_user_types'];
        }

        $new_user_types = self::$default_options['tfi_user_types'];
        foreach ( $user_types as $user_type_slug => $user_type_name ) {
            $sanitize_user_type_name = filter_var( $user_type_name, FILTER_SANITIZE_STRING );
            $sanitize_user_type_slug = $this->create_slug_from_string( $sanitize_user_type_name );
            
            if ( ! array_key_exists( $sanitize_user_type_slug, $new_user_types ) && ! empty( $sanitize_user_type_slug ) ) {
                $new_user_types[$sanitize_user_type_slug] = $sanitize_user_type_name;
            }
        }

        return $new_user_types;
    }

    /**
     * Verify_file_folders.
     * 
     * @since 1.2.0
     * @access private
     * 
     * @param array $file_folders   contains all folder paths to verify for the option tfi_file_folders
     * @return array                $file_folders sanitized
     */
    private function verify_file_folders( $file_folders ) {
        if ( ! is_array( $file_folders ) ) {
            return self::$default_options['tfi_file_folders'];
        }

        $new_file_folders = self::$default_options['tfi_file_folders'];
        $default_parent_folder = array_key_first( $new_file_folders );
        foreach ( $file_folders as $file_folder_slug => $file_folder ) {
            if ( isset( $file_folder['display_name'] ) ) {
                $sanitize_file_folder_name = filter_var( $file_folder['display_name'], FILTER_SANITIZE_STRING );
                $sanitize_file_folder_slug = $this->create_slug_from_string( $sanitize_file_folder_name );
                $sanitize_file_folder_parent = $default_parent_folder;

                if ( isset( $file_folder['parent'] ) ) {
                    $sanitize_file_folder_parent = filter_var( $file_folder['parent'], FILTER_SANITIZE_STRING );
                }
                
                if ( ! array_key_exists( $sanitize_file_folder_slug, $new_file_folders ) && ! empty( $sanitize_file_folder_slug ) ) {
                    $new_file_folders[$sanitize_file_folder_slug]['display_name'] = $sanitize_file_folder_name;
                    $new_file_folders[$sanitize_file_folder_slug]['parent'] = $sanitize_file_folder_parent;
                }
            }
        }
        
        /**
         * Verification at the end, if every folders have a valid parent.
         * Obviously, the parent should be different that himself
         */
        foreach ( $new_file_folders as $new_file_folder_slug => $new_file_folder_datas ) {
            $parent_slug = $new_file_folder_datas['parent'];
            if ( ! array_key_exists( $parent_slug, $new_file_folders ) || $parent_slug === $new_file_folder_slug ) {
                $new_file_folder_datas['parent'] = $default_parent_folder;
            }
        }

        return $new_file_folders;
    }

    /**
     * Verify_fields.
     * 
     * @since 1.1.0
     * @access private
     * 
     * @param array $fields     contains all fields to verify for the option tfi_fields
     * @return array            $fields sanitized
     */
    private function verify_fields( $fields ) {
        if ( ! is_array( $fields ) ) {
            return self::$default_options['tfi_fields'];
        }

        $field_types = tfi_get_option( 'tfi_field_types' );
        $user_types = tfi_get_option( 'tfi_user_types' );
        $new_fields = array();

        foreach ( $fields as $field_slug => $field_value ) {
            $sanitize_field_slug = $this->create_slug_from_string( $field_slug );
            $sanitize_field_value = array(
                'real_name'      => 'No name set',
                'type'		     => 'text',
                'default'	     => '',
                'users' 	     => array(),
                'special_params' => array()
            );

            if ( isset( $field_value['real_name'] ) && ! empty( $field_value['real_name'] ) ) {
                $sanitize_field_value['real_name'] = filter_var( $field_value['real_name'], FILTER_SANITIZE_STRING );
            }
            if ( isset( $field_value['type'] ) && array_key_exists( $field_value['type'], $field_types ) ) {
                $sanitize_field_value['type'] = $field_value['type'];
            }
            if ( isset( $field_value['default'] ) ) {
                $sanitize_field_value['default'] = filter_var( $field_value['default'], FILTER_SANITIZE_STRING );
            }
            if ( isset( $field_value['users'] ) && is_array( $field_value['users'] ) ){
                foreach ( $field_value['users'] as $user_type ) {
                    if ( array_key_exists( $user_type, $user_types ) ) {
                        $sanitize_field_value['users'][] = $user_type;
                    }
                }
            }

            /**
             * Files special params
             */
            if ( $sanitize_field_value['type'] === 'image' ) {
                $file_folders = tfi_get_option( 'tfi_file_folders' );
                $sanitize_field_value['special_params']['folder'] = array_key_first( $file_folders );
                if ( isset( $field_value['special_params']['folder'] ) && array_key_exists( $field_value['special_params']['folder'], $file_folders ) ) {
                    $sanitize_field_value['special_params']['folder'] = $field_value['special_params']['folder'];
                }
            }

            /**
             * Image special params
             */
            if ( $sanitize_field_value['type'] === 'image' ) {
                $sanitize_field_value['special_params']['height'] = 20;
                $sanitize_field_value['special_params']['width'] = 20;

                if ( isset( $field_value['special_params']['height'] ) && is_numeric( $field_value['special_params']['height'] ) ) {
                    $sanitize_field_value['special_params']['height'] = floor( abs( $field_value['special_params']['height'] ) );
                }
                if ( isset( $field_value['special_params']['width'] ) && is_numeric( $field_value['special_params']['width'] ) ) {
                    $sanitize_field_value['special_params']['width'] = floor( abs( $field_value['special_params']['width'] ) );
                }
            }
            /**
             * Link special params
             */
            else if ( $sanitize_field_value['type'] === 'link' ) {
                $sanitize_field_value['special_params']['mandatory_domains'] = array();

                if ( isset( $field_value['special_params']['mandatory_domains'] ) && is_array( $field_value['special_params']['mandatory_domains'] ) ) {
                    foreach ( $field_value['special_params']['mandatory_domains'] as $domain_name ) {
                        if ( tfi_is_valid_domain_name( $domain_name ) ) {
                            $sanitize_field_value['special_params']['mandatory_domains'][] = $domain_name;
                        }
                    }
                }
            }

            $new_fields[$sanitize_field_slug] = $sanitize_field_value;
        }

        return $new_fields;
    }

    /**
     * Verify_users.
     * 
     * @since 1.1.0
     * @access private
     * 
     * @param array $users  contains all users to verify for the option tfi_users
     * @return array        $users sanitized
     */
    private function verify_users( $users ) {
        if ( ! is_array( $users ) ) {
            return self::$default_options['tfi_users'];
        }

        $user_types = tfi_get_option( 'tfi_user_types' );
        $fields     = tfi_get_option( 'tfi_fields' );
        $new_users  = array();

        /**
         * User verification according to $on_installation attribute is due to wp_roles->add_cap.
         * Indeed, this method doesn't change the actual users array and so administrators etc... do not have the access_intranet cap.
         * Because wp_roles->add_cap is called during installation, we need to do a different checkup
         */
        foreach ( $users as $user_id => $user_datas ) {
            $user = get_user_by( 'id', $user_id );
            if ( $user === false ) {
                continue;
            }
            if ( $this->on_installation && empty( array_intersect( $user->roles, InstallManager::$access_intranet_roles ) ) ) {
                continue;
            }
            if ( ! $this->on_installation && ! array_key_exists( 'access_intranet', $user->allcaps ) ) {
                continue;
            }

            $sanitize_user_datas = array(
                'user_type'		 => 'default_type',
                'special_fields' => array()
            );
            
            if ( isset( $user_datas['user_type'] ) && array_key_exists( $user_datas['user_type'], $user_types ) ) {
                $sanitize_user_datas['user_type'] = $user_datas['user_type'];
            }
            if ( isset( $user_datas['special_fields'] ) && is_array( $user_datas['special_fields'] ) ){
                foreach ( $user_datas['special_fields'] as $special_field ) {
                    if ( array_key_exists( $special_field, $fields ) ) {
                        $sanitize_user_datas['special_fields'][] = $special_field;
                    }
                }
            }

            $new_users[$user_id] = $sanitize_user_datas;
        }

        return $new_users;
    }

    /**
     * Verify_field_types.
     * 
     * This option cannot be changed by anyone.
     * It should be like set by default in the current version of the plugin.
     * 
     * @since 1.1.0
     * @access private
     * 
     * @param array $field_types    contains all field types to verify for the option tfi_field_types
     * @return array                the default and mandatory tfi_field_types option
     */
    private function verify_field_types( $field_types ) {
        return self::$default_options['tfi_field_types'];
    }

    /**
     * Create_slug_from_string.
     * 
     * Create a slug form a given string
     * It has no space, no capital letter and no special chars only underscores
     * 
     * @since 1.1.0
     * @access private
     * 
     * @param string $string    the string to create a slug from
     * @return string           $string as a slug 
     */
    private function create_slug_from_string( $string ) {
        return preg_replace( '/[^a-z0-9_]/', '', str_replace( ' ', '_', strtolower( $string ) ) );
    }
}