<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all intranet user info
 *
 * @since 1.0.0
 */
class User {

    /**
     * Id.
     * 
     * User id
     * 
     * @since 1.0.0
     * @since 1.2.0 Access set to public
     * @access public
     * 
     * @var int
     */
    public $id;

    /**
     * Is_allowed.
     * 
     * Is the user allowed to access intranet
     * 
     * @since 1.0.0
     * @access private
     * 
     * @var bool
     */
    private $is_allowed;

    /**
     * User_datas.
     * 
     * Contains everything about the user.
     * This is a cache to avois multiple calls to database
     * This is false if the user isn't register in the tfi_users option
     * 
     * @since 1.0.0
     * @access private
     * 
     * @var array|false
     */
    private $user_datas;

	/**
	 * User constructor.
	 *
	 * @since 1.0.0
	 * @access public
     * @param int $user_id the id of the user to get
	 */
    public function __construct( $user_id ) {
        $users = tfi_get_option( 'tfi_users' );

        $this->id           = $user_id;
        $this->is_allowed   = get_user_by( 'id', $user_id );
        if ( $this->is_allowed !== false ) {
            $this->is_allowed->has_cap( 'access_intranet' );
        }
        $this->user_datas   = array_key_exists( $user_id, $users ) ? $users[$user_id] : false;
    }

    /**
     * Has_intranet_access.
     * 
     * Is the user allowed to access intranet.
     * 
     * @since 1.0.0
     * @access public
     * @return bool
     */
    public function has_intranet_access() {
        return $this->is_allowed;
    }

    /**
     * Is_register.
     * 
     * Is the user register in the tfi_users option list.
     * 
     * @since 1.0.0
     * @access public
     * @return bool
     */
    public function is_register() {
        return $this->user_datas !== false ;
    }

    /**
     * Is_ok.
     * 
     * Is the user register AND allowed.
     * 
     * @since 1.0.0
     * @access public
     * @return bool
     */
    public function is_ok() {
        return $this->is_register() && $this->has_intranet_access();
    }

    /**
     * User_type.
     * 
     * Return the user type.
     * 
     * @since 1.0.0
     * @access public
     * @return string
     * @return false if not ok
     */
    public function user_type() {
        if ( ! $this->is_ok() ) {
            return false;
        }

        if ( ! array_key_exists( 'checked_user_type', $this->user_datas ) ) {
            $user_types = tfi_get_option( 'tfi_user_types' );

            if ( ! array_key_exists( $this->user_datas['user_type'], $user_types ) ) {
                $this->user_datas['checked_user_type'] = array_key_first( $user_types );
            }
            else {
                $this->user_datas['checked_user_type'] = $this->user_datas['user_type'];
            }

            unset( $this->user_datas['user_type'] );
                
        }

        return $this->user_datas['checked_user_type'];
    }

    /**
     * Allowed_fields.
     * 
     * Return all fields allowed for the user
     * A field can have the user_type in its 'users' array
     * A user can have specific fields in his 'special_fields' array
     * 
     * @since 1.0.0
     * @access public
     * @return array
     * @return false if not ok
     */
    public function allowed_fields() {
        if ( ! $this->is_ok() ) {
            return false;
        }

        if ( ! array_key_exists( 'allowed_fields', $this->user_datas ) ) {
            require_once TFI_PATH . 'includes/field.php';
            $allowed_fields = array();

            foreach ( tfi_get_option( 'tfi_fields' ) as $slug => $field ) {
                if ( in_array( $this->user_type(), $field['users'] ) || in_array( $slug, $this->user_datas['special_fields'] ) ) {
                    $allowed_fields[$slug] = new Field( $slug, $field['real_name'], $field['default'], $field['type'], $field['special_params'] );
                }
            }
            
            unset( $this->user_datas['special_fields'] );
            $this->user_datas['allowed_fields'] = $allowed_fields;
        }

        return $this->user_datas['allowed_fields'];
    }

    /**
     * Get_value_for_field.
     * 
     * Alias for the Field::get_value_for_user
     * 
     * @since 1.2.2
     * @access public
     */
    public function get_value_for_field( $field_slug, $type = 'real_url' ) {
        if ( ! $this->is_ok() || ! array_key_exists( $field_slug, $this->allowed_fields() ) ) {
            return null;
        }

        return $this->allowed_fields()[$field_slug]->get_value_for_user( $this, $type );
    }

