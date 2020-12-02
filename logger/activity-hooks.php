<?php

class DT_Advanced_Security_Hooks
{
    private $current_ip_address = null;
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        add_action( 'wp_login_failed', [ $this, 'login_failed' ], 10, 2 );

        // Set user IP address
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $this->current_ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $this->current_ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $this->current_ip_address = $_SERVER['REMOTE_ADDR'];
        }
    }

    public function login_failed( $username, $error ) {

        dt_activity_insert(
            [
                'action' => 'login_failed',
                'object_type' => 'User',
                'object_name' => $username,
                'object_note' => $error->get_error_code(),
                'hist_ip' => $this->current_ip_address,
            ]
        );
    }
}
DT_Advanced_Security_Hooks::instance();
