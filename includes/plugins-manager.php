<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Everything concerning plugins are here 
 *
 * @since 1.3.0
 */
class PluginsManager {

    /**
     * Plugins.
     * 
     * Contains all plugins in the plugin directory
     * 
     * @since 1.3.0
     * @access private
     * @static
     * 
     * @var array
     */
    private static $plugins;

    /**
     * Get_plugin.
     * 
     * Get a specific plugin if exists, else return false
     * 
     * @since 1.3.0
     * @access public
     * @static
     * 
     * @param string $plugin_name   The name of the plugin to get
     * @return SubPlugin            The asking plugin
     * @return false                If the asking plugin doesn't exist 
     */
    public static function get_plugin( $plugin_name ) {
        /**
         * We called this method first to be sure that plugins are well instantiate
         */
        self::instantiate_plugins();

        if ( isset( self::$plugins[$plugin_name] ) ) {
            return self::$plugins[$plugin_name];
        }
        return false;
    }

    /**
     * Instantiate_plugins.
     *
     * On plugin activation, it will search all plugins and update the option if needed
     * 
     * @since 1.3.0 
     * @access private
     * @static
     */
    public static function instantiate_plugins() {
        /**
         * Avoid to call this method multiple times, there is no use to do that
         */
        if ( self::$plugins !== null ) {
            return;
        }

        self::$plugins = array();

        /**
         * A sub plugin needs to be inside a directory
         */
        $plugins_directory_files    = glob( TFI_PLUGINS_FOLDER_PATH . '*', GLOB_ONLYDIR );
        $option_plugins             = array();
		$old_option_plugins         = tfi_get_option( 'tfi_plugins' );

        /**
         * Look at all directory inside the plugins directory
         */
        foreach ( $plugins_directory_files as $file ) {
            $plugin_name        = pathinfo( $file, PATHINFO_FILENAME );
            $plugin_main_file   = $file . '/' . $plugin_name . '.php';
            $uninstall_file     = $file . '/uninstall.php';

            /**
             * Verifying the existence of the main file
             */
            if ( ! file_exists( $plugin_main_file ) ) {
                continue;
            }

            /**
             * Verifying the existence of the uninstall file
             */
            if ( ! file_exists( $uninstall_file ) ) {
                $uninstall_file = null;
            }

            /**
             * Modify the option or set the basic value on false
             */
            $option_plugins[$plugin_name] = isset( $old_option_plugins[$plugin_name] ) ? $old_option_plugins[$plugin_name] : false;

            /**
             * The key is the plugin_name to access it easily
             */
            self::$plugins[$plugin_name] = new SubPlugin( $plugin_name, $option_plugins[$plugin_name], $plugin_main_file, $uninstall_file );
        }

        /**
         * If there is new plugins, we update the option.
         */
        if ( $option_plugins !== $old_option_plugins ) {
            update_option( 'tfi_plugins', $option_plugins );
        }
    }
}

/**
 * class SubPlugin
 * 
 * Allow to keep all information about a subplugin in one object
 * 
 * @since 1.3.0
 */
class SubPlugin {
    public $name;
    public $pretty_name;
    public $description;
    public $version;
    private $enable;
    private $main_file;
    private $uninstall_file;

    public function __construct( $name, $enable, $main_file, $uninstall_file = null ) {
        $this->name             = $name;
        $this->enable           = $enable;
        $this->main_file        = $main_file;
        $this->uninstall_file   = $uninstall_file;

        /**
         * Create variable according to the first comment of the main file
         */
        $this->read_first_comment();

        if ( $enable ) {
            $this->include_main_file();
        }
    }

    /**
     * Include_main_file.
     * 
     * Include the file for the plugin
     * 
     * @since 1.3.0
     * @access public
     */
    private function include_main_file() {
        include_once $this->main_file;
    }

    /**
     * Read_first_comment.
     * 
     * Read the first comment of the plugin to get datas
     * 
     * @since 1.3.0
     * @access private
     */
    private function read_first_comment() {
        $content = file_get_contents( $this->main_file );

        if ( $content === false ) {
            $this->get_comment_values();
            return;
        }

        $comment_begin = strpos( $content, '/**' );

        if ( $comment_begin === false ) {
            $this->get_comment_values();
            return;
        }

        $content = substr( $content, $comment_begin + 3 );
        $comment_end = strpos( $content, '*/' );

        if ( $comment_end === false ) {
            $this->get_comment_values();
            return;
        }

        $content = substr( $content, 0, $comment_end );
        $content = explode( "\n", $content );
        $plugin_parameters = array();

        foreach ( $content as $value ) {
            /**
             * Each parameters should be separate from its value by a ':'
             * The value possess some of ':' so we just need to know the first
             */
            $separator = strpos( $value, ':' );

            /**
             * This is not a valid key/value pair
             */
            if ( $separator === false ) {
                continue;
            }

            /**
             * Remove every '*' and space before and after the key
             */
            $comment_key = trim( substr( $value, 0, $separator ), '* ' );

            /**
             * Remove spaces before and after the value
             */
            $comment_value = trim( substr( $value, $separator + 1 ), ' ' );
            
            $plugin_parameters[$comment_key] = $comment_value;
        }

        $this->get_comment_values( $plugin_parameters );
    }

    /**
     * Get_comment_values.
     * 
     * Given parameters will be given to the object
     * If a wanted parameter doesn't exist, return the default value
     * 
     * @since 1.3.0
     * @access private
     * 
     * @param array $parameters     Contains all parameters of the plugin
     */
    private function get_comment_values( $parameters = array() ) {
        $default_parameters = array(
            'Plugin Name' => ucfirst( implode( ' ', explode( '_', $this->name ) ) ),
            'Description' => '',
            'Version' => ''
        );

        $parameters = array_merge( $default_parameters, $parameters );
        
        $this->pretty_name  = $parameters['Plugin Name'];
        $this->description  = $parameters['Description'];
        $this->version      = $parameters['Version'];
    }

    /**
     * Activate.
     * 
     * Call the action hook of activation for this plugin.
     * 
     * @since 1.3.0
     * @access public
     * 
     * @param bool $force_activation    The action will be called even if the plugin is already activate
     *                                  You should keep it to false except on main plugin activation
     */
    public function activate( $force_activation = false ) {
        if ( $force_activation || ! $this->enable ) {
            $this->include_main_file();
            do_action( 'tfi_plugins_activate_' . $this->name );
            $this->enable = true;
        }
    }

    /**
     * Deactivate.
     * 
     * Call the action hook of deactivation for this plugin.
     * 
     * @since 1.3.0
     * @access public
     * 
     * @param bool $force_deactivation  The action will be called even if the plugin is already deactivate
     *                                  You should keep it to false except on main plugin deactivation
     */
    public function deactivate( $force_deactivation = false ) {
        if ( $force_deactivation || $this->enable ) {
            do_action( 'tfi_plugins_deactivate_' . $this->name );
            $this->enable = false;
        }
    }

    /**
     * Uninstall.
     * 
     * Call the uninstall hook for this plugin.
     * If the plugin has a uninstall file, add it
     * 
     * @since 1.3.0
     * @access public
     */
    public function uninstall() {
        if ( $this->uninstall_file != null ) {
            include_once $this->uninstall_file;
        }

        /**
         * Once all is well uninstalled, delete files
         */
        tfi_delete_files( TFI_PLUGINS_FOLDER_PATH . $this->name );
    }
}

/**
 * Once the plugin is loaded, create the plugin manager instance
 */
add_action( 'plugins_loaded', array( 'TFI\\PluginsManager', 'instantiate_plugins' ) );