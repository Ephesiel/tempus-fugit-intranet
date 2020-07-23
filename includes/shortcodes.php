<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manager of the plugin shortcodes
 *
 * @since 1.0.0
 */
class ShortcodesManager {

    /**
     * User.
     * 
     * Current user
     * 
     * @since 1.0.0
     * @access private
     * 
     * @var User
     */
    private $user;

    /**
     * Database_result.
     * 
     * When the user has send his datas
     * Store every errors or success
     * 
     * @since 1.0.0
     * @access private
     * 
     * @var array|null
     */
    private $database_result;

    /**
     * Fatal_error.
     * 
     * Contain the fatal error return by the database
     * This value is null if no fatal error
     * 
     * @since 1.1.5
     * @access private
     * 
     * @var string|null
     */
    private $fatal_error;

    /**
     * Different_fields_version.
     * 
     * Set to true if fields have been updated, and post datas are still here
     * 
     * @since 1.2.3
     * @access private
     * 
     * @var true|null
     */
    private $different_fields_version;

	/**
	 * ShortcodesManager constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 */
    public function __construct() {
        add_action( 'init', array( $this, 'shortcodes_init' ) );
        add_action( 'init', array( $this, 'user_init' ) );
    }

	/**
	 * Shortcodes_init.
     * 
     * Initiation of all shortcodes used by the plugin
	 *
	 * @since 1.0.0
	 * @access public
	 */
    public function shortcodes_init() {
        add_shortcode( 'tfi_user_form', array( $this, 'display_user_form' ) );
        add_shortcode( 'tfi_user_data', array( $this, 'get_user_data' ) );
    }

	/**
	 * User_init.
     * 
     * Initiation of the current user object
	 *
	 * @since 1.0.0
	 * @access public
	 */
    public function user_init() {
        require_once TFI_PATH . 'includes/user.php';

        $this->user = new User( get_current_user_id() );

        $posts = array_key_exists( 'tfi_update_user', $_POST ) ? $_POST['tfi_update_user'] : array();
        $files = array_key_exists( 'tfi_update_user', $_FILES ) ? tfi_re_array_files( $_FILES['tfi_update_user'] ) : array();

        /**
         * Deletion of all number_to_replace keys which are hidden rows
         */
        tfi_recursive_unset( $posts, 'number_to_replace' );
        tfi_recursive_unset( $files, 'number_to_replace' );

        /**
         * Once the user is created, and if we have a tfi_update_user key on post datas
         * It means that a form has been send with new datas
         * 
         * Those datas will be send in the database.
         */
        if ( $this->user->is_ok() && ( ! empty( $posts ) || ! empty( $files ) ) ) {
            if ( array_key_exists( 'tfi_fields_version', $_POST ) && $_POST['tfi_fields_version'] != tfi_get_option( 'tfi_fields_version' ) ) {
                $this->different_fields_version = true;
            }
            else {
                try {
                    $this->database_result = $this->user->send_new_datas( $posts, $files );
                }
                catch( \Exception $e ) {
                    $this->fatal_error = $e->getMessage();
                }
            }
        }
    }

