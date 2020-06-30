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
                    $allowed_fields[$slug] = new Field( $slug, $field['real_name'], $field['default'], $field['type'], $field['special_params'], $field['type'] == 'image' );
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
     * Return the value of the wanted field.
     * It will verify if the user can access to this field
     * Then, it will call user_db_datas and watch if the value exists on the array
     * If it isn't, return the default value
     * If the field is a file, return the url to display (images...) (or the path since 1.2.0)
     * 
     * @since 1.0.0
     * @since 1.2.0     Add $url param
     * @access public
     * 
     * @param string    $field_slug
     * @param string    $type           If the field is a file, you have multiple choice :
     *                                      - 'real_url' (default)  : give you the url of the file (to display an image for example)
     *                                      - 'absolute_path'       : give you the path for the file (usefull when move file)
     *                                      - 'upload_path'         : give you the path from inside the upload dir (this is the database value)
     * 
     * @return mixed    The value of the field
     * @return false    If not ok or if the field isn't allowed for this user
     */
    public function get_value_for_field( $field_slug, $type = 'real_url' ) {
        if ( ! $this->is_ok() || ! array_key_exists( $field_slug, $this->allowed_fields() ) ) {
            return false;
        }

        $field = $this->allowed_fields()[$field_slug];
        $value = '';
        
        if ( array_key_exists( $field_slug, $this->user_db_datas() ) ) {
            $value = $this->user_db_datas()[$field_slug];
        }
        
        if ( empty( $value ) ) {
            $value = $field->default_value;
        }

        if ( $field->is_file && ! empty( $value ) && $type !== 'upload_path' ) {
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

    /**
     * Send_new_datas.
     * 
     * Update user datas into the database
     * 
     * @since 1.0.0
     * @access public
     * @param array $datas datas to send
     * @param array $files all files to upload in the uploads folder. Default null
     * @return array errors or success on upload datas.
     * @return false if not ok or if datas sending failed
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
            if ( isset( $datas[$field->name] ) ) {
                $sanitation = false;
                switch ( $field->type ) {
                    case 'text':
                        $sanitation = $field_sanitizor->sanitize_text_field( $datas[$field->name] );
                    break;
                    case 'link':
                        $sanitation = $field_sanitizor->sanitize_link_field( $datas[$field->name], $field->special_params );
                    break;
                    case 'number':
                        $sanitation = $field_sanitizor->sanitize_number_field( $datas[$field->name], $field->special_params );
                    break;
                    case 'color':
                        $sanitation = $field_sanitizor->sanitize_color_field( $datas[$field->name] );
                    break;
                }

                if ( $sanitation === false ) {
                    $result[$field->name]['tfi-error'] = $field_sanitizor->last_error();
                }
                else if ( array_key_exists( $field->name, $this->user_db_datas() ) && $this->user_db_datas()[$field->name] === $sanitation ) {
                    $result[$field->name]['tfi-info'] = __( 'No change' );
                }
                else {
                    $changes[$field->name] = $sanitation;
                    $result[$field->name]['tfi-success']  = __( 'This field has been successfully changed' );
                }
            }
            else if ( isset( $files[$field->name] ) ) {
                if ( $files[$field->name]['error'] !== 4 ) {
                    $sanitation = false;
                    switch ( $field->type ) {
                        case 'image':
                            $sanitation = $field_sanitizor->sanitize_post_file_field( $field, $files[$field->name], $this->id );
                        break;
                    }
    
                    if ( $sanitation === false ) {
                        $result[$field->name]['tfi-error'] = $field_sanitizor->last_error();
                    }
                    else {
                        $changes[$field->name] = $sanitation;

                        $old_value = $this->get_value_for_field( $field->name, 'upload_path' );

                        if ( $old_value !== '' ) {
                            if ( ! $file_manager->remove_file( $old_value ) ) {
                                $result[$field->name]['tfi-error'] = __( 'The old image failed to removed' );
                            }
                        }

                        if ( ! $file_manager->upload_file( $files[$field->name]['tmp_name'], $sanitation ) ) {
                            $result[$field->name]['tfi-error'] = __( 'Impossible to upload the new image' );
                        }
                        else {
                            $result[$field->name]['tfi-success']  = __( 'This file has been uploaded with success' );
                        }
                    }
                }
                else {
                    $result[$field->name]['tfi-info'] = __( 'No file given' );
                }
            }
        }

        if ( $this->update_user_datas( $changes ) ) {
            return $result;
        }
        return false;
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
     * @access private
     * @return array
     * @return false if not ok
     * @global wpdb     $wpdb           The database object to drop the table
     */
    private function user_db_datas() {
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
        if ( ! empty( array_diff_assoc( $new_user_datas, $old_user_datas ) ) ) {
            global $wpdb;
            
            $db_result = $wpdb->update( $wpdb->prefix . TFI_TABLE, array( 'datas' => maybe_serialize( $new_user_datas ) ), array( 'user_id' => $this->id ), null, '%d' );

            if ( $db_result === false ) {
                return false;
            }

            // The datas changed
            $this->user_datas['user_db_datas'] = $new_user_datas;
        }

        /**
         * Even if datas didn't changed in database, it is possible that files changes for example.
         * So every datas send in this method will be send to the user_datas_changed hook
         */
        $changed_fields = array();
        $values = array();

        foreach ( $datas_to_changed as $field_name => $change ) {
            $changed_fields[$field_name]    = $this->allowed_fields()[$field_name];
            $values[$field_name]            = $this->get_value_for_field( $field_name );
        }

        /**
         * When user datas has been changed, a filter is applying to allow post process
         * 
         * @param int   $this->id           The id of the user whom datas has been modified
         * @param array $changed_fields     All Field objects which changed, keys are field_slug
         * @param array $values             Values to display in html or to know the exact link for certain fields
         * 
         * @since 1.2.0
         */
        apply_filters( 'tfi_user_datas_changed', $this->id, $changed_fields, $values );

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
 */
class Field {
    public $name;
    public $display_name;
    public $default_value;
    public $type;
    public $special_params;
    public $is_file;

    /**
     * Field constructor
     * 
     * @since 1.0.0
     * @param string $name              the field's slug 
     * @param string $display_name      pretty name displayed on html
     * @param string $default_value     the field's default
     * @param string $type              the field's type (link, image, text...)
     * @param array  $special_params    contains alla special params of a specific field
     * @param bool   $is_file           is the value a filename which points to the path of the file in the upload folder. Default false.
     */
    public function __construct( $name, $display_name, $default_value, $type, $special_params, $is_file = false ) {
        $this->name             = $name;
        $this->display_name     = $display_name;
        $this->default_value    = $default_value;
        $this->type             = $type;
        $this->special_params   = $special_params;
        $this->is_file          = $is_file;
    }
}