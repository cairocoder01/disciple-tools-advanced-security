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

            $ecs = $this->map_activity_to_ecs( $args );

            $this->post_to_api( $ecs );

        } catch ( Exception $ex ) {
            dt_write_log( json_encode( $ex ) );
        }
    }

    private function map_activity_to_ecs( $args ) {

        $plugin_data = get_plugin_data( dirname( __FILE__, 2 ) . '/disciple-tools-advanced-security.php' );
        $version = $plugin_data['Version'];

        $action = $args['action'];
        $object_type = $args['object_type'];
        $http_regex = '/http[s]?:\/\//';

        $ecs = [
            '@timestamp' => gmdate( DateTimeInterface::RFC3339_EXTENDED, $args['hist_time'] ),
            'labels' => $args,
            'message' => "action:$action object_type:$object_type",
            'event' => [
                'action' => "$object_type:$action",
                'original' => json_encode( $args ),
                'module' => 'dt-advanced-security-plugin',
                'url' => $this->get_url(),
                'kind' => 'event',
                // 'category' => '', // mapped below and blank if no match
                'type' => [],
            ],
            'host' => [
                'hostname' => preg_replace( $http_regex, "", home_url() ),
            ],
            'agent' => [
                'name' => preg_replace( $http_regex, "", home_url() ),
                'type' => 'dt-advanced-security-plugin',
                'version' => $version
            ],
            'user' => [
                'id' => isset( $args['user_id'] ) ? $args['user_id'] : null,
                'roles' => isset( $args['user_caps'] ) ? [ $args['user_caps'] ] : null,
            ]
        ];

        // authentication -> event.category: authentication
        $auth_actions = [ 'logged_in', 'login_failed', 'password_reset_request', 'password_reset' ];
        if ( $object_type == 'User' && in_array( $action, $auth_actions ) ) {
            $ecs['event']['category'] = 'authentication';
        }

        // user changes -> event.category: iam
        $iam_actions = [ 'created', 'add_role', 'remove_role', 'add_to_site', 'remove_from_site', 'granted_super_admin', 'revoked_super_admin', 'deleted' ];
        if ( $object_type == 'User' && in_array( $action, $iam_actions ) ) {
            $ecs['event']['category'] = 'iam';
            $ecs['event']['type'][] = 'user';
            if ( $action == 'created' ) {
                $ecs['event']['type'][] = 'creation';
            } else if ( $action == 'deleted' ) {
                $ecs['event']['type'][] = 'deletion';
            } else {
                $ecs['event']['type'][] = 'change';
            }
        }

        // plugin -> event.category: package
        if ( $object_type == 'plugin' || $object_type == 'core' ) {
            $ecs['event']['category'] = 'package';

            if ( in_array( $action, [ 'delete', 'delete_fail', 'deactivate_network', 'deactivate' ] ) ) {
                $ecs['event']['type'][] = 'deletion';
                if ( $action == 'delete_fail' ) {
                    $ecs['event']['outcome'] = 'failure';
                }
            } else if ( in_array( $action, [ 'activate_network', 'activate' ] ) ) {
                $ecs['event']['type'][] = 'installation';
            } else if ( in_array( $action, [ 'update' ] ) ) {
                $ecs['event']['type'][] = 'change';
            }
        }

        // export -> event.category: process
        if ( $action == 'export' ) {
            $ecs['event']['category'] = 'process';
            $ecs['event']['type'][] = 'access';
        }

        // site_link_system -> event.category: database
        if ( $object_type == 'site_link_system' ) {
            $ecs['event']['category'] = 'database';
            $ecs['event']['type'][] = 'change';
        }

        return $ecs;
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

    /**
     * @return string
     */
    private function get_url() {
        if ( !isset( $_SERVER['REQUEST_URI'] ) ) {
            return null;
        }
        $uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

        if ( !isset( $_SERVER['HTTP_HOST'] ) ) {
            return $uri;
        }
        $host = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );

        return ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) .
            "://$host$uri";
    }
}
DT_Advanced_Security_Elastic_Logger::instance();