    /**
     * Display_user_form.
     * 
     * This method is called when using the [tfi_user_form] shortcode
     * It display the form with all fields concerning the actual user
     * 
     * Attributes :
     *      -   preview => bool     => If you want to have a previzualisation of all fields
     *      -   fields  => string   => Fields to display in the form, separate by comma.
     *      -   prefixs => string   => Prefixs separate by comma. all fields which begin by one of those prefix will be display on the form.
     *      -   suffixs => string   => Same ad 'prefixs' but with suffixs
     *      -   not-*   => string   => fields, prefixs or suffixs, will not take fields with those values
     * 
     * If none of the three last arguments are set, all fields will be displayed
     * 
     * @since 1.0.0
     * @since 1.2.0     Add arguments to display special fields
     * @access public
     */
    public function display_user_form( $atts = array(), $content = null, $tag = '' ) {
        $atts = array_change_key_case( (array)$atts, CASE_LOWER );
        $output = '';

        if ( ! $this->user->is_ok() ) {
            return $this->get_error( 'not_register' );
        }
        if ( $this->different_fields_version === true ) {
            $output .= $this->get_error( 'different_fields_version' );
        }
        if ( $this->database_result === false ) {
            $output .= $this->get_error( 'database_problem' );
        }
        if ( $this->fatal_error !== null ) {
            $output .= $this->get_error( 'fatal' );
        }

        $user_fields = $this->user->allowed_fields();

        if ( array_key_exists( 'fields', $atts ) || array_key_exists( 'prefixs', $atts ) || array_key_exists( 'suffixs', $atts ) ) {
            $fields = array_key_exists( 'fields', $atts ) ? explode( ',', $atts['fields'] ) : array();
            $prefixs = array_key_exists( 'prefixs', $atts ) ? explode( ',', $atts['prefixs'] ) : array();
            $suffixs = array_key_exists( 'suffixs', $atts ) ? explode( ',', $atts['suffixs'] ) : array();
            $user_fields = $this->change_fields( $user_fields, $fields, $prefixs, $suffixs );
        }

        if ( array_key_exists( 'not-fields', $atts ) || array_key_exists( 'not-prefixs', $atts ) || array_key_exists( 'not-suffixs', $atts ) ) {
            $fields = array_key_exists( 'not-fields', $atts ) ? explode( ',', $atts['not-fields'] ) : array();
            $prefixs = array_key_exists( 'not-prefixs', $atts ) ? explode( ',', $atts['not-prefixs'] ) : array();
            $suffixs = array_key_exists( 'not-suffixs', $atts ) ? explode( ',', $atts['not-suffixs'] ) : array();
            $fields_to_remove = $this->change_fields( $user_fields, $fields, $prefixs, $suffixs );

            $user_fields = array_diff_key( $user_fields, $fields_to_remove );
        }

        $output .= '<form class="tfi-user-form" action="' . esc_attr( get_permalink( get_the_ID() ) ) . '" enctype="multipart/form-data" method="POST">';
        $output .=      '<table class="form-table">';
        foreach ( $user_fields as $field ) {
            if ( $field->is_multiple() ) {
                $output .= $this->add_multiple_field( $field, $atts );
            }
            else {
                $output .= $this->add_field( $field, $atts );
            }
        }
        $output .=          '<tr><td><input type="submit" id="submit" class="submit-button" value="' . esc_attr__( 'Register modifications' ) . '" /></td></tr>';
        $output .=      '</table>';
        $output .=      '<input type="hidden" name="tfi_fields_version" value="' . esc_attr__( tfi_get_option( 'tfi_fields_version' ) ) . '" />';
        $output .= '</form>';

        return $output;
    }

    /**
     * Change_fields.
     * 
     * Factorisation to do this verification on tfi_user_form shotcode with normal and not-* attributes.
     * 
     * @since 1.2.2
     * @access private
     */
    private function change_fields( $fields_to_look_at, $required_fields, $prefixs, $suffixs ) {
        $to_return = array();

        foreach ( $fields_to_look_at as $field_slug => $field_value ) {
            if ( in_array( $field_slug, $required_fields ) ) {
                $to_return[$field_slug] = $field_value;
                continue;
            }
            foreach ( $prefixs as $prefix ) {
                if ( strpos( $field_slug, $prefix ) === 0 ) {
                    $to_return[$field_slug] = $field_value;
                    break;
                }
            }
            foreach ( $suffixs as $suffix ) {
                if ( strrpos( $field_slug, $suffix ) === strlen( $field_slug ) - strlen( $suffix ) ) {
                    $to_return[$field_slug] = $field_value;
                    break;
                }
            }
        }

        return $to_return;
    }

