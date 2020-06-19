<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Everything concerning options are here 
 *
 * @since 1.0.1
 */
class OptionsManager {

    /**
     * Options_properties.
     * 
     * An array of properties for each options.
     * It allows to be sure that the plugin will work correctly
     * 
     * For each options you have different keys :
     *      Mandatory for all :
     *      - 'type'        =>  {string} possible value : 'bool', 'int', 'string' or 'array'
     *      - 'default'     =>  {mixed}  needs to be of the same type than the 'type' key
     *      - 'mandatory'   =>  {mixed}  replace the 'default' and 'type' keys, this value is mandatory and cannot be different
     * 
     *      Optionnal :
     *      - 'callback'    =>  {string}        an OptionsManager method to verify the value.
     *                                          the callback method will return the new value or null if nothing need changes.
     *                                          the callback method needs to have 0, 1 or 2 parameter(s).
     *                                          this is the last step of the verification
     *                                          be carefull to add the key 'custom_keys' = true for array whiche needs custom verification on rows  
     * 
     *      - 'values'      =>  {array}         all values possible for the field, values need to have the same type than 'type' key.
     *                                          it has the priority on all other optionnal keys.
     * 
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
     * 
     *      - 'optionnal_keys' => {array}
     *                          (type = array) the same than the 'keys' key but keys here are optionnals.
     * 
     *      - 'custom_keys' =>  {array|true}
     *                          (type = array) used when the array can be customize by the user with specific type
     *                              If true, it means that custom rows won't be remove but won't be verify neither
     *                              If array, this is an array with the same parameters than an option value
     *                              'default' key is useless here because if the type isn't respected, it just delete the key
     * 
     * @since 1.0.1
     * @static
     * 
     * @var array
     */
    private static $options_properties = array(
        'tfi_shortcut' => array(
            'type' => 'array',
            'keys' => array(
                'ctrl_key_used' => array(
                    'type' =>'bool',
                    'default' => false
                ),
                'alt_key_used' => array(
                    'type' =>'bool',
                    'default' => false
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
            'default' => -1,
            'callback' => 'verify_user_page_id'
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
                        'height' => array(
                            'type' => 'int',
                            'default' => 0
                        ),
                        'width' => array(
                            'type' => 'int',
                            'default' => 0
                        )
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
                'custom_keys' => true,
                'callback' => 'verify_field',
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
            'default' => array()
        )
    );

    /**
     * Non_mandatory_key_error.
     * 
     * The error string return when an error occured and the key is not mandatory
     * This string should be a value never used by a user like a big undefined word
     * It's because the value return is checked and if === this_var it assumes that an error occured and delete the key
     * 
     * @since 1.0.1
     * @static
     * 
     * @var string
     */
    private static $non_mandatory_key_error = 'dgeuilgezlifzeilcbgfezcuec';

    /**
     * All_options_name.
     * 
     * Get the name of all options
     * 
     * @since 1.0.1
     * @access public
     * 
     * @return array values are strings of all options' name
     */
    public function all_options_name() {
        $options_name = array();

        foreach ( self::$options_properties as $option_name => $properties ) {
            $options_name[] = $option_name;
        }

        return $options_name;
    }

    /**
     * Update_options.
     * 
     * Update all options in the database
     * Each option has a property array, set in the OptionsManager::options_properties attribute
     * 
     * @since 1.0.1
     * @access public
     */
    public function update_options() {
        foreach( self::$options_properties as $option_name => $option_properties ) {
            $option = get_option( $option_name );

            try {
                if ( $option === false && update_option( $option_name, false ) === false ) {
                    add_option( $option_name, $this->verify_option( null, $option_properties, $option_name ) );
                }
                else {
                    $result = $this->verify_option( $option, $option_properties, $option_name );
                    if ( $result !== null ) {
                        update_option( $option_name, $result );
                    }
                }
            } catch ( \Exception $e ) {
                error_log( __( 'Exception handle during an option update on activation' ) );
                error_log( $e->getMessage() );
            }
        }
    }

    /**
     * Sanitize_option.
     * 
     * Sanitize when an option has been changed by someone.
     * This method should be called to sanitize options input
     * 
     * @since 1.0.1
     * @access public
     * 
     * @param string    $option_name    The name of the option
     * @param mixed     $option_value   The new value of the option
     * 
     * @return mixed    The sanitize new value
     */
    public function sanitize_option( $option_name, $option_value ) {
        if ( isset( self::$options_properties[$option_name] ) ) {
            try {
                $result = $this->verify_option( $option_value, self::$options_properties[$option_name], $option_name );

                if ( $result !== null ) {
                    return $result;
                }
                return $option_value;
            } catch (\Exception $e) {
                error_log( __( 'Exception handle during an option update on admin panel' ) );
                error_log( $e->getMessage() );
            }
        }

        // We need to return something
        return $option_value;
    }

    /**
     * Verify_option.
     * 
     * This method verify an option field.
     * It will do it recursively if an option is an array
     * 
     * @since 1.0.1
     * @access private
     * 
     * @param mixed     $option_value       the actual value of the option which need verification
     * @param array     $option_properties  the option array
     * @param string    $option_name        the name of the option to debug
     * @param bool      $mandatory          if the option is mandatory, if not, the value will be deleted when there is an error, not the default value
     * 
     * @return mixed        the value to modify for the option
     * @return null         the option is okay and don't need change
     * @return self::$non_mandatory_key_error      the option is not mandatory and its value is wrong
     * @throws \Exception    the option is not okay and it is impossible to modify the option
     */
    private function verify_option( $option_value, $option_properties, $option_name, $mandatory = true ) {
        /**
         * When an option has a mandatory key, it's override everything else
         */
        if ( isset( $option_properties['mandatory'] ) ) {
            if ( $option_value === $option_properties['mandatory'] ) {
                return null;
            }
            return $option_properties['mandatory'];
        }

        // If the given value is null, we can't return null because it means that a null value will be set on an option
        // So it will return the default value
        $cant_be_null = $option_value === null;

        /**
         * Type verification
         */
        if ( ! isset( $option_properties['type'] ) ) {
            throw new \Exception( $option_name . ' -> ' . __( 'This option don\'t have a type key' ) );
        }
        
        $type = $option_properties['type'];

        if ( ! in_array( $type, array( 'bool', 'int', 'string', 'array' ) ) ) {
            throw new \Exception( $option_name . ' -> ' . __( 'This option don\'t have a good type, it can only have one of this values\n : \'bool\', \'int\', \'string\' and \'array\'' ) );
        }

        /**
         * When type = bool, it's a special case because value are store like strings and we can't know if this is a bool, so we just convert the value as a bool
         */
        if ( $type == 'bool' ) {
            $option_value = (bool) $option_value;
        }

        /**
         * The value needs to be like the specific type 
         */
        $type_verification_callback = $type === 'int' ? 'is_numeric' : 'is_' . $type;
        if ( ! call_user_func( $type_verification_callback, $option_value ) ) {
            return $mandatory ? $this->return_default_value( $option_properties, $option_name ) : self::$non_mandatory_key_error;
        }

        /**
         * It's better to works with real integer; bu after the type verification
         */
        if ( $type == 'int' ) {
            $option_value = (int) $option_value;
        }

        /**
         * Since here, it's possible to have a value to return.
         * We need to keep it to call the callback method at the end.
         */
        $to_return = null;

        /**
         * We can have a value array.
         * The option value needs to be one of this values
         */
        if ( isset( $option_properties['values'] ) && is_array( $option_properties['values'] ) ) {
            if ( in_array( $option_value, $option_properties['values'], true ) ) {
                $to_return = null;
            }
            else {
                $to_return = $mandatory ? $this->return_default_value( $option_properties, $option_name ) : self::$non_mandatory_key_error;
            }
        }
        else {
            /**
             * When type = bool, we have nothing more to do
             * The value has been converted so we need to retutn it in all case to be sure.
             * It means that a bool option will always be override in the database
             */
            if ( $type == 'bool' ) {
                $to_return = $option_value;
            }

            /**
             * When type = int, we can have a max and mon value
             */
            else if ( $type == 'int' ) {
                if ( ( isset( $option_properties['max'] ) && $option_properties['max'] < $option_value ) ||
                        ( isset( $option_properties['min'] ) && $option_properties['min'] > $option_value ) ) {
                        $to_return = $mandatory ? $this->return_default_value( $option_properties, $option_name ) : self::$non_mandatory_key_error;
                }
            }

            /**
             * When type = string, we can have a special length value
             */
            else if ( $type == 'string' ) {
                if ( ( isset( $option_properties['length'] ) && $option_properties['length'] != strlen( $option_value ) ) ||
                        ( isset( $option_properties['min-length'] ) && $option_properties['min-length'] > strlen( $option_value ) ) ||
                        ( isset( $option_properties['max-length'] ) && $option_properties['max-length'] < strlen( $option_value ) ) ) {
                            $to_return = $mandatory ? $this->return_default_value( $option_properties, $option_name ) : self::$non_mandatory_key_error;
                }
            }

            /**
             * When type = array, we need to verify the length + all the keys and custom_keys
             */
            else if ( $type == 'array' ) {
                $keys_length = 0;

                // Keep a copy to unset every forbidden keys of custom_keys is not set
                $copy = $option_value;

                // If this bool is set to true, it means that the array changed, so, we return the new value
                $changed = false;

                /**
                 * Verification of all mandatory keys
                 */
                if ( isset( $option_properties['keys'] ) && is_array( $option_properties['keys'] ) ) {
                    $keys = $option_properties['keys'];
                    $keys_length += count( $keys );

                    /**
                     * If the key is well set, we verify its value recursively
                     * If the key don't exist, we take the default value
                     */
                    foreach ( $keys as $option_key => $key_properties ) {
                        if ( isset( $option_value[$option_key] ) ) {
                    
                            $result = $this->verify_option( $option_value[$option_key], $key_properties, $option_name . '/' . $option_key );

                            if ( $result !== null ) {
                                $option_value[$option_key] = $result;
                                $changed = true;
                            }
                        } else {
                            $option_value[$option_key] = $this->return_default_value( $key_properties, $option_name . '/' . $option_key );
                            $changed = true;
                        }

                        /**
                         * Unset the key from the copy
                         * Allow to keep all custom keys
                         */
                        unset( $copy[$option_key] );
                    }
                }

                /**
                 * Verification of all optionnal keys
                 */
                if ( isset( $option_properties['optionnal_keys'] ) && is_array( $option_properties['optionnal_keys'] ) ) {
                    $optionnal_keys = $option_properties['optionnal_keys'];
                    $keys_length += count( $optionnal_keys );

                    /**
                     * If the key is well set, we verify its value recursively
                     * If the key don't exist, we do nothing
                     */
                    foreach ( $optionnal_keys as $option_key => $key_properties ) {
                        if ( isset( $option_value[$option_key] ) ) {
                            $result = $this->verify_option( $option_value[$option_key], $key_properties, $option_name . '/' . $option_key );

                            if ( $result === self::$non_mandatory_key_error ) {
                                unset( $option_value[$option_key] );
                                $changed = true;
                            }
                            else if ( $result !== null ) {
                                $option_value[$option_key] = $result;
                                $changed = true;
                            }
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
                     * If this parameter is set to true it's said
                     * "I don't want to destroy every rows but i don't want to verify them neither"
                     * It's usefull when thee is a callback function at the end
                     */
                    if ( $option_properties['custom_keys'] !== true ) {
                        /**
                         * Verify each custom keys
                         * A custom keys isn't mandatory
                         */
                        foreach( $copy as $option_key => $sub_option_value ) {
                            $result = $this->verify_option( $sub_option_value, $option_properties['custom_keys'], $option_name . '/' . $option_key, false );
    
                            /**
                             * If the result is self::$non_mandatory_key_error an error occured
                             */
                            if ( $result === self::$non_mandatory_key_error ) {
                                unset( $option_value[$option_key] );
                                $changed = true;
                            }
                            else if ( $result !== null ) {
                                $option_value[$option_key] = $result;
                                $changed = true;
                            }
                        }
                    }
                }
                else if ( ! empty( $copy ) ) {
                    /**
                     * Custom keys are not allowed, so we delete every unwanted keys
                     */
                    foreach ( $copy as $key => $value ) {
                        unset( $option_value[$key] );
                        $changed = true;
                    }
                }

                /**
                 * The keys length is add to lengths properties because mandatory keys don't count like a row
                 */
                if ( ( isset( $option_properties['length'] ) && $option_properties['length'] + $keys_length != count( $option_value ) ) ||
                        ( isset( $option_properties['min-length'] ) && $option_properties['min-length'] + $keys_length > count( $option_value ) ) ||
                        ( isset( $option_properties['max-length'] ) && $option_properties['max-length'] + $keys_length < count( $option_value ) ) ) {
                        return $mandatory ? $this->return_default_value( $option_properties, $option_name ) : self::$non_mandatory_key_error;
                }

                if ( $changed ) {
                    $to_return = $option_value;
                }
            }
        }

        /**
         * Last step
         * If there is a callback key, and that this is a valid callback, calls it
         * The callback can have 1 or 2 parameters :
         *      - (type)    The new option value
         *      - (string)  The option name
         * 
         * No parameters are required for callbacks but the max is 2.
         * The return type must be (type)
         */
        if ( isset( $option_properties['callback'] ) && is_string( $option_properties['callback'] ) && method_exists( $this, $option_properties['callback'] ) ) {
            $reflection = new \ReflectionMethod( 'TFI\OptionsManager::' . $option_properties['callback'] );
            $callback = array( $this, $option_properties['callback'] );

            $new_value = $to_return == null ? $option_value : $to_return;

            if ( $reflection->getNumberOfParameters() == 0 ) {
                $to_return = call_user_func( $callback );    
            }
            else if ( $reflection->getNumberOfParameters() == 1 ) {
                $to_return = call_user_func( $callback, $new_value );    
            }
            else if ( $reflection->getNumberOfParameters() == 2 ) {
                $to_return = call_user_func( $callback, $new_value, $option_name );    
            }
            else {
                throw new \Exception( $option_name . ' -> ' . __( 'The callback for this option need to have at most 2 parameters' ) );
            }
        }

        if ( $cant_be_null && $to_return === null ) {
            return $this->return_default_value( $option_properties, $option_name );
        }

        return $to_return;
    }

    /**
     * Return_default_value.
     * 
     * This method is for factorization of the InstallManager::verify_option method.
     * Return the default value or throw an error if there is no default key 
     * 
     * @since 1.0.1
     * @access private
     * 
     * @param array     $option_properties  the option array
     * @param string    $option_name        the option name for debug
     * 
     * @return mixed        the value to modify for the option
     * @throws \Exception   the option is not okay and it is impossible to modify the option
     */
    private function return_default_value( $option_properties, $option_name ) {
        if ( isset( $option_properties['default'] ) && isset( $option_properties['type'] ) ) {
            $default_value = $option_properties['default'];
            $type = $option_properties['type'];
            if ( $type == 'bool' ) {
                return (bool) $default_value;
            }
            if ( call_user_func( 'is_' . $type, $default_value ) ) {
                return $default_value;
            }
            throw new \Exception( sprintf( __( $option_name . ' -> ' . 'The default value of this option should be a value of type %s' ), $type ) );
        }
        throw new \Exception( __( $option_name . ' -> ' . 'This option don\'t have a default value' ) );
    }

    /**
     * Verify_field.
     * 
     * Callback for a field validation,
     * 
     * @since 1.0.1
     * @access private
     * 
     * @param array     $field          the field to serialized
     * @param string    $field_name     the name of the field
     * 
     * @return array        the field need to be override
     * @return null         the field is okay and don't need change
     * @throws \Exception   the option is not okay and it is impossible to modify the option
     */
    private function verify_field( $field, $field_name ) {
        // We can do this because this option has been set BEFORE tfi_fields in the database
        // When this method is called, tfi_field_types have already been verify
        $field_types = get_option( 'tfi_field_types' );

        // All needed keys for a field
        $keys = array(
            'real_name' => array(
                'type' => 'string',
                'default' => ''
            ),
            'type' => array(
                'type' => 'string',
                'values' => array_keys( $field_types ),
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
            ),
            'special_params' => array(
                'type' => 'array',
                'default' => array(),
                'keys' => array()
            )
        );

        $changed = false;
        
        // First verify the type
        $type = isset( $field['type'] ) ? $field['type'] : null;
        $result = $this->verify_option( $type, $keys['type'], $field_name . '/type' );

        if ( $result !== null ) {
            $type = $result;
            $changed = true;
        }

        // Place all special params for this field type
        $special_params = $field_types[$type]['special_params'];
        foreach ( $special_params as $key => $properties ) {
            $keys['special_params']['keys'][$key] = $properties;
            $keys['special_params']['default'][$key] = $properties['default'];
        }

        // We don't need the 'default' key because a field is not mandatory
        $properties = array(
            'type' => 'array',
            'keys' => $keys
        );

        // Unset for conveniance, they will be deleted anyway in the verify_option below
        // Because the type is already verified
        unset( $field['type'] );
        unset( $keys['type'] );

        $result = $this->verify_option( $field, $properties, $field_name, false );

        if ( $result === self::$non_mandatory_key_error ) {
            return $result;
        }

        if ( $result !== null ) {
            $changed = true;
            $field = $result;
        }

        $field['type'] = $type;

        if ( $changed ) {
            return $field;
        }
        return null;
    }

    /**
     * Verify_user_page_id.
     * 
     * Callback for the user_page validation,
     * 
     * @since 1.0.1
     * @access private
     * 
     * @param int   $new_user_page_id   the field to serialized
     * 
     * @return int          the user_id need to be override
     * @return null         the user_id is okay and don't need change
     */
    private function verify_user_page_id( $new_user_page_id ) {
		$pages = get_pages( array(
			'meta_key' => '_wp_page_template',
			'meta_value' => TFI_TEMPLATE_PAGE
        ) );

        foreach ( $pages as $page ) {
            if ( $page->ID == $new_user_page_id ) {
                return $new_user_page_id;
            }
        }

        // If the page doesn't exist, return null, because we don't change the old value
        return null;
    }
}