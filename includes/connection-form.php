<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tfi site connection form manager.
 *
 * Display and manage the form which is display in the site
 *
 * @since 1.0.0
 */

class ConnectionFormManager {

    /**
     * Errors.
     * 
     * Keep errors to be displayed when the connection failed 
     * 
     * @since 1.0.0
     * @access private
     * 
     * @var array
     */
    private $errors;

	/**
	 * ConnectionFormManager constructor.
	 *
	 * Initializing all actions to do on the page
	 *
	 * @since 1.0.0
	 * @access public
	 */
    public function __construct() {
        add_action( 'init', array( $this, 'try_connection' ) );
        add_action( 'wp_footer', array( $this, 'load_assets' ) );
        add_action( 'wp_footer', array( $this, 'html_form' ), 100 );
    }

	/**
	 * Load_assets.
	 *
	 * Add js and css files needed for the connection form
	 *
	 * @since 1.0.0
	 * @access public
	 */
    public function load_assets() {
        wp_enqueue_script( 'form-load', TFI_URL . 'assets/js/form-load.js', array(), "1.0", true );
        wp_localize_script( 'form-load', 'tfi_form_shortcut', tfi_get_option( 'tfi_shortcut' ) );
        wp_enqueue_style( 'form-style', TFI_URL . 'assets/css/form.css' );

        if ( current_user_can( 'access_intranet' ) ) {
            wp_localize_script( 'form-load', 'tfi_user_page_url', get_permalink( tfi_get_option( 'tfi_user_page_id' ) ) );
        }
    }

	/**
	 * Html_form.
	 *
	 * Html construction of the form added to the site page
     * All exceptions are handled and displayed in a little box 
	 *
	 * @since 1.0.0
	 * @access public
	 */
    public function html_form() {
        ?>
        <div id="tfi-form-container" <?php if ( empty ( $this->errors ) ): ?> style="display: none;" <?php endif; ?>>
            <div id="tfi-form-wrapper">
                <?php
                $actual_url = get_permalink( get_the_ID() );
                $user_page_url = get_permalink( tfi_get_option( 'tfi_user_page_id' ) );
                if ($user_page_url === false || get_page_template_slug( tfi_get_option( 'tfi_user_page_id' ) ) != TFI_TEMPLATE_PAGE ): ?>
                <div class="tfi-message"><?php esc_html_e( 'To be able to access intranet user page, a valid user-page should be set first... If you want to access it, please refer to your administrator about this warning' ); ?></div>
                <?php elseif ( current_user_can( 'access_intranet' ) ): ?>
                <div class="tfi-message"><?php esc_html_e( 'Are you sure you want to access the intranet ?' ); ?></div>
                <div class="tfi-form-submit-container">
                    <input type="button" onclick="tfi_display_form(false)" class="tfi-form-button" id="tfi-form-cancel" value="<?php esc_attr_e( 'No' ); ?>" />
                    <input type="button" onclick="tfi_redirect_user_page()" class="tfi-form-button" id="tfi-form-submit" value="<?php esc_attr_e( 'Yes (redirect)' ); ?>" />
                </div>
                <?php else: ?>
                <?php if ( ! empty ( $this->errors ) ): ?>
                <div id="tfi-form-errors-wrapper">
                    <?php foreach( $this->errors as $error ): ?>
                    <p class="tfi-form-error"><?php esc_html_e( $error ); ?><p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="tfi-message"><?php esc_html_e( 'Please connect you before accessing to the intranet' ); ?></div>
                <form id="tfi-form" action="<?php esc_attr_e( $actual_url ); ?>" method="post">
                    <input type="hidden" name="tfi[last_url]" value="<?php esc_attr_e( $actual_url ); ?>" /> 
                    <div>
                        <label for="tfi-user-id"><?php esc_html_e( 'Your id :' ); ?></label>
                        <input class="tfi-input" id="tfi-user-id" type="text" name="tfi[user_id]" value="<?php echo isset( $_POST['tfi']['user_id'] ) ? esc_attr__( $_POST['tfi']['user_id'] ) : ''; ?>" />
                    </div>
                    <div>
                        <label for="tfi-password"><?php esc_html_e( 'Password :' ); ?></label>
                        <input class="tfi-input" type="password" name="tfi[password]" id="tfi-password" value="" />
                    </div>
                    <div class="tfi-form-submit-container">
                        <input type="button" onclick="tfi_display_form(false)" class="tfi-form-button" id="tfi-form-cancel" value="<?php esc_attr_e( 'Cancel' ); ?>" />
                        <input type="submit" class="tfi-form-button" id="tfi-form-submit" value="<?php esc_attr_e( 'Login' ); ?>" />
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Try_connection.
     * 
     * If the user tried to log in, verify the login
     * If the login is okay, redirects to the user-page
     * If not, display errors on the form
	 *
	 * @since 1.0.0
	 * @access public
     */
    public function try_connection() {
        if ( isset( $_POST['tfi']['user_id'] ) && isset( $_POST['tfi']['password'] ) ) {
            $connection = wp_signon( array(
                "user_login" => $_POST['tfi']['user_id'],
                "user_password" => $_POST['tfi']['password']
            ) );
            $url = get_permalink( tfi_get_option( 'tfi_user_page_id' ) );
            
            if ( ! empty( $connection->errors ) ) {
                $this->errors = array(
                    __( 'Login or password incorrect' )
                );
            }
            else if ( wp_redirect( $url ) ) {
                exit;
            }
            else {
                $this->errors = array(
                    __( 'The redirection url isn\'t valid, please try later' )
                );
            }
        }
        else {
            $this->errors = array();
        }
    }
}