    /**
     * Get_user_data.
     * 
     * This method is called when using the [tfi_user_data] shortcode
     * It return the value of the wanted field
     * 
     * Attributes :
     *      -   user_id     => int      => the id of the wanted user
     *      OR  user_slug   => string   => the slug of the wanted user
     *      OR  null                    => the user will be the current user
     *      -   field       => string   => the slug of the wanted field (mandatory)
     * 
     * @since 1.0.0
     * @access public
     */
    public function get_user_data( $atts = array(), $content = null, $tag = '' ) {
        $atts = array_change_key_case( (array)$atts, CASE_LOWER );
        $output = '';
        $user   = false;
        $field  = false;

        if ( array_key_exists( 'user_id', $atts ) ) {
            $user = get_user_by( 'id', $atts['user_id'] );
        }
        else if ( array_key_exists( 'user_slug', $atts ) ) {
            $user = get_user_by( 'slug', $atts['user_slug'] );
        }
        else {
            $user = wp_get_current_user();
        }

        if ( array_key_exists( 'field', $atts ) ) {
            $field = $atts['field'];
        }

        if ( $user !== false && $field !== false ) {
            $user = new user( $user->ID );
            $output = $user->get_value_for_field( $field );
        }

        return $output;
    }

    private function add_echo_template_form() {
        $campains = $this->user->get_echo_campains();
        $templates = $this->user->get_echo_templates();

        $o = '<table class="form-table">';
        $o.=    '<tr>';
        $o.=        '<th>';
        $o.=            '<label for="echo_template_select">';
        $o.=                esc_html__( 'Echo template selection' );
        $o.=            '</label>';
        $o.=        '</th>';
        $o.=        '<td>';
        $o.=            '<form class="tfi-user-form form-inline" action="' . esc_attr( get_permalink( get_the_ID() ) ) . '" method="GET">';
        $o.=                '<select onchange="this.form.submit()" id="echo_template_select" name="tfi_update_echo_template">';
        foreach ( $templates as $template ) {
        $o.=                    '<option value="' . $template->id . '" ' . ( $template->id === $this->user->get_current_echo_template()->id ? ' selected' : '' ) . '>' . $template->name . '</option>';
        }
        $o.=                '</select>';
        $o.=                '<input onclick="document.getElementById(\'echo_template_select\').setAttribute(\'name\', \'tfi_delete_echo_template\'); this.form.submit()" type="button" class="submit-button" value="' . esc_attr__( 'Delete it' ) . '" />';
        $o.=            '</form>';
        $o.=        '</td>';
        $o.=    '</tr>';
        $o.=    '<tr>';
        $o.=        '<th>';
        $o.=            '<label for="new_echo_template">';
        $o.=                esc_html__( 'New echo template' );
        $o.=            '</label>';
        $o.=        '</th>';
        $o.=        '<td>';
        $o.=            '<form class="tfi-user-form form-inline" action="' . esc_attr( get_permalink( get_the_ID() ) ) . '" method="GET">';
        $o.=                '<input type="text" placeholder="' . esc_attr__( 'my_new_template' ) . '" id="new_echo_template" name="tfi_new_echo_template">';
        $o.=                '<label for="echo_campain_select">';
        $o.=                    esc_html__( 'Campain:' );
        $o.=                '</label>';
        $o.=                '<select onchange="this.form.submit()" id="echo_campain_select" name="tfi_echo_campain">';
        foreach ( $campains as $campain ) {
        $o.=                    '<option>' . $campain . '</option>';
        }
        $o.=                '</select>';
        $o.=                '<input type="submit" class="submit-button" value="' . esc_attr__( 'Add template' ) . '" />';
        $o.=            '</form>';
        $o.=        '</td>';
        $o.=    '</tr>';
        $o.= '</table>';
        $o.= '<hr />';

        return $o;
    }

    /**
     * Add_field
     * 
     * It will add a row for one specific field.
     * Remember that every field type such as text, link etc... should have their own method.
     * Those method should be called add_field_{FIELD_NAME}
     * 
     * @since 1.0.0
     * @access private
     * 
     * @param   Field   $field  The field to display
     * @param   array   $atts   Attributes send by the shortcode. Default null.
     * @return  string          The html content for the field
     */
    private function add_field( $field, $atts = null ) {
        if ( ! method_exists( $this, 'add_field_' . $field->type ) ) {
            return ''; 
        }

        $callback           = array( $this, 'add_field_' . $field->type );
        $field_form_name    = 'tfi_update_user[' . $field->name . ']';

        $o = '<tr>';
        $o.=    '<th scope="row">';
        $o.=        '<label for="' . esc_attr( $field->name ) . '">';
        $o.=            esc_html__( $field->display_name );
        $o.=        '</label>';
        $o.=    '</th>';
        $o.=    '<td colspan="2">';
        $o.=        call_user_func( $callback, $field, $field_form_name );
        $o.=        $this->get_database_message( $field );
        $o.=    '</td>';
        if ( isset( $atts['preview'] ) && $atts['preview'] && method_exists( $this, 'preview_field_' . $field->type ) ) {
        $o.=    '<td class="preview">';
        $o.=        call_user_func( array( $this, 'preview_field_' . $field->type ), $field );
        $o.=    '</td>';
        }
        $o.= '</tr>';

        return $o;
    }

