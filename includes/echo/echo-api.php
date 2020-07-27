<?php
namespace TFI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class Api
 * 
 * Connect this website to the echo api
 * 
 * @since 1.3.0
 */
class Api {
    public $last_error = '';
    private $cache = array();
    private $echo_api_url = 'https://ip232.ip-51-210-179.eu/api-echo-v1';
    private $login_api_url = 'https://ip232.ip-51-210-179.eu/api-login-v1';
    private $token = '';

    private static $instance;

    public static function get() {
        if ( self::$instance === null ) {
            self::$instance = new Api;
        }
        return self::$instance;
    }

    private function post( $url, $datas = array() ) {
        $response   = wp_remote_post( $url, array( 'body' => $datas ) );
        $code       = wp_remote_retrieve_response_code( $response );
        $value      = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( $code !== 200 ) {
            $this->last_error = 'Error during a post request, code return = ' . $code; 
            return false;
        }
        else if ( $value['result'] === false ) {
            $this->last_error = $value['error'];
            return false;
        }

        return $value;
    }

    private function fetch( $url, $datas = array() ) {
        $response   = wp_remote_get( $url, array( 'body' => $datas ) );
        $code       = wp_remote_retrieve_response_code( $response );
        $value      = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( $code !== 200 ) {
            $this->last_error = 'Error during a get request, code return = ' . $code; 
            return false;
        }
        else if ( $value['result'] === false ) {
            $this->last_error = $value['error'];
            return false;
        }

        return $value;
    }

    public function try_login( $username, $hash ) {
        return true;
    }

    public function register( $username, $hash ) {
        return true;
    }

    /**
     * Get_templates_for_user.
     * 
     * Get all templates for the logged user. If there is no template, create a default one
     * 
     * @since 1.3.0
     * @access public
     * 
     * @return array    All template object existing for this user
     * @return false    An error occured in the query see Api::last_error to get the error
     */
    public function get_templates_for_user() {
        if ( ! array_key_exists( 'templates', $this->cache ) ) {
            if ( $this->get_templates_and_campains_for_user() === false ) {
                return false;
            }
        }

        return $this->cache['templates'];
    }

    /**
     * Get_campains_for_user.
     * 
     * Get all campains for the logged user
     * 
     * @since 1.3.0
     * @access public
     * 
     * @return array    All campains name existing for this user
     * @return false    An error occured in the query see Api::last_error to get the error
     */
    public function get_campains_for_user() {
        if ( ! array_key_exists( 'campains', $this->cache ) ) {
            if ( $this->get_templates_and_campains_for_user() === false ) {
                return false;
            }
        }

        return $this->cache['campains'];
    }

    /**
     * Add_template_for_user.
     * 
     * @since 1.3.0
     * @access public
     * 
     * @param string    $template_name  The name of the new template
     * @param string    $campain        The name of the campain for the new template
     * 
     * @return Template The creating template object
     * @return false    An error occured in the query see Api::last_error to get the error
     */
    public function add_template_for_user( $template_name, $campain ) {
        $value = $this->post( $this->echo_api_url . '/insert/template', array(
            'token' => $this->token,
            'path' =>  $template_name,
            'campain' => $campain
        ) );
        
        /**
         * Error
         */
        if ( $value === false ) {
            return false;
        }

        require_once TFI_PATH . 'includes/echo/echo-template.php';
        return new Template( $value['template_id'], $template_name, $campain );
    }

    /**
     * Get_templates_and_campains_for_user.
     * 
     * Method which add campains and templates to the cache.
     * It is used because both can be get with the same request
     * 
     * @since 1.3.0
     * @access private
     * 
     * @return bool The success of the operation, false means there is an error
     */
    private function get_templates_and_campains_for_user() {
        $value = $this->fetch( $this->echo_api_url . '/campains/' . $this->token . 'templates' );
        
        /**
         * Error
         */
        if ( $value === false ) {
            return false;
        }
        
        unset( $value['result'] );

        $templates = array();
        $campains  = array();
        require_once TFI_PATH . 'includes/echo/echo-template.php';

        foreach ( $value as $campain_name => $user_templates ) {
            foreach ( $user_templates as $template_id => $template_value ) {
                array_push( $templates, new Template( $template_id, $template_value, $campain_name ) );
            }
            if ( ! in_array( $campain_name, $campains, true ) ) {
                array_push( $campains, $campain_name );
            }
        }

        if ( empty( $templates ) ) {
            if ( empty( $campains ) ) {
                $this->last_error = 'Impossible to get templates, there is no campain at all';
                return false;
            }

            $new_template = $this->add_template_for_user( 'default', $campains[0] );
            
            /**
             * Error when creating the new template
             */
            if ( $new_template === false ) {
                return false;
            }

            array_push( $templates, $new_template );
        }

        $this->cache['templates'] = $templates;
        $this->cache['campains']   = $campains;

        return true;
    }
}