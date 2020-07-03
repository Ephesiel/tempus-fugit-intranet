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