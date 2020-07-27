<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tfi plugin.
 *
 * The main plugin handler class is responsible for initializing tfi. The
 * class registers and all the components required to run the plugin.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Instance.
	 *
	 * Holds the plugin instance.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @var Plugin
	 */
	public static $instance = null;

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the plugin class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return Plugin An instance of the class.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
    }

    private function addAdminOption() {
        require_once TFI_PATH . 'includes/admin-panel.php';

        new AdminPanelManager();
    }
    
    private function addFormManager() {
        require_once TFI_PATH . 'includes/connection-form.php';

        new ConnectionFormManager();
	}
	
	private function addSortcodesManager() {
        require_once TFI_PATH . 'includes/shortcodes.php';

        new ShortcodesManager();
	}

	/**
	 * Plugin constructor.
	 *
	 * Initializing tfi plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function __construct() {
        if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
            $this->addAdminOption();
        }
        else {
            $this->addFormManager();
			$this->addSortcodesManager();
		}
	}
}

add_action( 'plugins_loaded', array( 'TFI\\Plugin', 'instance' ) );