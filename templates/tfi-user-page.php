<?php
if ( ! current_user_can( 'access_intranet' ) ) {
    wp_die( __( 'You don\'t have the permission to be here' ) );
}

$redirect_url = get_home_url();

/**
 * Header file for the TFI user page plugins
 * 
 * @since 1.0.0
 * @since 1.1.3     Add the parallax of tempus fugit site css and js. It's because I don't know how to add css with wordpress in a custom template
 */
?><!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="stylesheet" href="<?php echo esc_attr( TFI_URL ) . 'assets/css/user-page.css'; ?>" />
        <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri() . '/assets/css/parallax.css'; ?>" />
	</head>
	<body>
		<header id="tfi-user-page-header">
            <h1><?php the_title(); ?></h1>
            <div id="header-buttons">
                <a href="<?php esc_attr_e( $redirect_url ); ?>"><?php esc_html_e( 'Come back to site' ); ?></a>
                <a href="<?php esc_attr_e( wp_logout_url( $redirect_url ) ); ?>"><?php esc_html_e( 'Log out' ); ?></a>
            </div>
        </header>
        <div id="tfi-user-page-content">
        <?php
        global $post;
        setup_postdata( $post );
        the_content();
        wp_reset_postdata( $post );
        ?>
        </div>
        <footer id="tfi-user-page-footer">
            <small><?php printf( esc_html__( 'Page load by plugin %s' ), '<b>' . esc_html__( 'Tempus Fugit Intranet' ) . '</b>' ); ?></small>
        </footer>
        <script type="text/javascript" src="<?php echo get_stylesheet_directory_uri() . '/assets/js/parallax.js'; ?>"></script>
    </body>
</html>