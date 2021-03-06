<?php

if ( ! function_exists( 'tfi_get_users' ) ) {
    /**
     * Tfi_get_users.
     * 
     * Get all wordpress users which have a specific tfi_user type.
     * If no $user_type set, return all tfi users
     * 
     * @since 1.1.0
     * @param string $user_type The wanted user type. Default = null
     * @return array            All wordpress users which match with the specific type.
     */
    function tfi_get_users( $user_type = null ) {
        $users = array();
        foreach( get_option( 'tfi_users' ) as $user_id => $user_datas ) {
            if ( $user_type === null ) {
                $users[] = get_user_by( 'id', $user_id );
            }
            else if ( isset( $user_datas['user_type'] ) && $user_datas['user_type'] == $user_type ) {
                $users[] = get_user_by( 'id', $user_id );
            }
        }
        return $users;
    }
}

if ( ! function_exists( 'tfi_get_users_which_have_field' ) ) {
    /**
     * Tfi_get_users_which_have_field.
     * 
     * Get all wordpress users which are allowed to use a specific field.
     * 
     * @since 1.1.1
     * @param string $field_slug    The wanted field.
     * @return array                All wordpress users which have the specific field.
     */
    function tfi_get_users_which_have_field( $field_slug ) {
        require_once TFI_PATH . '/includes/user.php';

        $users = array();
        foreach ( tfi_get_users() as $user ) {
            $user_obj = new TFI\User( $user->ID );
            if ( array_key_exists( $field_slug, $user_obj->allowed_fields() ) ) {
                $users[] = $user;
            }
        }

        return $users;
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
            case 'tfi_file_folders':
            case 'tfi_field_types':
            case 'tfi_user_types':
                $default = array();
            break;
            case 'tfi_user_page':
                $default = -1;
            break;
            case 'tfi_fields_version':
                $default = 0;
            break;
            case 'tfi_shortcut':
                $default = array(
                    'ctrl_key_used' => false,
                    'alt_key_used' => false,
                    'shift_key_used' => false,
                    'key' => 0
                );
            break;
            case 'tfi_plugins':
                $default = array(
                    'echo' => false,
                    'parallax' => false
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

            /**
             * Verification of the file, because if the '/' isn't set at the end of the first target,
             * the folder is also inside $files and will be deleted twice, generating a warning
             */
            if ( file_exists( $target ) ) {
                rmdir( $target );
            }
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
     * @since 1.2.2     Add recursion for array of files
     * 
     * @param array     $file_post  The array of files given by $_FILES global variable
     * @return array                The same array but with reformated 
     */
    function tfi_re_array_files( &$file_post ) {
        $file_ary = array();
        $file_keys = array_keys( $file_post );

        foreach ( $file_post['name'] as $file_name => $file_value ) {
            foreach ( $file_keys as $key ) {
                $file_ary[$file_name][$key] = $file_post[$key][$file_name];
            }
        }
        foreach ( $file_ary as $file_name => $values ) {
            if ( is_array( $values['name'] ) ) {
                $file_ary[$file_name] = tfi_re_array_files( $values );
            }
        }

        return $file_ary;
    }
}

if ( ! function_exists( 'tfi_is_valid_domain_name' ) ) {
    /**
     * Tfi_is_valid_domain_name.
     * 
     * Verify that a string is a valid domain name
     * 
     * @since 1.1.0
     * @param string $domain_name   The domain name to verify
     * @return bool                 If $domain_name is a valid domain name
     * @author velcrow https://stackoverflow.com/questions/1755144/how-to-validate-domain-name-in-php
     */
    function tfi_is_valid_domain_name( $domain_name ) {
        return ( preg_match( "/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name ) // valid chars check
              && preg_match( "/^.{1,253}$/", $domain_name )                                         // overall length check
              && preg_match( "/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name ) );                   // length of each label
    }
}

if ( ! function_exists( 'tfi_add_temp_folder' ) ) {
    /**
     * Tfi_add_temp_folder.
     * 
     * Add a new temporary folder
     * 
     * @since 1.1.5
     * @since 1.3.0     Add the possibility to create a temp folder with a specific name
     * 
     * @param string    $temp_folder_name   The name of the folder to create, default is empty and will create a random folder name
     * @return string                       The new temporary folder path
     */
    function tfi_add_temp_folder( $temp_folder_name = '' ) {
        if ( ! empty( $temp_folder_name ) ) {
            if ( ! file_exists( $temp_folder_name ) ) {
                wp_mkdir_p( $temp_folder_name );
            }
        }
        else {
            do {
                $temp_folder_name = TFI_TEMP_PATH . bin2hex( random_bytes( 8 ) );
            } while ( file_exists( $temp_folder_name ) );

            wp_mkdir_p( $temp_folder_name );
        }

        return $temp_folder_name . '/';
    }
}

if ( ! function_exists( 'tfi_remove_temp_folder' ) ) {
    /**
     * Tfi_remove_temp_folder.
     * 
     * Remove a temporary folder add inside the tmp/ directory
     * This function must be called after tfi_add_temp_folder
     * 
     * @since 1.1.5
     * @param string $temp_folder_path  The path for the temporary folder given in tfi_add_temp_folder
     */
    function tfi_remove_temp_folder( $temp_folder_path ) {
        if ( strpos( $temp_folder_path, TFI_TEMP_PATH ) === 0 ) {
            tfi_delete_files( $temp_folder_path );
        }
    }
}

if ( ! function_exists( 'tfi_get_user_file_folder_path' ) ) {
    /**
     * Tfi_get_user_file_folder_path.
     * 
     * This method allows to get a user file folder path by giving an id and a slug.
     * Return false if this is not possible to get the path of the wanted slug/user
     * 
     * @since 1.2.0
     * 
     * @param int           $user_id        The id of the user.
     * @param string|null   $folder_slug    The slug of the folder to get. If you only want the user path, give null. Default null.
     * @param bool          $absolute       If set to false, give the path form TFI_UPLOAD_FOLDER_DIR, if set to true, return an absolute path. Default true.
     * 
     * @return string       The wanted path (remind to check if the folder exists!)
     * @return false        When an error occured (bad user_id, non existing slug...) and the path cannot be given
     */
    function tfi_get_user_file_folder_path( $user_id, $folder_slug = null, $absolute = true ) {
        $user = get_user_by( 'id', $user_id );
        if ( $user === false ) {
            return false;
        }

        $subdirs = '';

        if ( $folder_slug !== null ) {
            require_once TFI_PATH . 'includes/options.php';

            $subdir         = $folder_slug;
            $all_folders    = tfi_get_option( 'tfi_file_folders' );
            $parent_folder  = TFI\OptionsManager::get_parent_file_folder_slug();
            
            // We only need to verify the first, because options are sanitized
            if ( ! array_key_exists( $subdir, $all_folders ) ) {
                return false;
            }
    
            while ( $subdir != $parent_folder ) {
                $subdirs = '/' . $subdir . $subdirs;
                $subdir = $all_folders[$subdir]['parent'];
            }
        }

        // The id is used to be sure that the dirname is unique.
        $user_dirname = $user->user_nicename . '-' . $user->ID . $subdirs;

        if ( $absolute ) {
            if ( ! defined( 'TFI_UPLOAD_FOLDER_DIR' ) ) {
                return false;
            }

            return  TFI_UPLOAD_FOLDER_DIR . '/' . $user_dirname;
        }
        else {
            return $user_dirname;
        }
    }
}

if ( ! function_exists( 'tfi_array_merge_recursive_ex' ) ) {
    /**
     * Tfi_array_merge_recursive_ex.
     * 
     * Merge 2 arrays but excludes multiple keys
     * 
     * @since 1.2.2
     * 
     * @param array         $array1         The first array to merge.
     * @param array         $array2         The second array to merge.
     * 
     * @return array        Merged array
     * @author              Mark.Ablov https://stackoverflow.com/questions/25712099/php-multidimensional-array-merge-recursive
     */
    function tfi_array_merge_recursive_ex(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => & $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = tfi_array_merge_recursive_ex($merged[$key], $value);
            } else if (is_numeric($key)) {
                if (!in_array($value, $merged)) {
                    $merged[] = $value;
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}

if ( ! function_exists( 'tfi_recursive_unset' ) ) {
    /**
     * Tfi_recursive_unset.
     * 
     * Unset every unwanted_key of an arry recursively 
     * 
     * @since 1.2.2
     * 
     * @param array         $array          Reference of the array where the key need to be removed.
     * @param array         $unwanted_key   The wanted key remove.
     * 
     * @author              soulmerge https://stackoverflow.com/questions/1708860/php-recursively-unset-array-keys-if-match
     */
    function tfi_recursive_unset( &$array, $unwanted_key ) {
        unset( $array[$unwanted_key] );
        foreach ( $array as &$value ) {
            if ( is_array( $value ) ) {
                tfi_recursive_unset( $value, $unwanted_key );
            }
        }
    }
}