    /**
     * Send_new_datas.
     * 
     * Update user datas into the database
     * 
     * @since 1.0.0
     * @access public
     * 
     * @param array $datas      Datas to send
     * @param array $files      All files to upload in the uploads folder. Default null
     * 
     * @return array            Errors or success on upload datas.
     * @return false            If not ok or if datas sending failed
     */
    public function send_new_datas( $datas, $files = null ) {
        if ( ! $this->is_ok() ) {
            return false;
        }

        require_once TFI_PATH . 'includes/field-sanitizor.php';
        require_once TFI_PATH . 'includes/file-manager.php';
        $field_sanitizor    = new FieldSanitizor;
        $file_manager       = new FileManager;

        $changes = array();
        $result = array();

        foreach ( $this->allowed_fields() as $field ) {
            if ( $field->is_multiple() ) {
                $is_data    = isset( $datas[$field->name] );
                $is_file    = isset( $files[$field->name] );
                $all        = $is_data ? $datas[$field->name] : ( $is_file ? $files[$field->name] : array() );
                $min        = $field->special_params['min_length'];
                $max        = $field->special_params['max_length'];
                $last_index = 0;

                /**
                 * If there is no datas, it can be because the user want to remove all fields.
                 * It should be possible, so the $changes var need to be updated to change the array on the database.
                 */
                if ( empty( $all ) ) {
                    $changes[$field->name] = $field->default_value();
                }
                else {
                    /**
                     * We transform the array, it needs to have sorted ascending key
                     */
                    foreach ( array_values( $all ) as $index => $value ) {
                        $last_index++;

                        $child_field = $field->get_field_for_index( $index );
                        $sanitation_result;
                        
                        if ( $is_data ) {
                            $sanitation_result  = $this->sanitize_non_file_data( $value, $child_field );
                        }
                        else {
                            $sanitation_result  = $this->sanitize_file_data( $value, $child_field );
                        }

                        $result[$field->name][$index] = $sanitation_result['result'];
                        if ( isset( $sanitation_result['change'] ) ) {
                            $changes[$field->name][$index] = $sanitation_result['change'];
                        }
                        else {
                            $old_value = $child_field->get_value_for_user( $this, 'upload_path' );
                            
                            /**
                             * If we don't set the old value, the key will missing to the new array and the old value risk to be deleted in database
                             */
                            if ( $old_value != '' ) {
                                $changes[$field->name][$index] = $old_value;
                            }
                        }
                    }
                }

                /**
                 * Once the loop end, if we don't have enough value, add default one
                 */
                if ( $last_index < $min ) {
                    for ( $i = $last_index; $i < $min; $i++ ) {
                        $changes[$field->name][$i] = $field->get_field_for_index( $i )->default_value();
                        $result[$field->name][$i]['tfi-warning'] = __( 'Minimum number of values required' );
                    }
                }
                /**
                 * If we have too much values, we delete the lasts
                 */
                else if ( $max != 0 && $last_index > $max ) {
                    for ( $i = $last_index - 1; $i >= $max; $i-- ) {
                        unset( $changes[$field->name][$i] );
                        unset( $result[$field->name][$i] );
                    }
                }
            }
            else if ( isset( $datas[$field->name] ) ) {
                $sanitation_result = $this->sanitize_non_file_data( $datas[$field->name], $field );
                $result[$field->name] = $sanitation_result['result'];
                if ( isset( $sanitation_result['change'] ) ) {
                    $changes[$field->name] = $sanitation_result['change'];
                }
            }
            else if ( isset( $files[$field->name] ) ) {
                $sanitation_result = $this->sanitize_file_data( $files[$field->name], $field );
                $result[$field->name] = $sanitation_result['result'];
                if ( isset( $sanitation_result['change'] ) ) {
                    $changes[$field->name] = $sanitation_result['change'];
                }
            }
        }

        if ( $this->update_user_datas( $changes ) ) {
            return $result;
        }
        return false;
    }

    /**
     * Sanitize_non_file_data.
     * 
     * Sanitize a data for a given field. Everything has been verify and all we need is to sanitize the value.
     * 
     * @since 1.2.2     Refactorization for multiple field
     * @access private
     * 
     * @param string    $data       The value to sanitize for the given field
     * @param Field     $field      The field for the value (it can be a field not set into User::allowed_fields())
     * 
     * @return array    'result' key is always set, to show message to the user
     *                  'change' key is set when database need to be change with this value
     */
    private function sanitize_non_file_data( $data, $field ) {
        $field_sanitizor    = new FieldSanitizor;
        $sanitation         = false;

        switch ( $field->type ) {
            case 'text':
                $sanitation = $field_sanitizor->sanitize_text_field( $data );
            break;
            case 'link':
                $sanitation = $field_sanitizor->sanitize_link_field( $data, $field->special_params );
            break;
            case 'number':
                $sanitation = $field_sanitizor->sanitize_number_field( $data, $field->special_params );
            break;
            case 'color':
                $sanitation = $field_sanitizor->sanitize_color_field( $data );
            break;
        }

        $old_value = $field->get_value_for_user( $this ); 

        if ( $sanitation === false ) {
            $to_return['result']['tfi-error'] = $field_sanitizor->last_error();
        }
        else if ( $old_value === $sanitation ) {
            $to_return['result']['tfi-info'] = __( 'No change' );
        }
        else {
            $to_return['change'] = $sanitation;
            $to_return['result']['tfi-success']  = __( 'This field has been successfully changed' );
        }

        return $to_return;
    }

