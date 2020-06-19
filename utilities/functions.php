<?php

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