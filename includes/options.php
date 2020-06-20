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
                'users' => array( 'default_type' )
            ),
            'twitter' => array(
                'real_name' => 'Twitter',
                'type' => 'link',
                'default' => '',
                'users' => array( 'default_type' )
            )
        ),
        'tfi_users' => array()
    );

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

    public function verify_option( $option_name, $value ) {
        // Remove the 'tfi_'
        $option_name = substr( $option_name, 4 );

        if ( method_exists( $this, 'verify_' . $option_name ) ) {
            return call_user_func( array( $this, 'verify_' . $option_name ), $value );
        }

        return false;
    }

    /**
     * 
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
     * 
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
     * 
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
     * 
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
                'real_name' => 'No name set',
                'type'		=> 'text',
                'default'	=> '',
                'users' 	=> array()
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

            $new_fields[$sanitize_field_slug] = $sanitize_field_value;
        }

        return $new_fields;
    }

    /**
     * 
     */
    private function verify_users( $users ) {
        if ( ! is_array( $users ) ) {
            return self::$default_options['tfi_users'];
        }

        $user_types = tfi_get_option( 'tfi_user_types' );
        $fields     = tfi_get_option( 'tfi_fields' );
        $new_users  = array();

        foreach ( $users as $user_id => $user_datas ) {
            $user = get_user_by( 'id', $user_id );
            if ( $user === false || ! array_key_exists( 'access_intranet', $user->allcaps ) ) {
                wp_die( var_dump( $user->allcaps ) );
                continue;
            }
            wp_die( var_dump( $user )  . $user_id );

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
        wp_die( var_dump( $users ) );

        return $new_users;
    }

    private function verify_field_types( $field_types ) {
        return self::$default_options['tfi_field_types'];
    }

    private function create_slug_from_string( $string ) {
        return preg_replace( '/[^a-z0-9_]/', '', str_replace( ' ', '_', strtolower( $string ) ) );
    }
}