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
                    <p class="tfi-form-error"><?php echo $error; ?><p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="tfi-message"><?php esc_html_e( 'Please connect you before accessing to the intranet' ); ?></div>
                <form id="tfi-form" action="<?php esc_attr_e( $actual_url ); ?>" method="post">
                    <input type="hidden" name="tfi[last_url]" value="<?php esc_attr_e( $actual_url ); ?>" /> 
                    <div>
                        <label for="tfi-user-login"><?php esc_html_e( 'Your login :' ); ?></label>
                        <input class="tfi-input" id="tfi-user-login" type="text" name="tfi[login]" value="<?php echo isset( $_POST['tfi']['login'] ) ? esc_attr__( $_POST['tfi']['login'] ) : ''; ?>" />
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
        if ( isset( $_POST['tfi']['login'] ) && isset( $_POST['tfi']['password'] ) ) {
            $password   = $_POST['tfi']['password'];
            $login      = $_POST['tfi']['login'];

            /**
             * Try a wordpress connection, if this value is true, the user will be connected
             */
            $wp_user = wp_signon( array(
                "user_login" => $login,
                "user_password" => $password
            ) );

            $is_wp_user = ! is_wp_error( $wp_user );

            require_once TFI_PATH . 'includes/echo/echo-api.php';

            /**
             * If it returns true, it means that the user is registered in the echo base, so, we need to add it to the wordpress users.
             */
            $is_echo_user = Api::get()->try_login( $login, $password );
            
            /**
             * First case : the user doesn't exists and is not an echo user -> display wp errors
             */
            if ( ! $is_wp_user && ! $is_echo_user ) {
                $this->errors = array(
                    $wp_user->get_error_message()
                );
            }
            /**
             * Second case : the user is a wordpress user, but not an echo one. 
             * Verification if the user need to be store in the echo database
             */
            else if ( $is_wp_user && ! $is_echo_user ) {
                require_once TFI_PATH . 'includes/user.php';

                $user = new User( $wp_user->ID );
                if ( $user->is_echo_user() ) {
                    $result = Api::get()->register( $login, $password );

                    if ( $result === false ) {
                        $this->errors = array(
                            '<strong>' . esc_html__( 'Echo API error:' ) . '</strong>' . esc_html__( Api::get()->last_error )
                        );
                    }
                }
            }
            /**
             * Third case : the user is an echo user, but not a wordpress one
             * We store the user in the wordpress database
             */
            else if ( ! $is_wp_user && $is_echo_user ) {
                if ( empty( $password ) ) {
                    $this->errors = array(
                        '<strong>' . esc_html__( 'New intranet user error: ' ) . '</strong>' .
                        esc_html__( 'Impossible to create a new user with an empty password.' ) . '<br />' .
                        esc_html__( 'Note that this is strange that you\'re an echo user without password, ask your administrator about that please' )
                    );
                }
                else {
                    $datas = array(
                        'user_pass' => $password,
                        'user_login' => $login,
                        'role' => 'tfi_user'
                    );
    
                    //wp_die( var_dump( $datas ) );
                    $wp_user_id = wp_insert_user( $datas );
    
                    if ( is_wp_error( $wp_user_id ) ) {
                        $this->errors = array(
                            '<strong>' . esc_html__( 'New intranet user error: ' ) . '</strong>' . $wp_user_id->get_error_message()
                        );
                    }
                    else {
                        $new_wp_user = wp_signon( array(
                            "user_login" => $login,
                            "user_password" => $password
                        ) );
    
                        if ( is_wp_error( $new_wp_user ) ) {
                            $this->errors = array(
                                '<strong>' . esc_html__( 'New intranet user error: ' ) . '</strong>' .
                                esc_html__( 'Success to create user but the login failed with the following error: ' ) . $new_wp_user->get_error_message()
                            );
                        }
                    }
                }
            }
            /**
             * Last case : the user is an echo user and a wordpress one, he can connect
             */
            
            if ( empty( $this->errors ) ) {
                /**
                 * If there is no errors, redirect the permalink to the intranet user page
                 */
                if ( wp_redirect( get_permalink( tfi_get_option( 'tfi_user_page_id' ) ) ) ) {
                    exit;
                }

                $this->errors = array(
                    esc_html__( 'The redirection url isn\'t valid, please try later' )
                );
            }

            /**
             * Not possible because it will recall the page without post (and so no errors) 
             */
            wp_logout_url( get_permalink( get_the_ID() ) );
        }
        else {
            $this->errors = array();
        }
    }
}