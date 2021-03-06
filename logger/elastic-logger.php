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

    /**
     * Map activity log ECS schema
     * @param $args
     * @return array
     * @see https://www.elastic.co/guide/en/ecs/current/ecs-reference.html
     */
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
                'roles' => isset( $args['user_caps'] ) ? [ $args['user_caps'] ] : [],
            ],
        ];

        // try to add IP address
        $ip = $this->get_client_ip();
        if ( isset( $ip ) ) {
            $ecs['client'] = [
                'ip' => $ip,
            ];
        }

        // include user agent info if available
        if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
            $ecs['user_agent'] = [
                'original' => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ),
            ];
        }

        // authentication -> event.category: authentication
        $auth_actions = [ 'logged_in', 'login_failed', 'password_reset_request', 'password_reset' ];
        if ( $object_type == 'User' && in_array( $action, $auth_actions ) ) {
            $ecs['event']['category'] = 'authentication';

            // user.id is not set correctly on logged_in, so map that specially
            $ecs['user']['id'] = $args['object_id'];
            $ecs['user']['name'] = $args['object_name'];
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
        if ( in_array( $object_type, [ 'plugin', 'theme', 'core' ] ) ) {
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

        // flesh out the user data
        if ( isset( $ecs['user']['id'] ) && !empty( $ecs['user']['id'] ) ) {
            $user_info = get_userdata( $ecs['user']['id'] );
            $ecs['user']['name'] = $user_info->user_login;
            if ( empty( $ecs['user']['roles'] ) ) {
                $ecs['user']['roles'] = $user_info->roles;
            } else {
                $ecs['user']['roles'] = array_unique( array_merge( $ecs['user']['roles'], $user_info->roles ) );
            }
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

    /**
     * @return mixed|null
     */
    private function get_client_ip() {
        $ipaddress = null;
        if (isset( $_SERVER['HTTP_CLIENT_IP'] )) {
            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } else if (isset( $_SERVER['HTTP_X_FORWARDED_FOR'] )) {
            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        } else if (isset( $_SERVER['HTTP_X_FORWARDED'] )) {
            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED'] ) );
        } else if (isset( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] )) {
            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) );
        } else if (isset( $_SERVER['HTTP_FORWARDED_FOR'] )) {
            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED_FOR'] ) );
        } else if (isset( $_SERVER['HTTP_FORWARDED'] )) {
            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED'] ) );
        } else if (isset( $_SERVER['REMOTE_ADDR'] )) {
            $ipaddress = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }
        return $ipaddress;
    }
}
DT_Advanced_Security_Elastic_Logger::instance();