    /**
     * Add_multiple_field.
     * 
     * Add all rows needed to display a multiple field.
     * This is a method surch as the previous but much more long, adapted for multiple field.
     * 
     * @since 1.2.2
     * @access private
     * 
     * @param   Field   $multiple_field     The field to display
     * @param   array   $atts               Attributes send by the shortcode. Default null.
     * @return  string                      The html content for the field
     */
    private function add_multiple_field( $multiple_field, $atts = null ) {
        if ( ! method_exists( $this, 'add_field_' . $multiple_field->special_params['type'] ) ) {
            return ''; 
        }
        $callback = array( $this, 'add_field_' . $multiple_field->special_params['type'] );

        /**
         * Number of field max and min
         */
        $max_length = $multiple_field->special_params['max_length'];
        $min_length = $multiple_field->special_params['min_length'];

        $messages       = isset( $this->database_result[$multiple_field->name] ) ? $this->database_result[$multiple_field->name] : array();
        $values         = $multiple_field->get_value_for_user( $this->user );
        $number_field   = $min_length;
        if ( count( $values ) > 0 ) {
            /**
             * Values are stored in 0, 1, 2 ... keys in the values' array.
             * We get the value according to the index, so, to be sure to have all values, we want at least last key number of row 
             * The last key is the highest, because rows are displayed in croissant order (so send like that in post datas)
             */
            $number_field = max( array_key_last( $values ) + 1, $number_field );
        }
        if ( ! empty( $messages ) ) {
            /**
             * When datas are sent, a message is get for every field.
             * if a field have an error, it will display it.
             * It avoid to hide bad values and allows user to see errors.
             */
            $number_field = max( count( $messages ), $number_field );
        }


        /**
         * Some class variables
         */
        $element_class          = 'multiple-field-' . $multiple_field->name;
        $remove_button_class    = 'remove-field-' . $multiple_field->name;
        $add_button_class       = 'add-field-' . $multiple_field->name;

        /**
         * Disable function to put on remove and add button
         */
        $disabled_buttons_function  = 'tfi_set_disable_button(\'' . $max_length . '\', \'' . $min_length . '\', \'' . $element_class . '\', \'' . $add_button_class . '\', \'' . $remove_button_class . '\' )';

        /**
         * Some attribute values
         */
        $id_to_clone    = 'default-col-' . $multiple_field->name;
        $id_suffix      = 'col-' . $multiple_field->name . '_';

        $o = '<tr class="multiple-field-first-row" data-max="' . $max_length . '" data-min="' . $min_length . '" element-class="' . $element_class . '" button-add-class="' . $add_button_class . '" button-remove-class="' . $remove_button_class . '">';
        $o.=    '<th scope="row">';
        $o.=        '<label>';
        $o.=            sprintf( $multiple_field->display_name . ' ' . esc_html__( '(min: %1$s, max: %2$s)' ), $min_length, $max_length != 0 ? $max_length : '&infin;' );
        $o.=        '</label>';
        $o.=    '</th>';
        $o.=    '<td>';
        $o.=        '<button';
        $o.=            ' onclick="tfi_add_row(\'' . $id_to_clone . '\', \'' . $id_suffix . '\', \'number_to_replace\'); ' . $disabled_buttons_function . '"';
        $o.=            ' class="multiple-field-button ' . $add_button_class . '"';
        $o.=            ' type="button">';
        $o.=                esc_html__( 'Add new value' );
        $o.=        '</button>';
        $o.=    '</td>';
        $o.= '</tr>';
        for ( $i = 0; $i <= $number_field; $i++ ) {
            $field = $multiple_field->get_field_for_index( $i );
            $field_form_name;
            if ( $i != $number_field ) {
                $field_form_name    = 'tfi_update_user[' . $multiple_field->name . '][' . $i . ']';
            }
            else {
                $field_form_name        = 'tfi_update_user[' . $multiple_field->name . '][number_to_replace]';
                $field->name            = $multiple_field->name . '_number_to_replace';
                $field->display_name    = $multiple_field->display_name . ' - number_to_replace';
            }

            if ( $i == $number_field ) {
            $o.= '<tr id="' . $id_to_clone . '" class="multiple-field ' . $element_class . '" hidden>';
            } else {
            $o.= '<tr id="' . $id_suffix . $i . '" class="multiple-field ' . $element_class . '">';
            }
            $o.=    '<td>';
            $o.=        '<label for="' . $field->name . '">';
            $o.=            $field->display_name;
            $o.=        '</label>';
            $o.=    '</td>';
            $o.=    '<td>';
            $o.=        call_user_func( $callback, $field, $field_form_name );
            if ( isset( $messages[$i] ) ) {
                foreach ( $messages[$i] as $message_type => $message ) {
                    $o .= $this->display_database_message( $message_type, $message );
                }
            }
            $o.=    '</td>';
            $o.=    '<td>';
            $o.=        '<button';
            $o.=            ' onclick="tfi_remove_row(\'col-' . $field->name . '\'); ' . $disabled_buttons_function . '"';
            $o.=            ' class="multiple-field-button ' . $remove_button_class . '"';
            $o.=            ' type="button">';
            $o.=                esc_html__( 'Remove value' );
            $o.=        '</button>';
            $o.=    '</td>';
            if ( isset( $atts['preview'] ) && $atts['preview'] && method_exists( $this, 'preview_field_' . $field->type ) ) {
            $o.=    '<td class="preview">';
            $o.=        call_user_func( array( $this, 'preview_field_' . $field->type ), $field );
            $o.=    '</td>';
            }
            $o.= '</tr>';
        }

        return $o;
    }

