<?php

if ( ! function_exists( 'tfi_options_properties' ) ) {
    /**
     * Tfi_options_properties.
     * 
     * Function to know if every keys are set on options.
     * It allows to be sure that the plugin will work correctly
     * 
     * For each options you have different keys :
     *      Mandatory for all :
     *      - 'type'        =>  {string} possible value : 'bool', 'int', 'string' or 'array'
     *      - 'default'     =>  {mixed}  needs to be of the same type than the 'type' key
     *      - 'mandatory'   =>  {mixed}  replace the 'default' and 'type' keys, this value is mandatory and cannot be different
     * 
     *      Optionnal :
     *      - 'values'      =>  {array} all values possible for the field, values need to have the same type than 'type' key
     *      - 'length'      =>  {int}
     *                          (type = string) the length of the string have to be equal to this value
     *                          (type = array)  the size of the table have to be equal to this value (useless if there is no 'custom_keys' key is set)
     *      - 'max-length'  =>  {int}
     *                          (type = string) the length of the string have to be lesser or equal than this value
     *                          (type = array)  the size of the table have to be lesser or equal than this value (useless if there is no 'custom_keys' key is set)
     *      - 'min-length'  =>  {int}
     *                          (type = string) the length of the string have to be greater or equal than this value
     *                          (type = array)  the size of the table have to be greater or equal than this value (useless if there is no 'custom_keys' key is set)
     * 
     *      - 'min'         =>  {int}
     *                          (type = int)    this value is the min value of the int
     *      - 'max'         =>  {int}
     *                          (type = int)    this value is the max value of the int
     * 
     *      - 'keys'        =>  {array}
     *                          (type = array) all the key needed for this array. Those fields are not count for a length, max-length or min-length option.
     *                              Keys need to be the key's mandatory name
     *                              Values are arrays with the same parameters than an option value   
     *      - 'custom_keys' =>  {array}
     *                          (type = array) used when the array can be customize by the user with specific type
     *                              This is an array with the same parameters than an option value
     *                              'default' and 'mandatory' keys are useless here because if the type isn't respected, it just delete the key
     * 
     * @since 1.0.0
     * @return array all options properties
     */
    function tfi_options_properties() {
        return array(
            'tfi_shortcut' => array(
                'type' => 'array',
                'keys' => array(
                    'ctrl_key_used' => array(
                        'type' =>'bool',
                        'default' => true
                    ),
                    'alt_key_used' => array(
                        'type' =>'bool',
                        'default' => true
                    ),
                    'shift_key_used' => array(
                        'type' =>'bool',
                        'default' => false
                    ),
                    'key' => array(
                        'type' =>'int',
                        'min' => 65,
                        'max' => 90,
                        'default' => 76
                    )
                ),
                'default' => array(
                    'ctrl_key_used' => true,
                    'alt_key_used' => true,
                    'shift_key_used' => false,
                    'key' => 76
                ),
            ),
            'tfi_user_page_id' => array(
                'type' => 'int',
                'default' => -1
            ),
            'tfi_user_types' => array(
                'type' => 'array',
                'keys' => array(
                    'default_type' => array(
                        'mandatory' => 'Default type'
                    )
                ),
                'custom_keys' => array(
                    'type' => 'string'
                ),
                'default' => array(
                    'default_type' => 'Default type'
                )
            ),
            'tfi_field_types' => array(
                'mandatory' => array(
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
                )
            ),
            'tfi_fields' => array(
                'type' => 'array',
                'custom_keys' => array(
                    'type' => 'array',
                    'keys' => array(
                        'real_name' => array(
                            'type' => 'string',
                            'default' => ''
                        ),
                        'type' => array(
                            'type' => 'string',
                            'values' => array( 'image', 'link', 'text' ),
                            'default' => 'text'
                        ),
                        'default' => array(
                            'type' => 'string',
                            'default' => ''
                        ),
                        'users' => array(
                            'type' => 'array',
                            'custom_keys' => array(
                                'type' => 'string'
                            ),
                            'default' => array()
                        )
                    ),
                    'default' => array(
                        'real_name' => '',
                        'type' => 'text',
                        'default' => '',
                        'users' => array()
                    )
                ),
                'default' => array(
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
                )
            ),
            'tfi_users' => array(
                'type' => 'array',
                'custom_keys' => array(
                    'type' => 'array',
                    'keys' => array(
                        'user_type' => array(
                            'type' => 'string',
                            'default' => 'default_type' 
                        ),
                        'special_fields' => array(
                            'type' => 'array',
                            'custom_keys' => array(
                                'type' => 'string'
                            ),
                            'default' => array()
                        )
                    )
                ),
                'default' => array(
                    get_current_user_id() => array (
                        'user_type' => 'default_type',
                        'special_fields' => array()
                    )
                )
            )
        );
    }
}

