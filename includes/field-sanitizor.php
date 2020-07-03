<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize fields
 *
 * @since 1.2.2
 */
class FieldSanitizor {
    /**
     * Last_error.
     * 
     * Store the last error on a field value validation
     * 
     * @var string
     */
    private $last_error;

    /**
     * Last_error.
     * 
     * It is advice to call this method each time that a sanitize method return false.
     * It allows to see what was the problem
     * 
     * @since 1.2.2
     * @access public
     * 
     * @return string   The last error of a sanitize field.
     */
    public function last_error() {
        return $this->last_error;
    }

    /**
     * Sanitize_text_field.
     * 
     * @since 1.2.2
     * @access public
     * 
     * @param string    $text       The new value of a text field.
     * 
     * @return string   The text sanitized
     * @return false    If an error occured (call FieldSanitizor::last_error() to have more information about the error)
     */
    public function sanitize_text_field( $text ) {
        return stripslashes( filter_var( $text, FILTER_SANITIZE_STRING ) );
    }

    /**
     * Sanitize_link_field.
     * 
     * @since 1.2.2
     * @access public
     * 
     * @param string    $link               The new value of a link field.
     * @param array     $special_params     Special parameters for the field
     * 
     * @return string   The link sanitized
     * @return false    If an error occured (call FieldSanitizor::last_error() to have more information about the error)
     */
    public function sanitize_link_field( $link, $special_params ) {
        $sanitize = esc_url( filter_var( stripslashes( $link ), FILTER_SANITIZE_STRING ) );

        if ( ! empty( $sanitize ) ) {
            if ( isset( $special_params['mandatory_domains'] ) && ! empty( $special_params['mandatory_domains'] ) ) {
                $domains = $special_params['mandatory_domains'];
                $domain = parse_url( $sanitize, PHP_URL_HOST );

                if ( in_array( $domain, $domains ) || ( substr( $domain, 0, 4 ) === 'www.' && in_array( substr( $domain, 4 ), $domains ) ) ) {
                    return $sanitize;
                }

                $this->last_error = sprintf( __( 'The hostname isn\'t in the mandatory names, please enter a link in one of those domains: %s' ), implode( ', ', $domains ) );
                return false;
            }

            return $sanitize;
        }

        $this->last_error = __( 'This value cannot be sanitized as a link' );
        return false;
    }

    /**
     * Sanitize_number_field.
     * 
     * @since 1.2.2
     * @access public
     * 
     * @param string    $number             The new value of a number field.
     * @param array     $special_params     Special parameters for the field
     * 
     * @return string   The number sanitized
     * @return false    If an error occured (call FieldSanitizor::last_error() to have more information about the error)
     */
    public function sanitize_number_field( $number, $special_params ) {
        $sanitize = filter_var( $number, FILTER_SANITIZE_NUMBER_INT );

        if ( ! empty( $sanitize ) ) {
            if ( $special_params['min'] > $special_params['max'] || ( $sanitize >= $special_params['min'] && $sanitize <= $special_params['max'] ) ) {
                return $sanitize;
            }

            $this->last_error = sprintf( __( 'It should be >= %1$s and <= %2$s' ), $special_params['min'], $special_params['max'] );
            return false;
        }

        $this->last_error = __( 'This value cannot be sanitized as a number' );
        return false;
    }

    /**
     * Sanitize_color_field.
     * 
     * @since 1.2.2
     * @access public
     * 
     * @param string    $color      The new value of a color field.
     * 
     * @return string   The color sanitized
     * @return false    If an error occured (call FieldSanitizor::last_error() to have more information about the error)
     */
    public function sanitize_color_field( $color ) {
        $sanitize = '#' . substr( preg_replace( '/[^a-f0-9]/', '', $color ), 0, 6 );

        if ( strlen( $sanitize ) === 7 ) {
            return $sanitize;
        }

        $this->last_error = __( 'This value cannot be sanitized as a color' );
        return false;
    }