    private function add_field_link( $field, $field_form_name ) {
        $o = '<input';
        $o.=    ' type="text"';
        $o.=    ' id="' . esc_attr( $field->name ) .'"';
        $o.=    ' name="' . esc_attr( $field_form_name ) . '"';
        $o.=    ' value="' . esc_attr( $field->get_value_for_user( $this->user ) ) . '"';
        $o.=    ' placeholder="' . esc_attr( $field->default_value() ) . '"';
        $o.= ' />';

        return $o;
    }

    private function add_field_text( $field, $field_form_name ) {
        $o = '<input';
        $o.=    ' type="text"';
        $o.=    ' id="' . esc_attr( $field->name ) .'"';
        $o.=    ' name="' . esc_attr( $field_form_name ) . '"';
        $o.=    ' value="' . esc_attr( $field->get_value_for_user( $this->user ) ) . '"';
        $o.=    ' placeholder="' . esc_attr( $field->default_value() ) . '"';
        $o.= ' />';

        return $o;
    }

    private function add_field_image( $field, $field_form_name ) {
        $o = '<input';
        $o.=    ' type="file"';
        $o.=    ' id="' . esc_attr( $field->name ) .'"';
        $o.=    ' name="' . esc_attr( $field_form_name ) . '"';
        $o.= '/>';
        $o.= '<label for="' . esc_attr( $field->name ) . '">';
        $o.=    esc_html__( 'Click to open browser' );
        $o.= '</label>';

        return $o;
    }

    private function add_field_number( $field, $field_form_name ) {
        $o = '<input';
        $o.=    ' type="number"';
        $o.=    ' id="' . esc_attr( $field->name ) .'"';
        $o.=    ' name="' . esc_attr( $field_form_name ) . '"';
        $o.=    ' value="' . esc_attr( $field->get_value_for_user( $this->user ) ) . '"';
        if ( $field->special_params['min'] < $field->special_params['max'] ) {
        $o.=    ' min="' . esc_attr( $field->special_params['min'] ) . '"';
        $o.=    ' max="' . esc_attr( $field->special_params['max'] ) . '"';
        }
        $o.= '/>';

        return $o;
    }

