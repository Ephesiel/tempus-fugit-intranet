<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class Field
 * 
 * Store a field and all its datas to use it easily
 * 
 * @since 1.0.0
 * @since 1.1.0 Add $special_params attribute
 * @since 1.2.2 Add $parent and $index attributes
 * @since 1.3.0 Handle echo field with templates 
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
     * @access public
     * 
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

    public function is_echo_field() {
        return strpos( $this->name, 'echo_' ) === 0;
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
        if ( ! $this->is_multiple() ) {
            return $this->default_value;
        }

        $result = array();

        if ( isset( $this->special_params['min_length'] ) ) {
            for ( $i = 0; $i < $this->special_params['min_length']; $i++ ) {
                $result[] = $this->get_field_for_index( $i )->default_value();
            }
        }

        return $result;
    }

    /**
     * Create_default_value.
     * 
     * Return the default value to be created
     * This differ than default_value() because it needs to add every echo field as an array with a default template value 
     * 
     * @since 1.3.0
     * @access public
     * 
     * @return mixed    The default value to insert in database
     */
    public function create_default_value() {
        $value = $this->default_value();

        if ( $this->is_echo_field() ) {
            $value = array( $value );
        }

        return $value;
    }

    /**
     * Get_folder_path_from_user.
     * 
     * @since 1.3.0
     * @access public
     * 
     * @param User  $user       The user to verify path from. If this is an echo field, it will get the user current template to add it to the path
     * @param bool  $absolute   If set to false, give the path form TFI_UPLOAD_FOLDER_DIR, if set to true, return an absolute path. Default true.
     * 
     * @return false If there is no folder for this field or if the folder can't be established
     * @return string The folder path for this field
     */
    public function get_folder_path_from_user( $user, $absolute = true ) {
        if ( ! isset( $this->special_params['folder'] ) ) {
            return false;
        }

        $folder_path = tfi_get_user_file_folder_path( $user->id, $this->special_params['folder'], $absolute );

        if ( $folder_path === false ) {
            return false;
        }

        /**
         * If this is a echo field, the template is had after the echo folder
         */
        if ( $this->is_echo_field() ) {
            $echo_path = tfi_get_user_file_folder_path( $user->id, 'echo', $absolute );

            if ( strpos( $folder_path, $echo_path . '/' ) === 0 || $folder_path === $echo_path ) {
                $folder_path = $echo_path . '/' . $user->get_current_echo_template()->campain . '/' . $user->get_current_echo_template()->pretty_id() . substr( $folder_path, strlen( $echo_path ) );
            }
        }

        return $folder_path;
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

        if ( $this->is_echo_field() ) {
            array_unshift( $indexes, $user->get_current_echo_template()->pretty_id() );
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