    /**
     * Sanitize_post_file_field.
     * 
     * Sanitize an image give in POST. It means an image get in $_FILES super global variable.
     * 
     * @since 1.2.2
     * @access public
     * 
     * @param Field     $field      The field which has a file type
     * @param array     $file       The new field value, this is a post data file (with name, type, tmp_name, error and size parameters)
     * @param int       $user_id    The id of the user, we need it to create the good filename for this user
     * 
     * @return string   The pathname for the sanitize file
     * @return false    If an error occured (call FieldSanitizor::last_error() to have more information about the error)
     */
    public function sanitize_post_file_field( $field, $file, $user_id ) {
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            switch ( $file['error'] ) {
                case UPLOAD_ERR_INI_SIZE :
                    $this->last_error = __( 'Sorry the file is too big' );
                case UPLOAD_ERR_FORM_SIZE :
                    $this->last_error = __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form' );
                case UPLOAD_ERR_PARTIAL :
                    $this->last_error = __( 'The uploaded file was only partially uploaded' );
                case UPLOAD_ERR_NO_FILE :
                    $this->last_error = __( 'No file given' );
                case UPLOAD_ERR_NO_TMP_DIR :
                    $this->last_error = __( 'Missing a temporary folder' );
                case UPLOAD_ERR_CANT_WRITE :
                    $this->last_error = __( 'Failed to write file to disk.' );
                case UPLOAD_ERR_EXTENSION :
                    $this->last_error = __( 'A PHP extension stopped the file upload.' );
                default:
                    $this->last_error = __( 'An unknown upload error occured' );
            }
            return false;
        }

        $extension = '';

        if ( $field->type === 'image' ) {
            $extension = $this->sanitize_image( $field, $file['tmp_name'] );
            if ( ! $extension ) {
                return false;
            }
        }
        
        $filepath   = tfi_get_user_file_folder_path( $user_id, $field->special_params['folder'], false );
        $filename   = $field->name . '.' . $extension;

        return $filepath . '/' . $filename;
    }

    /**
     * Sanitize_image.
     * 
     * Verify type and resize a field image value.
     * The file in $filename will be changed.
     * 
     * @since 1.2.2
     * @access private
     * 
     * @param Field     $field      The specific field which have type == 'image'
     * @param string    $filename   The path to get find the image. The image will be resize and override here.
     * 
     * @return string   The extension of the image
     * @return false    If an error occured (call FieldSanitizor::last_error() to have more information about the error)
     */
    private function sanitize_image( $field, $filename ) {
        /**
         * Check the mime type, it should be a .jpg, .png or .gif
         */
        $finfo = new \finfo( FILEINFO_MIME_TYPE );

        $extension = array_search(
            $finfo->file( $filename ),
            array(
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            )
        );

        if ( $extension === false ) {
            $this->last_error = __( 'This value should be an image, please give a .png, .jpeg or .gif file' );
            return false;
        }

        /**
         * Resize image according to it's extension. Each image of a gif are resized
         */
        if ( isset( $field->special_params['width'] ) && isset( $field->special_params['height'] ) ) {
            try {
                if ( $extension != 'gif' ) {
                    require_once TFI_PATH . 'utilities/resize-image.php';

                    $resize_image = new ResizeImage( $filename );
                    $resize_image->resize_to( $field->special_params['width'],  $field->special_params['height'] );
                    $resize_image->save_image( $filename );
                }
                else {
                    require_once TFI_PATH . 'utilities/resize-gif.php';

                    $resize_gif = new ResizeGif( $filename );
                    $resize_gif->resize_to( $field->special_params['width'],  $field->special_params['height'] );
                    $resize_gif->save_image( $filename );
                }
            }
            catch ( \Exception $e) {
                throw new \Exception( sprintf( __( 'This error occured when the image %1$s resized: %2$s' ), $field->display_name, $e->getMessage() ) );
            }
        }

        return $extension;
    }
}