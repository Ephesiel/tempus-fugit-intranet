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
                $is_data = isset( $datas[$field->name] );
                $is_file = isset( $files[$field->name] );
                $all = $is_data ? $datas[$field->name] : ( $is_file ? $files[$field->name] : array() );

                if ( $is_data || $is_file ) {
                    foreach ( $all as $index => $value ) {
                        $child_field        = $field->get_field_for_index( $index );
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
                            $changes[$field->name][$index] = $child_field->get_value_for_user( $this );
                        }
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

        $old_value  = $field->get_value_for_user( $this ); 
        $to_return  = array();

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
     * The new value isn't sanitize !!!
     * It will update the database for each calls !
     * 
     * @since 1.2.0
     * 
     * @param array     $new_datas    Contains names in keys and new values in values
     * 
     * @return bool     The success of the operation
     */
    public function set_values_for_fields( $new_datas ) {
        if ( ! $this->is_ok() ) {
            return false;
        }

        $datas = array();
        
        foreach ( $new_datas as $field_slug => $field_value ) {
            if ( array_key_exists( $field_slug, $this->allowed_fields() ) ) {
                $datas[$field_slug] = $field_value;
            }
        }

        return $this->update_user_datas( $datas );
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

/**
 * class Field
 * 
 * Store a field and all its datas to use it easily
 * 
 * @since 1.0.0
 * @since 1.1.0 Add $special_params attribute
 * @since 1.2.2 Add $parent and $index attributes
 */
class Field {
    public $name;
    public $display_name;
    public $type;
    public $special_params;
    private $default_value;
    private $parent;
    private $index;

    /**
     * Field constructor
     * 
     * @since 1.0.0
     * @param string    $name               The field's slug 
     * @param string    $display_name       Pretty name displayed on html
     * @param string    $default_value      The field's default
     * @param string    $type               The field's type (link, image, text...)
     * @param array     $special_params     Contains alla special params of a specific field
     */
    public function __construct( $name, $display_name, $default_value, $type, $special_params ) {
        $this->name             = $name;
        $this->display_name     = $display_name;
        $this->default_value    = $default_value;
        $this->type             = $type;
        $this->special_params   = $special_params;
        $this->parent           = null;
        $this->index            = -1;
    }

    public function is_file() {
        return $this->type === 'image';
    }

    public function is_multiple() {
        return $this->type === 'multiple';
    }

    /**
     * Default_value.
     * 
     * Return the real default value, because multiple fields have a default value for their child.
     * 
     * @since 1.2.2
     * @access public
     * 
     * @return mixed    The default value
     */
    public function default_value() {
        return $this->is_multiple() ? array() : $this->default_value;
    }

    /**
     * Get_field_for_index.
     * 
     * Return, for multiple field, a field object corresponding to the wanted index.
     * 
     * @since 1.2.2
     * @access public
     * 
     * @param   int     $index  The wanted index for the new field
     * @return  Field           The field created or $this if this is not a multiple field. 
     */
    public function get_field_for_index( $index ) {
        if ( ! $this->is_multiple() ) {
            return $this;
        }

        $index          = abs( $index );
        $name           = $this->name . '_' . $index;
        $display_name   = $this->display_name . ' - ' . $index;
        $type           = $this->special_params['type'];
        $default_value  = $this->default_value;
        $special_params = $this->special_params['multiple_field_special_params'];

        $field = new Field( $name ,$display_name, $default_value, $type, $special_params );
        $field->parent = $this;
        $field->index = $index;

        return $field;
    }

    /**
     * Get_value_for_user.
     * 
     * Return value for the wanted user or null if it's not possible.
     * 
     * @since 1.2.2     Move from User to Field
     * @access public
     * 
     * @param   User    $user   The user to get value from
     * @param   string  $type   If the field is a file, you have multiple choice :
     *                              - 'real_url' (default)  : give you the url of the file (to display an image for example)
     *                              - 'absolute_path'       : give you the path for the file (usefull when move file)
     *                              - 'upload_path'         : give you the path from inside the upload dir (this is the database value)
     * 
     * @return  mixed           The value for this field and this user
     * @return  null            If the user don't have any access
     */
    public function get_value_for_user( $user, $type = 'real_url'  ) {
        $field   = $this;
        $indexes = array();

        /**
         * Only the first parent is set into database (only multiple fields have parents).
         * So the field needs to be a possible allowed fields for the user.
         * Keep index to retrieve the value after.
         */
        while ( $field->parent !== null ) {
            array_unshift( $indexes, $field->index );
            $field = $field->parent;
        }

        if ( ! $user->is_ok() || ! array_key_exists( $field->name, $user->allowed_fields() ) ) {
            return null;
        }

        $value;

        if ( ! array_key_exists( $field->name, $user->user_db_datas() ) ) {
            $value = $this->default_value();
        }
        else {
            $value = $user->user_db_datas()[$field->name];
            /**
             * Loop on indexes to get the value.
             */
            foreach ( $indexes as $index ) {
                if ( ! isset( $value[$index] ) ) {
                    $value = $this->default_value();
                    break;
                }

                $value = $value[$index];
            }
        }

        /**
         * Return the good string for a file (link, path, none etc...)
         */
        if ( $this->is_file() && ! empty( $value ) && $type !== 'upload_path' ) {
            require_once TFI_PATH . 'includes/file-manager.php';
            $file_manager = new FileManager;

            if ( $type === 'absolute_path' ) {
                $value = $file_manager->get_file_link( $value, false );
            }
            else {
                $value = $file_manager->get_file_link( $value, true );
            }
        }

        return $value;

    }
}