<?php
require_once "logger-base.php";

class DT_Advanced_Security_Elastic_Logger extends DT_Advanced_Security_Base_Logger
{
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    protected function enabled() {
        $settings = json_decode( get_option( "dt_advanced_security_elastic_logger" ), true );
        return $settings && $settings['enabled'];
    }

    public function log_activity( $args ) {
        try {

            if ( !$this->should_write_log( $args ) ) {
                return;
            }

            $this->post_to_api( $args );

        } catch ( Exception $ex ) {
            dt_write_log( json_encode( $ex ) );
        }
    }

    /**
     * Post given data to Elastic endpoint saved in settings
     * @param $data
     * @return bool|WP_Error
     */
    public function post_to_api( $data ) {
        $settings = json_decode( get_option( "dt_advanced_security_elastic_logger" ), true );

        if ( !isset( $settings['url'] ) ) {
            return new WP_Error( 'missing_url', __( 'URL must be included to make API request' ) );
        }

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8'
            ),
            'body' => json_encode( $data ),
        );

        // Add auth token if it is part of the config
        if ( isset( $settings['username'] ) && isset( $settings['password'] ) ) {
            $basicauth = 'Basic ' . base64_encode( $settings['username'] . ':' . $settings['password'] );
            $args['headers']['Authorization'] = $basicauth;
        }

        // POST the data to the endpoint
        $result = wp_remote_post( $settings['url'], $args );

        if (is_wp_error( $result )) {
            $error_message = $result->get_error_message() ?? '';
            dt_write_log( $error_message );
            return $result;
        } else {
            // Success
            return true;
        }
    }
}
DT_Advanced_Security_Elastic_Logger::instance();