    private function add_field_color( $field, $field_form_name ) {
        $o = '<input';
        $o.=    ' type="color"';
        $o.=    ' id="' . esc_attr( $field->name ) .'"';
        $o.=    ' name="' . esc_attr( $field_form_name ) . '"';
        $o.=    ' value="' . esc_attr( $field->get_value_for_user( $this->user ) ) . '"';
        $o.= '/>';

        return $o;
    }

    private function preview_field_image( $field ) {
        $src = $field->get_value_for_user( $this->user );

        if ( empty ( $src ) ) {
            return '';
        }

        $o = '<div class="preview-image">';
        $o.=    '<img height="100" src="' . esc_attr( $src ) . '"/>';
        $o.= '</div>';

        return $o;
    }

    private function preview_field_link( $field ) {
        $href = $field->get_value_for_user( $this->user );
        
        $o = '<div class="preview-link">';
        if ( ! empty ( $href ) ) {
        $o.=    '<a href="' . esc_attr( $href ) . '">' . sprintf( esc_html__( '%s link' ), $field->display_name ) . '</a>';
        } else {
        $o.=    '<p>' . esc_html__( 'No link set yet' ) . '</p>';
        }
        $o.= '</div>';

        return $o;
    }

    /**
     * Get_database_message.
     * 
     * Return an html content which allows to display database message get in init.
     * 
     * @since 1.0.0
     * @param Field $field the field to get message
     * @return string the html content to add
     */
    private function get_database_message( $field ) {
        $html = '';

        if ( isset( $this->database_result[$field->name] ) ) {
            foreach ( $this->database_result[$field->name] as $message_type => $message ) {
                $html .= $this->display_database_message( $message_type, $message );
            }
        }

        return $html;
    }

    private function display_database_message( $message_type, $message ) {
        $html = '<div class ="tfi-message ' . esc_attr( $message_type ) . '"><small>';
        $html.=     $message; 
        $html.= '</small></div>';

        return $html;
    }

    /**
     * Get_error.
     * 
     * Display an error in a big div
     * 
     * @since 1.0.0
     * @param string $callback the specific error method to call. Default ''.
     * @return string the html content of the error
     */
    private function get_error( $callback = '' ) {

        $e = '<div class="error">';
        $e.=    method_exists( $this, 'error_' . $callback ) ? call_user_func( array( $this, 'error_' . $callback ) ) : esc_html__( 'An error occured' );
        $e.= '</div>';

        return $e;
    }

    private function error_not_register() {
        $e = esc_html__( 'Sorry, you don\'t have any role yet.' ) . '<br />';
        $e.= esc_html__( 'Please wait that your administator register your account and try again.' );

        return $e;
    }

    private function error_database_problem() {
        /**
         * When you get this error, you can verify into database, maybe the user has been deleted
         * Indeed, the database do an update of an existing user, the user MUST exists in ddb-prefix_tfi_datas
         */
        $e = esc_html__( 'The database response failed for an unknown reason. Maybe you\'re not connected.' ) . '<br />';
        $e.= esc_html__( 'If you\'re connected it can be a database error and files can have been pushed.' ) . '<br />';
        $e.= esc_html__( 'Please refer to your administrator about this error.' );

        return $e;
    }

    private function error_fatal() {
        $e = '<b>' . esc_html( __( 'Fatal error:' ) ) . '</b> ' . esc_html( $this->fatal_error ) . '<br/>';
        $e.= esc_html__( 'A fatal error is an unexpected, unwanted error which is not due to your for most cases. Please try again and if the error still appear, contact your support.' ) . '<br />';
        $e.= esc_html__( 'You should consider that your datas are not saved.' );

        return $e;
    }

    private function error_different_fields_version() {
        $e = esc_html__( 'Some fields of your form have been changed by an administrator' ) . '<br />';
        $e.= esc_html__( 'Please re-submit the form or close this page to remove this message' );

        return $e;
    }
}