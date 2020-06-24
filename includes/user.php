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
     * @access private
     * 
     * @var int
     */
    private $id;

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
     * If the field is a file, return the url to display (images...)
     * 
     * @since 1.0.0
     * @access public
     * @param string $field_slug
     * @return string
     * @return false if not ok or if the field isn't allowed for this user
     */
    public function get_value_for_field( $field_slug ) {
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

        if ( $field->is_file ) {
            $value = $this->get_file_link( $value );
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
     * @global wpdb     $wpdb           The database object to drop the table
     */
    public function send_new_datas( $datas, $files = null ) {
        if ( ! $this->is_ok() ) {
            return false;
        }

        // We get the older datas (because it is possible to have cache data)
        // See AdminPanelmanager::update_users_datas to have more details
        $to_send = $this->user_db_datas();
        $witness = $to_send;
        $result = array();

        foreach ( $this->allowed_fields() as $field ) {
            if ( isset( $datas[$field->name] ) || isset( $files[$field->name] )  ) {
                switch ($field->type) {
                    case 'text':
                        $sanitize = filter_var( stripslashes( $datas[$field->name] ), FILTER_SANITIZE_STRING );
                        if ( ! empty( $sanitize ) ) {
                            $to_send[$field->name] = $sanitize;
                            $result[$field->name]  = true;
                        }
                        else {
                            $result[$field->name]['tfi-error'][] = __( 'This value cannot be sanitized as a text' );
                        }
                    break;
                    case 'link':
                        $sanitize = esc_url( filter_var( stripslashes( $datas[$field->name] ), FILTER_SANITIZE_STRING ) );
                        if ( ! empty( $sanitize ) ) {
                            $success = true;

                            if ( isset( $field->special_params['mandatory_domains'] ) && ! empty( $field->special_params['mandatory_domains'] ) ) {
                                $domains = $field->special_params['mandatory_domains'];
                                $domain = parse_url( $sanitize, PHP_URL_HOST );
                                $success = in_array( $domain, $domains ) || ( substr( $domain, 0, 4 ) === 'www.' && in_array( substr( $domain, 4 ), $domains ) );

                                if ( ! $success ) {
                                    $result[$field->name]['tfi-error'][] = sprintf( __( 'The hostname isn\'t in the mandatory names, please enter a link in one of those domains: %s' ), implode( ',', $field->special_params['mandatory_domains'] ) );
                                }
                            }

                            if ( $success ) {
                                $to_send[$field->name] = $sanitize;
                                $result[$field->name]  = true;
                            }
                        }
                        else {
                            $result[$field->name]['tfi-error'][] = __( 'This value cannot be sanitized as a link' );
                        }
                    break;
                    case 'image':
                        if ( isset ( $files[$field->name] ) ) {
                            $file_result = $this->upload_file( $field, $files[$field->name] );
                            if ( is_string( $file_result ) ) {
                                $to_send[$field->name] = $file_result;
                                $result[$field->name]  = true;
                            }
                            else {
                                // The result is an array of errors
                                $result[$field->name]  = $file_result;
                            }
                        }
                        else {
                            $result[$field->name]['tfi-info'][] = __( 'No file given' );
                        }
                    break;
                }
            }
        }

        if ( ! empty( array_diff_assoc( $to_send, $witness ) ) ) {
            global $wpdb;
            
            $db_result = $wpdb->update( $wpdb->prefix . TFI_TABLE, array( 'datas' => maybe_serialize( $to_send ) ), array( 'user_id' => $this->id ), null, '%d' );

            if ( $db_result === false ) {
                return false;
            }

            // The datas changed
            $this->user_datas['user_db_datas'] = $to_send;
        }

        return $result;
    }

    /**
     * Get_file_link.
     * 
     * Rerurn the full url of a given path
     * 
     * @since 1.0.0
     * @param string $value file path stored in the database
     * @return string the url of the file
     */
    private function get_file_link( $value ) {
        if ( defined( 'TFI_UPLOAD_FOLDER_URL' ) && ! empty( $value ) ) {
            return TFI_UPLOAD_FOLDER_URL . '/' . $value;
        }
        return $value;
    }

    /**
     * Upload_file.
     * 
     * Check file content and then add it to the upload folder
     * 
     * @since 1.0.0
     * @param Field $field the specific field for the file
     * @param array $file the file to save in the upload dir. Keys should be the same than $_FILES (see in documentation)
     * @return string the path name to be able to store it in the database
     * @return array of errors if one occured
     * @throws \Exception if there is a fatal error on upload
     */
    private function upload_file( $field, $file ) {
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            switch ( $file['error'] ) {
                case UPLOAD_ERR_INI_SIZE :
                    return array( 'tfi-error' => array( __( 'Sorry the file is too big' ) ) );
                case UPLOAD_ERR_FORM_SIZE :
                    return array( 'tfi-error' => array( __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form' ) ) );
                case UPLOAD_ERR_PARTIAL :
                    return array( 'tfi-error' => array( __( 'The uploaded file was only partially uploaded' ) ) );
                case UPLOAD_ERR_NO_FILE :
                    return array( 'tfi-info' => array( __( 'No file given' ) ) );
                case UPLOAD_ERR_NO_TMP_DIR :
                    return array( 'tfi-error' => array( __( 'Missing a temporary folder' ) ) );
                case UPLOAD_ERR_CANT_WRITE :
                    return array( 'tfi-error' => array( __( 'Failed to write file to disk.' ) ) );
                case UPLOAD_ERR_EXTENSION :
                    return array( 'tfi-error' => array( __( 'A PHP extension stopped the file upload.' ) ) );
                default:
                    return array( 'tfi-error' => array( __( 'An unknown upload error occured' ) ) );
            }
        }

        $extension = '';

        // Check MIME Type
        $finfo = new \finfo( FILEINFO_MIME_TYPE );

        if ( $field->type === 'image' ) {
            $extension = array_search(
                $finfo->file( $file['tmp_name'] ),
                array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                )
            );
    
            if ( $extension === false ) {
                return array( 'tfi-error' => array( __( 'This value should be an image, please give a .png, .jpeg or .gif file' ) ) );
            }
    
            // resize the image
            if ( isset( $field->special_params['width'] ) && isset( $field->special_params['height'] ) ) {
                try {
                    if ( $extension != 'gif' ) {
                        require_once TFI_PATH . 'utilities/resize-image.php';

                        $resize_image = new ResizeImage( $file['tmp_name'] );
                        $resize_image->resize_to(  $field->special_params['width'],  $field->special_params['height'] );
                        $resize_image->save_image( $file['tmp_name'] );
                    }
                    else {
                        require_once TFI_PATH . 'utilities/resize-gif.php';

                        $resize_gif = new ResizeGif( $file['tmp_name'] );
                        $resize_gif->resize_to(  $field->special_params['width'],  $field->special_params['height'] );
                        $resize_gif->save_image( $file['tmp_name'] );
                    }
                }
                catch ( \Exception $e) {
                    throw new \Exception( sprintf( __( 'This error occured when the image %1$s resized: %2$s' ), $field->display_name, $e->getMessage() ) );
                }
            }
        }

        $user = get_user_by( 'id', $this->id );
        
        if ( $user === false ) {
            wp_die( __( 'Fatal error: you\'re not a register user' ) );
        }

        $upload_dir = wp_upload_dir();

        if ( ! defined( 'TFI_UPLOAD_FOLDER_DIR' ) ) {
            throw new \Exception( 'Impossible to find the upload directory.' );
        }

        // The id is used to be sure that the dirname is unique.
        $user_dirname   = $user->user_nicename . '-' . $user->ID;
        $dirname        = TFI_UPLOAD_FOLDER_DIR . '/' . $user_dirname;

        if ( ! file_exists( $dirname ) ) {
            wp_mkdir_p( $dirname );
        }

        // Delete every files with the same name (because the extension can change and we don't want 3 different files)
        foreach ( glob( $dirname . '/' . $field->name . '.*' ) as $filename ) {
            unlink( $filename ); 
        }

        $filename   = $field->name . '.' . $extension;
        $result     = move_uploaded_file ( $file['tmp_name'], $dirname . '/' . $filename );

        if ( $result === false ) {
            throw new \Exception( 'Impossible to write the file.' );
        }

        // Store the path name (without the plugin directory to be able to reuse the path if we change it)
        return $user_dirname . '/' . $filename;
    }

    /**
     * User_db_datas.
     * 
     * Return the user datas from database.
     * Those datas are stored in the database so one call will be done only if needed
     * If user_datas have already be called, just return the $user_datas class attribute
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