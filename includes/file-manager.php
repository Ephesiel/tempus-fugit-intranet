<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage everything for files about a specific user.
 * It's used to import a file into the upload folder.
 *
 * @since 1.2.2
 */
class FileManager {
    /**
     * Upload_file.
     * 
     * Move a file from a position to another, inside tfi upload dir.
     * It is mainly use to move temporary files on post datas.
     * 
     * @since 1.2.2
     * @access public
     * 
     * @param string    $filename       The actual path to the file to move
     * @param string    $new_filename   The filename where we want to move the file (it will be done inside the upload directory)
     * 
     * @return bool     Success of the operation
     */
    public function upload_file( $filename, $new_filename ) {
        if ( ! defined( 'TFI_UPLOAD_FOLDER_DIR' ) ) {
            return false;
        }

        $new_filename   = TFI_UPLOAD_FOLDER_DIR . '/' . $new_filename;
        $dirname        = dirname( $new_filename );

        if ( ! file_exists( $dirname ) ) {
            wp_mkdir_p( $dirname );
        }

        return move_uploaded_file( $filename, $new_filename );
    }

    /**
     * Remove_file.
     * 
     * Remove a file from the tfi upload dir
     * 
     * @since 1.2.2
     * @access public
     * 
     * @param string    $filename   The filename where we want to remove the file (it should be a file which exists inside the upload directory)
     * @return bool                 Success of the operation
     */
    public function remove_file( $filename ) {
        $filename = TFI_UPLOAD_FOLDER_DIR . '/' . $filename;

        if ( file_exists( $filename ) && ! is_dir( $filename ) ) {
            return unlink( $filename );
        }

        return false;
    }

    /**
     * Remove_directory.
     * 
     * Remove a directory from the tfi upload dir
     * 
     * @since 1.3.0
     * @access public
     * 
     * @param string    $dirname   The dirname where we want to remove the file (it should be a directory which exists inside the upload directory)
     */
    public function remove_directory( $dirname ) {
        tfi_delete_files( TFI_UPLOAD_FOLDER_DIR . '/' . $dirname );
    }

    /**
     * Get_file_link.
     * 
     * Return the full url of a given path
     * There is no obligation that the file exists
     * 
     * @since 1.0.0
     * @since 1.2.0     Add $url param
     * @since 1.2.2     Move from User to FileManager
     * @access public
     * 
     * @param string    $value  File path stored in the database
     * @param bool      $url    Do you want the url or the directory link ?
     * 
     * @return string the url (or dir) of the file
     */
    public function get_file_link( $value, $url ) {
        if ( $url && defined( 'TFI_UPLOAD_FOLDER_URL' ) && ! empty( $value ) ) {
            return TFI_UPLOAD_FOLDER_URL . '/' . $value;
        }
        if ( ! $url && defined( 'TFI_UPLOAD_FOLDER_DIR' ) && ! empty( $value ) ) {
            return TFI_UPLOAD_FOLDER_DIR . '/' . $value;
        }
        return $value;
    }
}