    /**
     * Sanitize_file_data.
     * 
     * Sanitize a file for a given field. Everything has been verify and all we need is to sanitize the value.
     * 
     * @since 1.2.2     Refactorization for multiple field
     * @access private
     * 
     * @param array     $file       The post array file to verify
     * @param Field     $field      The field for the value (it can be a field not set into User::allowed_fields())
     * 
     * @return array    'result' key is always set, to show message to the user
     *                  'change' key is set when database need to be change with this value
     */
    private function sanitize_file_data( $file, $field ) {
        $to_return = array();

        if ( $file['error'] === 4 ) {
            $to_return['result']['tfi-info'] = __( 'No file given' );
            return $to_return;
        }

        $field_sanitizor    = new FieldSanitizor;
        $sanitation         = false;

        switch ( $field->type ) {
            case 'image':
                $sanitation = $field_sanitizor->sanitize_post_file_field( $field, $file, $this->id );
            break;
        }

        if ( $sanitation === false ) {
            $to_return['result']['tfi-error'] = $field_sanitizor->last_error();
            return $to_return;
        }
        
        $file_manager           = new FileManager;
        $to_return['change']    = $sanitation;
        $old_value              = $field->get_value_for_user( $this, 'upload_path' ); 

        if ( $old_value !== '' && ! $file_manager->remove_file( $old_value ) ) {
            $to_return['result']['tfi-error'] = __( 'The old image failed to removed' );
        }

        if ( ! $file_manager->upload_file( $file['tmp_name'], $sanitation ) ) {
            $to_return['result']['tfi-error'] = __( 'Impossible to upload the new image' );
        }
        else {
            $to_return['result']['tfi-success'] = __( 'This file has been uploaded with success' );
        }

        return $to_return;
    }

    /**
     * Set_values_for_fields.
     * 
     * Set a value to the database for a user.
     * It will update the database for each calls !
     * 
     * This method will update the database and that's all.
     * No verification are done on fields, on key or whatever. It is used to enforced data storage.
     * 
     * @since 1.2.0
     * @since 1.2.3     Remove the verification for each field
     * 
     * @param array     $new_datas    Contains names in keys and new values in values
     * 
     * @return bool     The success of the operation
     */
    public function set_values_for_fields( $new_datas ) {
        if ( ! $this->is_ok() ) {
            return false;
        }

        return $this->update_user_datas( $new_datas );
    }

    /**
     * User_db_datas.
     * 
     * Return the user datas from database.
     * Those datas are stored in the database so one call will be done only if needed
     * If user_datas has already be called, just return the $user_datas class attribute
     * 
     * @since 1.0.0
     * @since 1.2.2     Access pass in public
     * @access public
     * 
     * @return array
     * @return false if not ok
     * 
     * @global wpdb     $wpdb           The database object to get the datas
     */
    public function user_db_datas() {
        if ( ! $this->is_ok() ) {
            return false;
        }

        if ( ! array_key_exists( 'user_db_datas', $this->user_datas ) ) {
            global $wpdb;
    
            $result = $wpdb->get_var( "SELECT datas FROM " . $wpdb->prefix . TFI_TABLE . " WHERE user_id = " . $this->id );
            
            // If the result is null, it means that there is no user_id with this id in the database
            if ( $result === null ) {
                $this->user_datas['user_db_datas'] = array();
            }
            else {
                $this->user_datas['user_db_datas'] = maybe_unserialize( $result );
            }
        }

        return $this->user_datas['user_db_datas'];
    }

    /**
     * Update_user_datas.
     * 
     * Update user data into database
     * Datas send in this method should only be the datas which changed, not others.
     * 
     * @since 1.2.0     Refactorization
     * @access private
     * 
     * @param array $datas_to_changed   All datas which have been changed. /!\ Datas should be verified BEFORE /!\ 
     * @return bool The result of the query
     * 
     * @global wpdb $wpdb               The database object to update the table
     */
    private function update_user_datas( $datas_to_changed ) {
        $old_user_datas = $this->user_db_datas();
        $new_user_datas = array_merge( $old_user_datas, $datas_to_changed );

        if ( $old_user_datas !== $new_user_datas ) {
            global $wpdb;
            
            $db_result = $wpdb->update( $wpdb->prefix . TFI_TABLE, array( 'datas' => maybe_serialize( $new_user_datas ) ), array( 'user_id' => $this->id ), null, '%d' );

            /**
             * If no row has been updates, it's a problem
             */
            if ( $db_result === false || $db_result === 0 ) {
                return false;
            }

            /**
             * Datas changed
             */
            $this->user_datas['user_db_datas'] = $new_user_datas;
        }

        /**
         * Even if datas didn't changed in database, it is possible that files changes for example.
         * So every datas send in this method will be send to the user_datas_changed hook
         */
        $changed_fields = array();
        $values = array();

        foreach ( $datas_to_changed as $field_name => $change ) {
            $changed_fields[$field_name] = $this->allowed_fields()[$field_name];
        }

        /**
         * When user datas has been changed, a filter is applying to allow post process
         * 
         * @param User  $this               User whom datas has been modified
         * @param array $changed_fields     All Field objects which changed, keys are field_slug
         * 
         * @since 1.2.0
         * @since 1.2.2     Return the user instead of the id, don't return values. 
         */
        apply_filters( 'tfi_user_datas_changed', $this, $changed_fields );

        return true;
    }
}