if ( ! function_exists( 'tfi_get_default_options' ) ) {
    /**
     * Tfi_get_default_options.
     * 
     * All options used in the plugin with their default values
     * 
     * @since 1.0.0
     * @return array all default options
     */
    function tfi_get_default_options() {
        return array(
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
                    'users' => array( "default_type" )
                ),
                'twitter' => array(
                    'real_name' => 'Twitter',
                    'type' => 'link',
                    'default' => '',
                    'users' => array( "default_type" )
                )
            ),
            'tfi_users' => array(
                get_current_user_id() => array (
                    'user_type' => 'default_type',
                    'special_fields' => array()
                )
            )
        );
    }
}

if ( ! function_exists( 'tfi_get_users' ) ) {
    /**
     * Tfi_get_user.
     * 
     * Get all tfi register user, it can be all users of a specific type.
     * 
     * @since 1.0.1
     * @param string $type The wanted user type. Default = null
     * @return array all users of the database with a specific type if asking.
     */
    function tfi_get_user( $user_type = null ) {
        if ( $user_type === null ) {
            return get_option( 'tfi_users' );
        }
        else {
            $to_return = array();

            foreach ( get_option( 'tfi_users' ) as $key => $value ) {
                if ( isset( $value['user_type'] ) && $value['user_type'] == $user_type ) {
                    $to_return[$key] = $value;
                }
            }

            return $to_return;
        }
    }
}

if ( ! function_exists( 'tfi_get_option' ) ) {
    /**
     * Tfi_get_option.
     * 
     * All options used in the plugin with their default values
     * 
     * @since 1.0.0
     * @param string $option_name The wanted option
     * @return mixed the option default value if not existing
     */
    function tfi_get_option( $option_name ) {
        $default = false;
        switch ( $option_name ) {
            case 'tfi_fields':
            case 'tfi_users':
            case 'tfi_field_types':
            case 'tfi_user_types':
                $default = array();
            break;
            case 'tfi_user_page':
                $default = -1;
            break;
            case 'tfi_shortcut':
                $default = array(
                    'ctrl_key_used' => false,
                    'alt_key_used' => false,
                    'shift_key_used' => false,
                    'key' => 0
                );
            break;
        }

        return get_option( $option_name, $default );
    }
}

if ( ! function_exists( 'tfi_delete_files' ) ) {
    /**
     * Tfi_delete_files.
     * 
     * Delete all files inside a directory recursively.
     * 
     * @since 1.0.0
     * @param string $target The path to the directory or file to delete
     * @author Lewis Cowles https://paulund.co.uk/php-delete-directory-and-files-in-directory#:~:text=files%20and%20delete%20them%20by,(%24dirname)%3B%20if%20(!%24
     */
    function tfi_delete_files( $target ) {
        if ( is_dir( $target ) ) {
            $files = glob( $target . '*', GLOB_MARK ); // GLOB_MARK adds a slash to directories returned

            foreach( $files as $file ){
                tfi_delete_files( $file );      
            }

            rmdir( $target );
        }
        else if ( is_file( $target ) ) {
            unlink( $target );  
        }
    }
}

if ( ! function_exists( 'tfi_re_array_files' ) ) {
    /**
     * Tfi_re_array_files.
     * 
     * Convenient function which allows to reformate $_FILES structure (whith multiple files).
     * Use this function only with MULTIPLE FILES !
     * Otherwise, it will do the opposite than wanted 
     * 
     * @since 1.0.0
     * @param array $file_post The array of files given by $_FILES global variable
     * @return array The same array but with reformated 
     */
    function tfi_re_array_files( &$file_post ) {
        $file_ary = array();
        $file_keys = array_keys( $file_post );

        foreach ( $file_post['name'] as $file_name => $file_value ) {
            foreach ( $file_keys as $key ) {
                $file_ary[$file_name][$key] = $file_post[$key][$file_name];
            }
        }

        return $file_ary;
    }
}